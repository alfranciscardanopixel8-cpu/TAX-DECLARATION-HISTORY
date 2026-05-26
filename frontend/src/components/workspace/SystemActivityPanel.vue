<template>
  <div class="ws-page audit-page">
    <WorkspacePageHeader
      module="Operations"
      title="Audit Logs"
      lead="Searchable trail of every change made across the system."
    >
      <template #actions>
        <q-btn outline no-caps color="primary" icon="download" label="Export CSV" :loading="exporting" @click="exportCsv" />
        <q-btn outline no-caps color="primary" icon="refresh" :loading="loading" @click="reload">
          <q-tooltip>Refresh</q-tooltip>
        </q-btn>
      </template>
    </WorkspacePageHeader>

    <!-- Stat strip -->
    <div class="audit-stats">
      <div class="audit-stat">
        <div class="audit-stat__label">All time</div>
        <div class="audit-stat__value">{{ formatNumber(summary.totals.all_time) }}</div>
      </div>
      <div class="audit-stat">
        <div class="audit-stat__label">Last 7 days</div>
        <div class="audit-stat__value">{{ formatNumber(summary.totals.last_7_days) }}</div>
      </div>
      <div class="audit-stat">
        <div class="audit-stat__label">Today</div>
        <div class="audit-stat__value">{{ formatNumber(summary.totals.today) }}</div>
      </div>
      <div class="audit-stat audit-stat--match">
        <div class="audit-stat__label">Matching filter</div>
        <div class="audit-stat__value">{{ formatNumber(meta.total) }}</div>
      </div>
    </div>

    <!-- Filters -->
    <section class="ws-card audit-filters">
      <div class="audit-filters__row">
        <q-input
          v-model="filters.search"
          dense
          outlined
          clearable
          debounce="350"
          class="audit-filter audit-filter--search"
          placeholder="Search description, user, PIN, lot, TD number…"
          @update:model-value="onFilterChange"
        >
          <template #prepend>
            <q-icon name="search" />
          </template>
        </q-input>

        <q-select
          v-model="filters.action"
          dense
          outlined
          clearable
          multiple
          use-chips
          emit-value
          map-options
          :options="actionOptions"
          option-label="display"
          option-value="value"
          label="Action"
          class="audit-filter audit-filter--action"
          @update:model-value="onFilterChange"
        />

        <q-select
          v-model="filters.user_id"
          dense
          outlined
          clearable
          emit-value
          map-options
          :options="userOptions"
          option-label="name"
          option-value="id"
          label="User"
          class="audit-filter"
          @update:model-value="onFilterChange"
        />

        <q-input
          v-model="filters.from"
          dense
          outlined
          clearable
          mask="####-##-##"
          placeholder="From"
          class="audit-filter audit-filter--date"
          @update:model-value="onFilterChange"
        >
          <template #prepend>
            <q-icon name="event" class="cursor-pointer">
              <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                <q-date v-model="filters.from" mask="YYYY-MM-DD" today-btn @update:model-value="onFilterChange">
                  <div class="row items-center justify-end">
                    <q-btn v-close-popup label="Close" color="primary" flat />
                  </div>
                </q-date>
              </q-popup-proxy>
            </q-icon>
          </template>
        </q-input>

        <q-input
          v-model="filters.to"
          dense
          outlined
          clearable
          mask="####-##-##"
          placeholder="To"
          class="audit-filter audit-filter--date"
          @update:model-value="onFilterChange"
        >
          <template #prepend>
            <q-icon name="event" class="cursor-pointer">
              <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                <q-date v-model="filters.to" mask="YYYY-MM-DD" today-btn @update:model-value="onFilterChange">
                  <div class="row items-center justify-end">
                    <q-btn v-close-popup label="Close" color="primary" flat />
                  </div>
                </q-date>
              </q-popup-proxy>
            </q-icon>
          </template>
        </q-input>

        <q-btn flat dense no-caps color="primary" icon="restart_alt" label="Reset" :disable="!hasFilters" @click="resetFilters" />
      </div>

      <div v-if="quickFilters.length" class="audit-filters__quick">
        <span class="audit-filters__quick-label">Quick filters:</span>
        <q-btn
          v-for="qf in quickFilters"
          :key="qf.value"
          flat
          dense
          no-caps
          size="sm"
          :class="['audit-quick-chip', { 'audit-quick-chip--active': isQuickActive(qf.value) }]"
          @click="toggleQuickFilter(qf.value)"
        >
          {{ qf.label }}
          <span class="audit-quick-chip__count">{{ qf.total }}</span>
        </q-btn>
      </div>
    </section>

    <!-- Log list -->
    <section class="ws-card ws-card--flush audit-list-wrap">
      <div v-if="loading && !activity.length" class="audit-loading">
        <q-spinner color="primary" size="32px" />
        <span>Loading audit log…</span>
      </div>

      <div v-else-if="!activity.length" class="ws-empty">
        <div class="empty-icon-wrapper">
          <q-icon name="fact_check" size="64px" />
        </div>
        <strong>No matching audit events</strong>
        <span>Try widening the date range, clearing filters, or checking again after recording new activity.</span>
      </div>

      <ul v-else class="audit-list">
        <li v-for="log in activity" :key="log.id" class="audit-row" :class="{ 'audit-row--open': isExpanded(log.id) }">
          <button type="button" class="audit-row__header" @click="toggle(log.id)">
            <span class="audit-row__time">
              <span class="audit-row__date">{{ formatDate(log.created_at) }}</span>
              <span class="audit-row__relative">{{ relativeTime(log.created_at) }}</span>
            </span>

            <span class="audit-row__main">
              <span :class="['audit-tag', actionClass(log.action)]">{{ actionLabel(log.action) }}</span>
              <span class="audit-row__description">{{ log.description }}</span>
              <span class="audit-row__meta">
                <span v-if="log.user" class="audit-row__user">
                  <q-icon name="person" size="14px" />
                  {{ log.user.name }}<span v-if="log.user.role" class="audit-role">· {{ log.user.role }}</span>
                </span>
                <span v-else class="audit-row__user audit-row__user--system">
                  <q-icon name="settings" size="14px" /> System
                </span>
                <span v-if="log.property" class="audit-row__property">
                  <q-icon name="home_work" size="14px" />
                  PIN {{ log.property.pin || '—' }}
                  <span v-if="log.property.lot_number">· Lot {{ log.property.lot_number }}</span>
                </span>
                <span v-if="log.tax_declaration" class="audit-row__td">
                  <q-icon name="receipt_long" size="14px" /> TD {{ log.tax_declaration.td_number }}
                </span>
                <span v-if="log.document" class="audit-row__doc">
                  <q-icon name="description" size="14px" /> {{ log.document.document_type }}
                  <span v-if="log.document.reference_number">· {{ log.document.reference_number }}</span>
                </span>
              </span>
            </span>

            <span class="audit-row__actions">
              <q-btn
                v-if="log.property_id"
                flat
                dense
                size="sm"
                no-caps
                color="primary"
                icon="open_in_new"
                @click.stop="$emit('open-property', log.property_id)"
              >
                <q-tooltip>Open property</q-tooltip>
              </q-btn>
              <q-icon
                v-if="hasDiff(log)"
                :name="isExpanded(log.id) ? 'expand_less' : 'expand_more'"
                class="audit-row__chev"
              />
            </span>
          </button>

          <div v-if="isExpanded(log.id) && hasDiff(log)" class="audit-row__diff">
            <div class="audit-diff">
              <div v-if="log.old_values" class="audit-diff__col">
                <div class="audit-diff__label audit-diff__label--old">Before</div>
                <pre>{{ formatJson(log.old_values) }}</pre>
              </div>
              <div v-if="log.new_values" class="audit-diff__col">
                <div class="audit-diff__label audit-diff__label--new">After</div>
                <pre>{{ formatJson(log.new_values) }}</pre>
              </div>
            </div>
          </div>
        </li>
      </ul>

      <div v-if="meta.total > 0" class="audit-pagination">
        <div class="audit-pagination__info">
          Showing {{ meta.from || 0 }}–{{ meta.to || 0 }} of {{ formatNumber(meta.total) }}
        </div>
        <q-pagination
          v-model="page"
          :max="meta.last_page || 1"
          :max-pages="6"
          boundary-numbers
          direction-links
          color="primary"
          @update:model-value="loadActivity"
        />
        <q-select
          v-model="perPage"
          dense
          outlined
          :options="[10, 25, 50, 100]"
          label="Per page"
          class="audit-perpage"
          @update:model-value="onPerPageChange"
        />
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useQuasar } from 'quasar';
import {
  fetchActivityLogs,
  fetchActivityLogSummary,
  downloadActivityLogsCsv,
  fetchUsers
} from '../../services/api';
import WorkspacePageHeader from '../../components/layout/WorkspacePageHeader.vue';

defineEmits(['open-property']);

const $q = useQuasar();

const loading = ref(false);
const exporting = ref(false);
const activity = ref([]);
const expanded = ref(new Set());
const users = ref([]);

const summary = ref({
  actions: [],
  totals: { all_time: 0, last_7_days: 0, today: 0 }
});

const meta = ref({ current_page: 1, last_page: 1, per_page: 25, total: 0, from: 0, to: 0 });

const page = ref(1);
const perPage = ref(25);

const filters = reactive({
  search: '',
  action: [],
  user_id: null,
  from: null,
  to: null
});

const ACTION_LABELS = {
  created: 'Property Created',
  updated: 'Property Updated',
  approved: 'Property Approved',
  archived: 'Property Archived',
  tax_declaration_added: 'TD Added',
  tax_declaration_updated: 'TD Updated',
  tax_declaration_approved: 'TD Approved',
  tax_declaration_cancelled: 'TD Cancelled',
  assessment_added: 'Assessment Added',
  assessment_updated: 'Assessment Updated',
  assessment_removed: 'Assessment Removed',
  document_added: 'Document Added',
  document_updated: 'Document Updated',
  document_archived: 'Document Archived',
  document_digitized: 'Document Digitized',
  document_ocr: 'OCR Saved',
  physical_record_moved: 'Physical Move'
};

const ACTION_GROUPS = {
  create: ['created', 'tax_declaration_added', 'assessment_added', 'document_added'],
  update: ['updated', 'tax_declaration_updated', 'assessment_updated', 'document_updated', 'document_ocr'],
  approve: ['approved', 'tax_declaration_approved'],
  remove: ['archived', 'tax_declaration_cancelled', 'assessment_removed', 'document_archived'],
  physical: ['physical_record_moved', 'document_digitized']
};

const actionOptions = computed(() => summary.value.actions.map((a) => ({
  value: a.action,
  display: `${ACTION_LABELS[a.action] || prettify(a.action)} (${a.total})`
})));

const userOptions = computed(() => users.value.map((u) => ({ id: u.id, name: u.name })));

const quickFilters = computed(() => {
  const counts = Object.fromEntries((summary.value.actions || []).map((a) => [a.action, a.total]));
  return [
    { value: 'create', label: 'Creates', total: sumGroup(counts, ACTION_GROUPS.create) },
    { value: 'update', label: 'Updates', total: sumGroup(counts, ACTION_GROUPS.update) },
    { value: 'approve', label: 'Approvals', total: sumGroup(counts, ACTION_GROUPS.approve) },
    { value: 'remove', label: 'Removals', total: sumGroup(counts, ACTION_GROUPS.remove) },
    { value: 'physical', label: 'Physical / Scans', total: sumGroup(counts, ACTION_GROUPS.physical) }
  ].filter((qf) => qf.total > 0);
});

const hasFilters = computed(() => Boolean(
  filters.search || (filters.action && filters.action.length) || filters.user_id || filters.from || filters.to
));

function sumGroup(counts, actions) {
  return actions.reduce((sum, a) => sum + (counts[a] || 0), 0);
}

function prettify(value) {
  if (!value) return '';
  return value.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function actionLabel(action) {
  return ACTION_LABELS[action] || prettify(action);
}

function actionClass(action) {
  if (!action) return 'audit-tag--neutral';
  if (ACTION_GROUPS.remove.includes(action)) return 'audit-tag--remove';
  if (ACTION_GROUPS.approve.includes(action)) return 'audit-tag--approve';
  if (ACTION_GROUPS.create.includes(action)) return 'audit-tag--create';
  if (ACTION_GROUPS.update.includes(action)) return 'audit-tag--update';
  if (ACTION_GROUPS.physical.includes(action)) return 'audit-tag--physical';
  return 'audit-tag--neutral';
}

function formatDate(value) {
  if (!value) return '';
  return new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}

function relativeTime(value) {
  if (!value) return '';
  const diffMs = Date.now() - new Date(value).getTime();
  const diffSec = Math.round(diffMs / 1000);
  if (diffSec < 60) return 'just now';
  const diffMin = Math.round(diffSec / 60);
  if (diffMin < 60) return `${diffMin}m ago`;
  const diffHr = Math.round(diffMin / 60);
  if (diffHr < 24) return `${diffHr}h ago`;
  const diffDay = Math.round(diffHr / 24);
  if (diffDay < 7) return `${diffDay}d ago`;
  const diffWk = Math.round(diffDay / 7);
  if (diffWk < 5) return `${diffWk}w ago`;
  return `${Math.round(diffDay / 30)}mo ago`;
}

function formatNumber(value) {
  return new Intl.NumberFormat('en-PH').format(Number(value || 0));
}

function formatJson(value) {
  if (value === null || value === undefined) return '';
  try {
    return JSON.stringify(value, null, 2);
  } catch {
    return String(value);
  }
}

function hasDiff(log) {
  return Boolean(log?.old_values || log?.new_values);
}

function isExpanded(id) {
  return expanded.value.has(id);
}

function toggle(id) {
  const next = new Set(expanded.value);
  if (next.has(id)) {
    next.delete(id);
  } else {
    next.add(id);
  }
  expanded.value = next;
}

function buildParams() {
  const params = { page: page.value, per_page: perPage.value };
  if (filters.search) params.search = filters.search;
  if (filters.action && filters.action.length) params.action = filters.action;
  if (filters.user_id) params.user_id = filters.user_id;
  if (filters.from) params.from = filters.from;
  if (filters.to) params.to = filters.to;
  return params;
}

async function loadActivity() {
  loading.value = true;

  try {
    const data = await fetchActivityLogs(buildParams());
    activity.value = data.data || [];
    meta.value = data.meta || { current_page: 1, last_page: 1, per_page: perPage.value, total: 0, from: 0, to: 0 };
    page.value = meta.value.current_page || 1;
  } catch (error) {
    console.error('Failed to load audit logs', error);
    $q.notify({
      type: 'negative',
      message: 'Unable to load audit logs. Check that the API is reachable.',
      timeout: 5000
    });
    activity.value = [];
  } finally {
    loading.value = false;
  }
}

async function loadSummary() {
  try {
    const data = await fetchActivityLogSummary();
    summary.value = data;
  } catch {
    // non-blocking; filters degrade gracefully
  }
}

async function loadUsers() {
  try {
    users.value = await fetchUsers();
  } catch {
    users.value = [];
  }
}

function onFilterChange() {
  page.value = 1;
  loadActivity();
}

function onPerPageChange() {
  page.value = 1;
  loadActivity();
}

function resetFilters() {
  filters.search = '';
  filters.action = [];
  filters.user_id = null;
  filters.from = null;
  filters.to = null;
  onFilterChange();
}

function isQuickActive(group) {
  const groupActions = ACTION_GROUPS[group] || [];
  if (!filters.action || !filters.action.length) return false;
  return filters.action.length === groupActions.length
    && groupActions.every((a) => filters.action.includes(a));
}

function toggleQuickFilter(group) {
  if (isQuickActive(group)) {
    filters.action = [];
  } else {
    filters.action = [...(ACTION_GROUPS[group] || [])];
  }
  onFilterChange();
}

async function exportCsv() {
  exporting.value = true;
  try {
    const params = buildParams();
    delete params.page;
    delete params.per_page;
    await downloadActivityLogsCsv(params);
    $q.notify({ type: 'positive', message: 'Audit log exported.' });
  } catch (error) {
    console.error('Audit log export failed', error);
    $q.notify({ type: 'negative', message: 'Unable to export audit log.', timeout: 5000 });
  } finally {
    exporting.value = false;
  }
}

async function reload() {
  await Promise.all([loadSummary(), loadActivity()]);
}

onMounted(async () => {
  await Promise.all([loadSummary(), loadUsers(), loadActivity()]);
});

defineExpose({ reload });
</script>

<style scoped>
.audit-page {
  gap: 16px;
}

.audit-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 12px;
}

.audit-stat {
  background: var(--ws-surface);
  border: 1px solid var(--ws-border);
  border-radius: var(--ws-radius);
  padding: 14px 16px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  box-shadow: var(--ws-shadow);
}

.audit-stat--match {
  background: linear-gradient(135deg, #1f3f70 0%, #2f6dc4 100%);
  border-color: transparent;
  color: #fff;
}

.audit-stat__label {
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-weight: 700;
  opacity: 0.78;
}

.audit-stat__value {
  font-size: 1.6rem;
  font-weight: 800;
  letter-spacing: -0.01em;
  line-height: 1;
}

.audit-filters {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 14px 16px;
}

.audit-filters__row {
  display: grid;
  grid-template-columns: minmax(220px, 2fr) repeat(2, minmax(160px, 1fr)) repeat(2, 140px) auto;
  gap: 10px;
  align-items: center;
}

@media (max-width: 1100px) {
  .audit-filters__row {
    grid-template-columns: 1fr 1fr;
  }
  .audit-filter--search {
    grid-column: 1 / -1;
  }
}

.audit-filters__quick {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  align-items: center;
}

.audit-filters__quick-label {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--ws-muted);
  margin-right: 4px;
}

.audit-quick-chip {
  border: 1px solid var(--ws-border);
  border-radius: 999px;
  padding: 0 10px;
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--ws-ink);
  background: #f3f6fb;
}

.audit-quick-chip__count {
  display: inline-block;
  margin-left: 6px;
  font-weight: 700;
  color: var(--ws-blue);
  font-variant-numeric: tabular-nums;
}

.audit-quick-chip--active {
  background: var(--ws-blue);
  color: #fff;
  border-color: var(--ws-blue);
}

.audit-quick-chip--active .audit-quick-chip__count {
  color: #fff;
}

.audit-list-wrap {
  position: relative;
}

.audit-loading {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 36px;
  color: var(--ws-muted);
}

.audit-list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.audit-row {
  border-bottom: 1px solid var(--ws-border);
  background: #fff;
  transition: background 0.15s ease;
}

.audit-row:last-child {
  border-bottom: none;
}

.audit-row:hover {
  background: rgba(47, 109, 196, 0.04);
}

.audit-row--open {
  background: rgba(47, 109, 196, 0.06);
}

.audit-row__header {
  display: grid;
  grid-template-columns: 140px 1fr auto;
  gap: 16px;
  width: 100%;
  text-align: left;
  background: transparent;
  border: 0;
  padding: 12px 16px;
  cursor: pointer;
  align-items: flex-start;
  font: inherit;
  color: inherit;
}

.audit-row__time {
  display: flex;
  flex-direction: column;
  gap: 2px;
  font-variant-numeric: tabular-nums;
}

.audit-row__date {
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--ws-ink);
}

.audit-row__relative {
  font-size: 0.7rem;
  color: var(--ws-muted);
}

.audit-row__main {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.audit-row__description {
  font-size: 0.92rem;
  font-weight: 500;
  color: var(--ws-ink);
  line-height: 1.35;
}

.audit-row__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  font-size: 0.76rem;
  color: var(--ws-muted);
  align-items: center;
}

.audit-row__meta > span {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.audit-role {
  margin-left: 4px;
  text-transform: capitalize;
  opacity: 0.8;
}

.audit-row__user--system {
  font-style: italic;
}

.audit-row__actions {
  display: flex;
  align-items: center;
  gap: 4px;
  color: var(--ws-muted);
}

.audit-row__chev {
  font-size: 1.2rem;
  color: var(--ws-muted);
}

.audit-row__diff {
  padding: 0 16px 16px 172px;
}

.audit-diff {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 12px;
}

.audit-diff__col {
  background: #0f1f3a;
  color: #d6e4ff;
  border-radius: var(--ws-radius-sm);
  padding: 12px;
  overflow: hidden;
}

.audit-diff__label {
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin-bottom: 6px;
}

.audit-diff__label--old { color: #ffb4a8; }
.audit-diff__label--new { color: #9ce0a3; }

.audit-diff pre {
  margin: 0;
  font-size: 0.74rem;
  line-height: 1.45;
  white-space: pre-wrap;
  word-break: break-word;
  font-family: 'JetBrains Mono', 'Fira Code', Consolas, monospace;
  max-height: 320px;
  overflow: auto;
}

/* Action tags — flat, sharp corners, sober palette */
.audit-tag {
  display: inline-flex;
  align-items: center;
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  padding: 3px 8px;
  border-radius: 2px;
  border: 1px solid transparent;
  white-space: nowrap;
  width: fit-content;
}

.audit-tag--create   { background: #e6f0fb; color: #1f4a85; border-color: #b8d2f0; }
.audit-tag--update   { background: #eef0f6; color: #303a55; border-color: #c7cee0; }
.audit-tag--approve  { background: #e3eef9; color: #14467f; border-color: #a9c8e8; }
.audit-tag--remove   { background: #f7e7e3; color: #832a1a; border-color: #e6bdb1; }
.audit-tag--physical { background: #f1ecdf; color: #6b4e1e; border-color: #d8c89c; }
.audit-tag--neutral  { background: #eef0f4; color: #475168; border-color: #d3d8e1; }

.audit-pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  border-top: 1px solid var(--ws-border);
  background: #f7f9fc;
  gap: 12px;
  flex-wrap: wrap;
}

.audit-pagination__info {
  font-size: 0.8rem;
  color: var(--ws-muted);
  font-variant-numeric: tabular-nums;
}

.audit-perpage {
  width: 110px;
}
</style>
