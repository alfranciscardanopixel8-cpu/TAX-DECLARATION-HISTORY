<template>
  <div class="ws-page security-page">
    <WorkspacePageHeader
      module="Administration"
      title="Security & Access"
      lead="Modify user roles and account status. Audit recent sign-in attempts."
    >
      <template #actions>
        <q-btn outline no-caps color="primary" icon="refresh" :loading="loading" @click="loadAll">
          <q-tooltip>Refresh</q-tooltip>
        </q-btn>
      </template>
    </WorkspacePageHeader>

    <!-- Top stats -->
    <div class="security-stats">
      <div class="security-stat">
        <q-icon name="group" size="22px" />
        <div>
          <div class="security-stat__value">{{ formatNumber(matrix.totals.users) }}</div>
          <div class="security-stat__label">Total accounts</div>
        </div>
      </div>
      <div class="security-stat">
        <q-icon name="task_alt" size="22px" color="positive" />
        <div>
          <div class="security-stat__value">{{ formatNumber(matrix.totals.active_users) }}</div>
          <div class="security-stat__label">Active</div>
        </div>
      </div>
      <div class="security-stat">
        <q-icon name="block" size="22px" color="grey-7" />
        <div>
          <div class="security-stat__value">{{ formatNumber(matrix.totals.inactive_users) }}</div>
          <div class="security-stat__label">Inactive</div>
        </div>
      </div>
      <div class="security-stat">
        <q-icon name="admin_panel_settings" size="22px" />
        <div>
          <div class="security-stat__value">{{ formatNumber(matrix.totals.admins) }}</div>
          <div class="security-stat__label">Administrators</div>
        </div>
      </div>
      <div class="security-stat security-stat--accent">
        <q-icon name="login" size="22px" />
        <div>
          <div class="security-stat__value">{{ formatNumber(loginStats.last_24h.success) }}</div>
          <div class="security-stat__label">Logins · 24h</div>
        </div>
      </div>
      <div v-if="failedTotal24h" class="security-stat security-stat--warning">
        <q-icon name="warning" size="22px" />
        <div>
          <div class="security-stat__value">{{ formatNumber(failedTotal24h) }}</div>
          <div class="security-stat__label">Failed / locked · 24h</div>
        </div>
      </div>
    </div>

    <q-tabs
      v-model="tab"
      dense
      no-caps
      align="left"
      indicator-color="primary"
      active-color="primary"
      class="security-tabs"
    >
      <q-tab name="access" icon="manage_accounts" label="Access Manager" />
      <q-tab name="permissions" icon="shield" label="Role Permissions" />
      <q-tab name="login" icon="vpn_key" label="Login Activity" />
    </q-tabs>

    <q-tab-panels v-model="tab" animated keep-alive class="security-tab-panels">
      <!-- Access Manager: inline role/status editing -->
      <q-tab-panel name="access" class="security-tab-panel">
        <div class="ws-card access-card">
          <header class="access-card__header">
            <div>
              <h2 class="access-card__title">User Access Manager</h2>
              <p class="access-card__lead">
                Change a user's role or activate/deactivate their account. Changes take effect on their next request.
              </p>
            </div>
            <q-input
              v-model="userSearch"
              dense
              outlined
              clearable
              placeholder="Search by name, email, or role…"
              class="access-card__search"
            >
              <template #prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </header>

          <div v-if="usersLoading && !users.length" class="access-loading">
            <q-spinner color="primary" /> Loading users…
          </div>

          <div v-else class="access-table-wrap">
            <table class="access-table">
              <thead>
                <tr>
                  <th>User</th>
                  <th>Role</th>
                  <th>Status</th>
                  <th class="access-table__actions-col">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="user in filteredUsers" :key="user.id" :class="{ 'access-table__row--self': isSelf(user) }">
                  <td>
                    <div class="access-user">
                      <div :class="['access-avatar', `access-avatar--${roleTone(user.role)}`]">
                        {{ initials(user.name) }}
                      </div>
                      <div class="access-user__main">
                        <div class="access-user__name">
                          {{ user.name }}
                          <span v-if="isSelf(user)" class="access-user__self-tag">YOU</span>
                        </div>
                        <div class="access-user__email">{{ user.email }}</div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <q-select
                      :model-value="user.role"
                      dense
                      borderless
                      emit-value
                      map-options
                      :options="roleOptions"
                      :disable="isSelf(user) || pendingId === user.id"
                      class="access-table__select"
                      @update:model-value="(value) => onRoleChange(user, value)"
                    >
                      <template #prepend>
                        <span :class="['role-tag', `role-tag--${roleTone(user.role)}`]">{{ roleLabel(user.role) }}</span>
                      </template>
                    </q-select>
                  </td>
                  <td>
                    <span :class="['status-tag', user.status === 'Active' ? 'status-tag--active' : 'status-tag--inactive']">
                      <q-icon :name="user.status === 'Active' ? 'check_circle' : 'radio_button_unchecked'" size="12px" />
                      {{ user.status }}
                    </span>
                  </td>
                  <td class="access-table__actions">
                    <q-btn
                      flat
                      dense
                      no-caps
                      size="sm"
                      icon="tune"
                      :color="user.has_overrides ? 'amber-9' : 'primary'"
                      :label="user.has_overrides ? `Permissions · ${overrideCount(user)}` : 'Permissions'"
                      :disable="pendingId === user.id"
                      @click="openPermissionsEditor(user)"
                    >
                      <q-tooltip v-if="user.has_overrides">
                        {{ user.permission_grants.length }} extra · {{ user.permission_denies.length }} blocked
                      </q-tooltip>
                    </q-btn>
                    <q-btn
                      flat
                      dense
                      no-caps
                      size="sm"
                      :icon="user.status === 'Active' ? 'block' : 'check_circle'"
                      :color="user.status === 'Active' ? 'grey-7' : 'positive'"
                      :label="user.status === 'Active' ? 'Deactivate' : 'Activate'"
                      :loading="pendingId === user.id"
                      :disable="isSelf(user)"
                      @click="toggleStatus(user)"
                    />
                    <q-btn
                      flat
                      dense
                      no-caps
                      size="sm"
                      icon="lock_reset"
                      color="primary"
                      label="Reset password"
                      :disable="pendingId === user.id"
                      @click="openPasswordReset(user)"
                    />
                  </td>
                </tr>
                <tr v-if="!filteredUsers.length">
                  <td colspan="4" class="access-table__empty">No users match "{{ userSearch }}".</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </q-tab-panel>

      <!-- Role Permissions: read-only catalog -->
      <q-tab-panel name="permissions" class="security-tab-panel">
        <div class="role-cards">
          <article
            v-for="role in matrix.roles"
            :key="role.value"
            :class="['role-card', `role-card--${role.tone}`]"
          >
            <header class="role-card__head">
              <div class="role-card__title">
                <span class="role-card__icon">
                  <q-icon :name="roleIcon(role.value)" size="20px" />
                </span>
                <div>
                  <div class="role-card__label">{{ role.label }}</div>
                  <div class="role-card__role-key">{{ role.value }}</div>
                </div>
              </div>
              <div class="role-card__counts">
                <span class="role-card__count">
                  <strong>{{ role.user_counts.active }}</strong> active
                </span>
                <span class="role-card__count role-card__count--muted">
                  {{ role.user_counts.inactive }} inactive
                </span>
              </div>
            </header>
            <p class="role-card__desc">{{ role.description }}</p>
          </article>
        </div>

        <div class="ws-card permission-matrix">
          <header class="permission-matrix__header">
            <div>
              <h2 class="permission-matrix__title">Permission Matrix</h2>
              <p class="permission-matrix__lead">
                Read-only reference. To change a user's effective access, use the Access Manager tab.
              </p>
            </div>
            <q-input
              v-model="permissionSearch"
              dense
              outlined
              clearable
              placeholder="Filter permissions…"
              class="permission-matrix__search"
            >
              <template #prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </header>

          <div class="permission-table-wrap">
            <table class="permission-table">
              <thead>
                <tr>
                  <th class="permission-table__col-perm">Permission</th>
                  <th
                    v-for="role in matrix.roles"
                    :key="role.value"
                    :class="['permission-table__col-role', `permission-table__col-role--${role.tone}`]"
                  >
                    <div class="permission-table__role-label">{{ role.label }}</div>
                    <div class="permission-table__role-sub">{{ role.user_counts.active }} active</div>
                  </th>
                </tr>
              </thead>
              <tbody>
                <template v-for="group in filteredGroups" :key="group.group">
                  <tr class="permission-table__group">
                    <td :colspan="matrix.roles.length + 1">{{ group.group }}</td>
                  </tr>
                  <tr v-for="perm in group.permissions" :key="perm.key" class="permission-table__row">
                    <td>
                      <div class="permission-table__perm-label">{{ perm.label }}</div>
                      <div class="permission-table__perm-key">{{ perm.key }}</div>
                    </td>
                    <td v-for="role in matrix.roles" :key="role.value" class="permission-table__cell">
                      <q-icon
                        v-if="perm.roles.includes(role.value)"
                        name="check"
                        size="18px"
                        :class="['permission-check', `permission-check--${role.tone}`]"
                      />
                      <span v-else class="permission-dash">—</span>
                    </td>
                  </tr>
                </template>
                <tr v-if="!filteredGroups.length">
                  <td :colspan="matrix.roles.length + 1" class="permission-table__empty">
                    No permissions match "{{ permissionSearch }}".
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </q-tab-panel>

      <!-- Login Activity -->
      <q-tab-panel name="login" class="security-tab-panel">
        <div class="login-grid">
          <section class="ws-card login-summary">
            <h3 class="login-summary__title">Login Trends · Last 7 Days</h3>
            <div class="login-summary__row">
              <div class="login-summary__metric">
                <span class="login-summary__metric-label">Successful</span>
                <span class="login-summary__metric-value">{{ formatNumber(loginStats.last_7d.success) }}</span>
              </div>
              <div class="login-summary__metric">
                <span class="login-summary__metric-label">Failed</span>
                <span class="login-summary__metric-value login-summary__metric-value--warn">
                  {{ formatNumber(loginStats.last_7d.failed) }}
                </span>
              </div>
              <div class="login-summary__metric">
                <span class="login-summary__metric-label">Rate-limited</span>
                <span class="login-summary__metric-value login-summary__metric-value--alert">
                  {{ formatNumber(loginStats.last_7d.locked) }}
                </span>
              </div>
            </div>

            <div v-if="loginStats.top_failures.length" class="login-summary__failures">
              <h4>Top failed-login emails (7 days)</h4>
              <ul>
                <li v-for="failure in loginStats.top_failures" :key="failure.email">
                  <span class="login-summary__email">{{ failure.email }}</span>
                  <span class="login-summary__count">{{ failure.total }}</span>
                </li>
              </ul>
            </div>
            <div v-else class="login-summary__no-failures">
              <q-icon name="check_circle" color="positive" /> No failed logins in the last 7 days.
            </div>
          </section>

          <section class="ws-card ws-card--flush login-list-wrap">
            <header class="login-list__header">
              <h3>Recent Login Attempts</h3>
              <div class="login-list__filters">
                <q-input
                  v-model="loginFilters.email"
                  dense
                  outlined
                  clearable
                  placeholder="Filter by email"
                  debounce="350"
                  class="login-filter"
                  @update:model-value="loadLogins"
                />
                <q-select
                  v-model="loginFilters.status"
                  dense
                  outlined
                  clearable
                  emit-value
                  map-options
                  :options="statusOptions"
                  label="Status"
                  class="login-filter"
                  @update:model-value="loadLogins"
                />
              </div>
            </header>

            <div v-if="loginsLoading && !logins.length" class="login-list__loading">
              <q-spinner color="primary" /> Loading login activity…
            </div>
            <div v-else-if="!logins.length" class="ws-empty ws-empty--compact">
              <q-icon name="vpn_key" size="36px" />
              <strong>No login attempts yet</strong>
              <span>Sign-in attempts and rate-limit events will appear here.</span>
            </div>
            <ul v-else class="login-list">
              <li v-for="log in logins" :key="log.id" class="login-row">
                <div class="login-row__main">
                  <span :class="['login-tag', `login-tag--${log.status}`]">{{ statusLabel(log.status) }}</span>
                  <div class="login-row__email">{{ log.email }}</div>
                  <div class="login-row__sub">
                    <span v-if="log.user">{{ log.user.name }} · {{ log.user.role }}</span>
                    <span v-else class="login-row__unknown">Unknown account</span>
                    <span v-if="log.reason" class="login-row__reason">· {{ log.reason }}</span>
                  </div>
                </div>
                <div class="login-row__meta">
                  <span class="login-row__time">{{ formatDate(log.attempted_at) }}</span>
                  <span class="login-row__ip">{{ log.ip_address || '—' }}</span>
                </div>
              </li>
            </ul>
          </section>
        </div>
      </q-tab-panel>
    </q-tab-panels>

    <!-- Per-user permission editor -->
    <q-dialog v-model="permissionsDialog" persistent>
      <q-card class="ws-dialog-card permissions-dialog">
        <q-card-section class="ws-dialog-card__head row items-center justify-between">
          <div>
            <div class="text-h6">Modify Permissions</div>
            <div class="permissions-dialog__sub" v-if="permissionsTarget">
              {{ permissionsTarget.name }} · <span class="permissions-dialog__role">{{ roleLabel(permissionsTarget.role) }}</span>
            </div>
          </div>
          <q-btn flat round icon="close" color="white" v-close-popup />
        </q-card-section>
        <q-separator />

        <q-card-section class="permissions-dialog__body">
          <div class="permissions-summary">
            <div class="permissions-summary__pill permissions-summary__pill--default">
              <q-icon name="check_circle" size="14px" />
              <span><strong>{{ defaultsCount }}</strong> from role</span>
            </div>
            <div v-if="grantsCount" class="permissions-summary__pill permissions-summary__pill--grant">
              <q-icon name="add_circle" size="14px" />
              <span><strong>+{{ grantsCount }}</strong> extra</span>
            </div>
            <div v-if="deniesCount" class="permissions-summary__pill permissions-summary__pill--deny">
              <q-icon name="remove_circle" size="14px" />
              <span><strong>−{{ deniesCount }}</strong> blocked</span>
            </div>
            <div class="permissions-summary__effective">
              Effective: <strong>{{ effectiveSet.length }}</strong> permission{{ effectiveSet.length === 1 ? '' : 's' }}
            </div>
          </div>

          <div class="permissions-help">
            <q-icon name="info" size="16px" />
            <span>
              Toggle a feature to grant or block it for this user. Defaults come from the role.
              Click "Reset to role defaults" to clear all overrides.
            </span>
          </div>

          <div class="permissions-list">
            <div v-for="group in permissionGroups" :key="group.group" class="permissions-group">
              <div class="permissions-group__header">
                <span>{{ group.group }}</span>
                <span class="permissions-group__count">{{ groupGrantedCount(group) }} / {{ group.permissions.length }}</span>
              </div>
              <div class="permissions-group__items">
                <div
                  v-for="perm in group.permissions"
                  :key="perm.key"
                  :class="['perm-row', `perm-row--${permState(perm.key)}`, { 'perm-row--locked': isProtected(perm.key) }]"
                >
                  <div class="perm-row__info">
                    <div class="perm-row__label">
                      {{ perm.label }}
                      <q-icon v-if="isProtected(perm.key)" name="lock" size="14px" class="perm-row__lock-icon">
                        <q-tooltip>Cannot be changed for your own account.</q-tooltip>
                      </q-icon>
                    </div>
                    <div class="perm-row__meta">
                      <code class="perm-row__key">{{ perm.key }}</code>
                      <span v-if="perm.roles.includes(permissionsTarget?.role)" class="perm-row__badge perm-row__badge--default">DEFAULT</span>
                      <span v-else class="perm-row__badge perm-row__badge--off">NOT IN ROLE</span>
                    </div>
                  </div>

                  <div class="perm-row__controls">
                    <q-btn-toggle
                      :model-value="permState(perm.key)"
                      :options="stateOptionsFor(perm)"
                      :disable="isProtected(perm.key)"
                      no-caps
                      dense
                      unelevated
                      spread
                      toggle-color="primary"
                      :class="['perm-toggle', `perm-toggle--${permState(perm.key)}`]"
                      @update:model-value="(state) => setPermState(perm, state)"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </q-card-section>

        <q-separator />
        <q-card-section class="permissions-dialog__footer">
          <q-btn
            flat
            no-caps
            color="grey-8"
            icon="restart_alt"
            label="Reset to role defaults"
            :disable="!grantsCount && !deniesCount"
            @click="resetOverrides"
          />
          <q-space />
          <q-btn flat no-caps label="Cancel" v-close-popup />
          <q-btn
            unelevated
            no-caps
            color="primary"
            icon="save"
            label="Save permissions"
            :loading="permissionsSaving"
            @click="savePermissions"
          />
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Password reset dialog -->
    <q-dialog v-model="passwordDialog" persistent>
      <q-card class="ws-dialog-card">
        <q-card-section class="ws-dialog-card__head row items-center justify-between">
          <div class="text-h6">Reset Password</div>
          <q-btn flat round icon="close" color="white" v-close-popup />
        </q-card-section>
        <q-separator />
        <q-card-section>
          <q-form class="ws-form-grid" @submit.prevent="submitPasswordReset">
            <div class="reset-target">
              Reset password for <strong>{{ passwordTarget?.name }}</strong>
              <span class="reset-target__email">{{ passwordTarget?.email }}</span>
            </div>
            <q-input
              v-model="newPassword"
              outlined
              dense
              label="New password"
              :type="showPassword ? 'text' : 'password'"
              :rules="[v => (v && v.length >= 8) || 'At least 8 characters']"
              autofocus
            >
              <template #append>
                <q-icon :name="showPassword ? 'visibility_off' : 'visibility'" class="cursor-pointer" @click="showPassword = !showPassword" />
              </template>
            </q-input>
            <div class="ws-form-grid__actions row justify-end q-gutter-sm">
              <q-btn flat label="Cancel" v-close-popup />
              <q-btn color="primary" icon="lock_reset" label="Reset password" type="submit" :loading="passwordSaving" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useQuasar } from 'quasar';
import {
  fetchSecurityMatrix,
  fetchLoginStats,
  fetchLoginActivity,
  fetchUsers,
  updateUser,
  updateUserPermissions,
  resetUserPermissions
} from '../../services/api';
import { useAuth } from '../../composables/useAuth';
import WorkspacePageHeader from '../../components/layout/WorkspacePageHeader.vue';

const $q = useQuasar();
const { sessionUser } = useAuth();

const tab = ref('access');
const loading = ref(false);
const usersLoading = ref(false);
const loginsLoading = ref(false);
const pendingId = ref(null);

const matrix = ref({
  roles: [],
  permission_groups: [],
  totals: { users: 0, active_users: 0, inactive_users: 0, admins: 0 }
});

const loginStats = ref({
  last_24h: { success: 0, failed: 0, locked: 0 },
  last_7d: { success: 0, failed: 0, locked: 0 },
  top_failures: []
});

const users = ref([]);
const logins = ref([]);
const userSearch = ref('');
const permissionSearch = ref('');
const loginFilters = reactive({ email: '', status: null });

const passwordDialog = ref(false);
const passwordTarget = ref(null);
const newPassword = ref('');
const showPassword = ref(false);
const passwordSaving = ref(false);

// Per-user permission editor state
const permissionsDialog = ref(false);
const permissionsTarget = ref(null);
const permissionsSaving = ref(false);
const editGrants = ref([]); // permission keys explicitly granted on top of role defaults
const editDenies = ref([]); // permission keys explicitly denied that role would grant

const PROTECTED_FOR_SELF = ['user.update', 'user.permissions', 'security.manage'];

const roleOptions = [
  { label: 'Administrator', value: 'admin' },
  { label: 'Assessor', value: 'assessor' },
  { label: 'Records Staff', value: 'records_staff' },
  { label: 'Viewer', value: 'viewer' }
];

const statusOptions = [
  { value: 'success', label: 'Success' },
  { value: 'failed', label: 'Failed' },
  { value: 'locked', label: 'Rate-limited' }
];

const ROLE_TONE = { admin: 'navy', assessor: 'blue', records_staff: 'steel', viewer: 'slate' };

const failedTotal24h = computed(() => loginStats.value.last_24h.failed + loginStats.value.last_24h.locked);

const filteredUsers = computed(() => {
  const keyword = (userSearch.value || '').trim().toLowerCase();
  if (!keyword) return users.value;
  return users.value.filter((user) => {
    return (user.name || '').toLowerCase().includes(keyword)
      || (user.email || '').toLowerCase().includes(keyword)
      || (user.role || '').toLowerCase().includes(keyword)
      || roleLabel(user.role).toLowerCase().includes(keyword);
  });
});

const filteredGroups = computed(() => {
  const keyword = (permissionSearch.value || '').trim().toLowerCase();
  if (!keyword) return matrix.value.permission_groups;

  return matrix.value.permission_groups
    .map((group) => ({
      ...group,
      permissions: group.permissions.filter((perm) => {
        return perm.label.toLowerCase().includes(keyword)
          || perm.key.toLowerCase().includes(keyword)
          || group.group.toLowerCase().includes(keyword);
      })
    }))
    .filter((group) => group.permissions.length > 0);
});

function formatNumber(value) {
  return new Intl.NumberFormat('en-PH').format(Number(value || 0));
}

function formatDate(value) {
  if (!value) return '';
  return new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}

function roleLabel(role) {
  return roleOptions.find((option) => option.value === role)?.label || role;
}

function roleTone(role) {
  return ROLE_TONE[role] || 'slate';
}

function roleIcon(role) {
  return {
    admin: 'verified_user',
    assessor: 'gavel',
    records_staff: 'edit_note',
    viewer: 'visibility'
  }[role] || 'badge';
}

function statusLabel(status) {
  return statusOptions.find((option) => option.value === status)?.label || status;
}

function initials(name) {
  if (!name) return '·';
  const parts = name.trim().split(/\s+/);
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

function isSelf(user) {
  return Boolean(sessionUser.value && sessionUser.value.id === user.id);
}

function showError(error, fallback) {
  console.error(fallback, error);
  const errors = error.response?.data?.errors;
  const message = errors ? Object.values(errors).flat()[0] : (error.response?.data?.message || fallback);
  $q.notify({ type: 'negative', message, timeout: 5000 });
}

function showSuccess(message) {
  $q.notify({ type: 'positive', message, timeout: 3000 });
}

async function loadMatrix() {
  try {
    matrix.value = await fetchSecurityMatrix();
  } catch (error) {
    showError(error, 'Unable to load security matrix.');
  }
}

async function loadStats() {
  try {
    loginStats.value = await fetchLoginStats();
  } catch {
    // non-blocking
  }
}

async function loadUsers() {
  usersLoading.value = true;
  try {
    users.value = await fetchUsers();
  } catch (error) {
    showError(error, 'Unable to load user accounts.');
  } finally {
    usersLoading.value = false;
  }
}

async function loadLogins() {
  loginsLoading.value = true;
  try {
    const params = {};
    if (loginFilters.email) params.email = loginFilters.email;
    if (loginFilters.status) params.status = loginFilters.status;
    logins.value = await fetchLoginActivity(params);
  } catch (error) {
    showError(error, 'Unable to load login activity.');
  } finally {
    loginsLoading.value = false;
  }
}

async function loadAll() {
  loading.value = true;
  try {
    await Promise.all([loadMatrix(), loadStats(), loadUsers(), loadLogins()]);
  } finally {
    loading.value = false;
  }
}

function confirmAction({ title, message, detail, type = 'warning' }) {
  return new Promise((resolve) => {
    $q.dialog({
      title,
      message: detail ? `${message}<br><span style="color:#6b7280;font-size:0.85rem">${detail}</span>` : message,
      html: true,
      ok: { label: 'Confirm', color: type === 'danger' ? 'negative' : 'primary', unelevated: true, noCaps: true },
      cancel: { label: 'Cancel', flat: true, noCaps: true },
      persistent: true
    }).onOk(() => resolve(true)).onCancel(() => resolve(false)).onDismiss(() => resolve(false));
  });
}

async function onRoleChange(user, newRole) {
  if (newRole === user.role) return;
  if (isSelf(user)) {
    $q.notify({ type: 'warning', message: 'You cannot change your own role.', timeout: 4000 });
    return;
  }

  if (user.role === 'admin' && matrix.value.totals.admins <= 1) {
    $q.notify({ type: 'warning', message: 'Cannot demote the last administrator.', timeout: 4000 });
    return;
  }

  const confirmed = await confirmAction({
    title: 'Change role',
    message: `Change <strong>${user.name}</strong>'s role to <strong>${roleLabel(newRole)}</strong>?`,
    detail: 'Their permissions update immediately on next request.'
  });

  if (!confirmed) return;

  pendingId.value = user.id;
  try {
    await updateUser(user.id, { role: newRole });
    user.role = newRole;
    showSuccess(`Role updated to ${roleLabel(newRole)}.`);
    await Promise.all([loadMatrix()]);
  } catch (error) {
    showError(error, 'Unable to update role.');
  } finally {
    pendingId.value = null;
  }
}

async function toggleStatus(user) {
  if (isSelf(user)) return;
  const next = user.status === 'Active' ? 'Inactive' : 'Active';
  const action = next === 'Active' ? 'Activate' : 'Deactivate';

  const confirmed = await confirmAction({
    title: `${action} account`,
    message: `${action} <strong>${user.name}</strong>?`,
    detail: next === 'Inactive' ? 'They will be unable to sign in until reactivated.' : 'They will regain sign-in access.',
    type: next === 'Inactive' ? 'warning' : 'info'
  });

  if (!confirmed) return;

  pendingId.value = user.id;
  try {
    await updateUser(user.id, { status: next });
    user.status = next;
    showSuccess(`Account ${next === 'Active' ? 'activated' : 'deactivated'}.`);
    await loadMatrix();
  } catch (error) {
    showError(error, 'Unable to update account status.');
  } finally {
    pendingId.value = null;
  }
}

function openPasswordReset(user) {
  passwordTarget.value = user;
  newPassword.value = '';
  showPassword.value = false;
  passwordDialog.value = true;
}

// ===== Per-user permission editor =====

const permissionGroups = computed(() => matrix.value.permission_groups || []);

const defaultsCount = computed(() => permissionsTarget.value?.role_defaults?.length || 0);
const grantsCount = computed(() => editGrants.value.length);
const deniesCount = computed(() => editDenies.value.length);

const effectiveSet = computed(() => {
  if (!permissionsTarget.value) return [];
  const defaults = permissionsTarget.value.role_defaults || [];
  const merged = new Set([...defaults, ...editGrants.value]);
  editDenies.value.forEach((key) => merged.delete(key));
  return Array.from(merged);
});

function overrideCount(user) {
  return (user.permission_grants?.length || 0) + (user.permission_denies?.length || 0);
}

function permState(key) {
  if (editGrants.value.includes(key)) return 'granted';
  if (editDenies.value.includes(key)) return 'denied';
  if ((permissionsTarget.value?.role_defaults || []).includes(key)) return 'default';
  return 'off';
}

function stateLabel(state) {
  return {
    granted: 'GRANTED',
    denied: 'BLOCKED',
    default: 'ALLOWED',
    off: 'OFF'
  }[state] || state.toUpperCase();
}

function isProtected(key) {
  if (!permissionsTarget.value || !sessionUser.value) return false;
  if (permissionsTarget.value.id !== sessionUser.value.id) return false;
  return PROTECTED_FOR_SELF.includes(key);
}

function openPermissionsEditor(user) {
  permissionsTarget.value = user;
  editGrants.value = [...(user.permission_grants || [])];
  editDenies.value = [...(user.permission_denies || [])];
  permissionsDialog.value = true;
}

function togglePermission(perm, value) {
  if (!permissionsTarget.value) return;
  const key = perm.key;
  const role = permissionsTarget.value.role;
  const isDefault = perm.roles.includes(role);

  // Always clear any existing override for this key first.
  editGrants.value = editGrants.value.filter((k) => k !== key);
  editDenies.value = editDenies.value.filter((k) => k !== key);

  if (value && !isDefault) {
    // Turning ON something the role doesn't grant — add an explicit grant.
    editGrants.value = [...editGrants.value, key];
  } else if (!value && isDefault) {
    // Turning OFF something the role does grant — add an explicit deny.
    editDenies.value = [...editDenies.value, key];
  }
  // value === isDefault means we're back to defaults, no override needed.
}

/**
 * Return the three-way option set for a single permission row.
 * The middle option label depends on whether the role grants it by default.
 */
function stateOptionsFor(perm) {
  if (!permissionsTarget.value) return [];
  const isDefault = perm.roles.includes(permissionsTarget.value.role);

  return [
    { value: 'denied', label: 'Block', icon: 'block' },
    isDefault
      ? { value: 'default', label: 'Allow', icon: 'check' }
      : { value: 'off', label: 'Off', icon: 'remove' },
    { value: 'granted', label: 'Grant', icon: 'add' }
  ];
}

/**
 * Apply a new state for a permission. Maps the three-way choice to grants/denies.
 */
function setPermState(perm, state) {
  if (!permissionsTarget.value || isProtected(perm.key)) return;
  const key = perm.key;

  // Clear any existing override first.
  editGrants.value = editGrants.value.filter((k) => k !== key);
  editDenies.value = editDenies.value.filter((k) => k !== key);

  if (state === 'granted') {
    editGrants.value = [...editGrants.value, key];
  } else if (state === 'denied') {
    editDenies.value = [...editDenies.value, key];
  }
  // 'default' or 'off' means no override.
}

function groupGrantedCount(group) {
  return group.permissions.filter((perm) => effectiveSet.value.includes(perm.key)).length;
}

function resetOverrides() {
  editGrants.value = [];
  editDenies.value = [];
}

async function savePermissions() {
  if (!permissionsTarget.value) return;

  permissionsSaving.value = true;
  try {
    const updated = await updateUserPermissions(permissionsTarget.value.id, {
      grants: editGrants.value,
      denies: editDenies.value
    });

    // Refresh local user list with the saved record.
    const idx = users.value.findIndex((u) => u.id === updated.id);
    if (idx >= 0) {
      users.value.splice(idx, 1, updated);
    }

    permissionsDialog.value = false;
    showSuccess(`Permissions updated for ${updated.name}.`);
  } catch (error) {
    showError(error, 'Unable to save permission overrides.');
  } finally {
    permissionsSaving.value = false;
  }
}

async function submitPasswordReset() {
  if (!passwordTarget.value) return;
  if (!newPassword.value || newPassword.value.length < 8) {
    $q.notify({ type: 'negative', message: 'Password must be at least 8 characters.', timeout: 4000 });
    return;
  }

  passwordSaving.value = true;
  try {
    await updateUser(passwordTarget.value.id, { password: newPassword.value });
    showSuccess(`Password reset for ${passwordTarget.value.name}.`);
    passwordDialog.value = false;
  } catch (error) {
    showError(error, 'Unable to reset password.');
  } finally {
    passwordSaving.value = false;
  }
}

onMounted(loadAll);
</script>

<style scoped>
.security-page {
  gap: 16px;
}

/* Stat strip */
.security-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 12px;
}

.security-stat {
  background: var(--ws-surface);
  border: 1px solid var(--ws-border);
  border-radius: var(--ws-radius);
  padding: 14px 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: var(--ws-shadow);
}

.security-stat__value {
  font-size: 1.4rem;
  font-weight: 800;
  line-height: 1;
  color: var(--ws-ink);
}

.security-stat__label {
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--ws-muted);
  font-weight: 700;
  margin-top: 4px;
}

.security-stat--accent {
  background: linear-gradient(135deg, #1f3f70 0%, #2f6dc4 100%);
  color: #fff;
  border-color: transparent;
}
.security-stat--accent .security-stat__value,
.security-stat--accent .security-stat__label,
.security-stat--accent .q-icon { color: #fff; }
.security-stat--accent .security-stat__label { opacity: 0.85; }

.security-stat--warning {
  background: #f7e7e3;
  border-color: #e6bdb1;
  color: #832a1a;
}
.security-stat--warning .security-stat__value,
.security-stat--warning .security-stat__label { color: #832a1a; }

/* Tabs */
.security-tabs {
  border-bottom: 1px solid var(--ws-border);
}

.security-tab-panels {
  background: transparent;
}

.security-tab-panel {
  padding: 16px 0 0 0;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Access Manager */
.access-card {
  padding: 16px;
}

.access-card__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 16px;
  margin-bottom: 14px;
  flex-wrap: wrap;
}

.access-card__title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--ws-blue);
}

.access-card__lead {
  margin: 4px 0 0;
  font-size: 0.82rem;
  color: var(--ws-muted);
}

.access-card__search {
  width: 280px;
  max-width: 100%;
}

.access-loading {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 24px;
  color: var(--ws-muted);
}

.access-table-wrap {
  border: 1px solid var(--ws-border);
  border-radius: var(--ws-radius-sm);
  overflow-x: auto;
}

.access-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.88rem;
}

.access-table th {
  padding: 10px 14px;
  text-align: left;
  background: #f3f6fb;
  border-bottom: 2px solid var(--ws-border);
  font-weight: 700;
  font-size: 0.72rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--ws-ink);
}

.access-table td {
  padding: 10px 14px;
  border-top: 1px solid var(--ws-border);
  vertical-align: middle;
}

.access-table tbody tr:hover td {
  background: rgba(47, 109, 196, 0.04);
}

.access-table__row--self td {
  background: rgba(255, 200, 87, 0.08);
}

.access-table__row--self:hover td {
  background: rgba(255, 200, 87, 0.12);
}

.access-table__actions-col {
  width: 1%;
  white-space: nowrap;
  text-align: right;
}

.access-table__actions {
  text-align: right;
  white-space: nowrap;
}

.access-table__select {
  min-width: 200px;
}

.access-table__empty {
  text-align: center;
  color: var(--ws-muted);
  padding: 32px 16px;
}

.access-user {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.access-avatar {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 0.78rem;
  color: #fff;
  flex-shrink: 0;
}

.access-avatar--navy   { background: #14467f; }
.access-avatar--blue   { background: #2f6dc4; }
.access-avatar--steel  { background: #4a6582; }
.access-avatar--slate  { background: #6b7892; }

.access-user__name {
  font-weight: 700;
  color: var(--ws-ink);
  display: flex;
  align-items: center;
  gap: 6px;
}

.access-user__email {
  font-size: 0.78rem;
  color: var(--ws-muted);
}

.access-user__self-tag {
  background: #fde68a;
  color: #92400e;
  font-size: 0.62rem;
  font-weight: 800;
  letter-spacing: 0.05em;
  padding: 2px 6px;
  border-radius: 2px;
}

/* Role tag prefixed inside the role select */
.role-tag {
  display: inline-flex;
  align-items: center;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  padding: 3px 8px;
  border-radius: 2px;
  border: 1px solid transparent;
  white-space: nowrap;
}

.role-tag--navy   { background: #e6f0fb; color: #14467f; border-color: #b8d2f0; }
.role-tag--blue   { background: #e3eef9; color: #2f6dc4; border-color: #a9c8e8; }
.role-tag--steel  { background: #eef0f6; color: #4a6582; border-color: #c7cee0; }
.role-tag--slate  { background: #eef0f4; color: #6b7892; border-color: #d3d8e1; }

/* Status tag */
.status-tag {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  padding: 3px 8px;
  border-radius: 2px;
  border: 1px solid transparent;
}

.status-tag--active   { background: #e6f0fb; color: #14467f; border-color: #b8d2f0; }
.status-tag--inactive { background: #eef0f4; color: #6b7892; border-color: #d3d8e1; }

/* Role cards (permissions tab) */
.role-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 12px;
}

.role-card {
  background: var(--ws-surface);
  border: 1px solid var(--ws-border);
  border-left: 4px solid #2f6dc4;
  border-radius: var(--ws-radius);
  padding: 14px 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  box-shadow: var(--ws-shadow);
}

.role-card--navy   { border-left-color: #14467f; }
.role-card--blue   { border-left-color: #2f6dc4; }
.role-card--steel  { border-left-color: #4a6582; }
.role-card--slate  { border-left-color: #6b7892; }

.role-card__head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
}

.role-card__title {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.role-card__icon {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: rgba(47, 109, 196, 0.12);
  color: #14467f;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.role-card__label {
  font-size: 1rem;
  font-weight: 800;
  color: var(--ws-ink);
}

.role-card__role-key {
  font-size: 0.72rem;
  color: var(--ws-muted);
  font-family: 'JetBrains Mono', Consolas, monospace;
}

.role-card__counts {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 2px;
  font-size: 0.72rem;
  color: var(--ws-muted);
}

.role-card__count strong {
  color: var(--ws-ink);
  font-size: 1rem;
  margin-right: 4px;
}

.role-card__count--muted { opacity: 0.7; }

.role-card__desc {
  margin: 0;
  font-size: 0.85rem;
  color: var(--ws-muted);
  line-height: 1.4;
}

/* Permission matrix */
.permission-matrix {
  padding: 16px;
}

.permission-matrix__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 16px;
  margin-bottom: 14px;
  flex-wrap: wrap;
}

.permission-matrix__title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--ws-blue);
}

.permission-matrix__lead {
  margin: 4px 0 0;
  font-size: 0.82rem;
  color: var(--ws-muted);
}

.permission-matrix__search {
  width: 260px;
  max-width: 100%;
}

.permission-table-wrap {
  overflow-x: auto;
  border: 1px solid var(--ws-border);
  border-radius: var(--ws-radius-sm);
}

.permission-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
}

.permission-table th,
.permission-table td {
  padding: 10px 14px;
  text-align: left;
  vertical-align: middle;
}

.permission-table thead th {
  background: #f3f6fb;
  border-bottom: 2px solid var(--ws-border);
  font-weight: 700;
  font-size: 0.78rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--ws-ink);
}

.permission-table__col-role {
  text-align: center;
  width: 130px;
}

.permission-table__role-label { font-weight: 800; }
.permission-table__role-sub {
  font-size: 0.7rem;
  font-weight: 600;
  color: var(--ws-muted);
  text-transform: none;
  letter-spacing: 0;
}

.permission-table__group td {
  background: #eaeff7;
  font-weight: 700;
  text-transform: uppercase;
  font-size: 0.72rem;
  letter-spacing: 0.08em;
  color: #14467f;
  padding: 8px 14px;
}

.permission-table__row td { border-top: 1px solid var(--ws-border); }
.permission-table__row:hover td { background: rgba(47, 109, 196, 0.04); }

.permission-table__cell { text-align: center; }

.permission-table__perm-label {
  font-weight: 600;
  color: var(--ws-ink);
}

.permission-table__perm-key {
  font-family: 'JetBrains Mono', Consolas, monospace;
  font-size: 0.7rem;
  color: var(--ws-muted);
  margin-top: 2px;
}

.permission-check { color: #14467f; }
.permission-check--navy   { color: #14467f; }
.permission-check--blue   { color: #2f6dc4; }
.permission-check--steel  { color: #4a6582; }
.permission-check--slate  { color: #6b7892; }
.permission-dash { color: #c5cdda; font-weight: 700; }

.permission-table__empty {
  text-align: center;
  color: var(--ws-muted);
  padding: 32px 16px;
}

/* Login tab */
.login-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
}

@media (min-width: 1100px) {
  .login-grid {
    grid-template-columns: minmax(280px, 360px) 1fr;
  }
}

.login-summary {
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.login-summary__title {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 800;
  color: var(--ws-blue);
}

.login-summary__row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}

.login-summary__metric {
  background: #f3f6fb;
  border: 1px solid var(--ws-border);
  border-radius: var(--ws-radius-sm);
  padding: 10px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  align-items: flex-start;
}

.login-summary__metric-label {
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--ws-muted);
  font-weight: 700;
}

.login-summary__metric-value {
  font-size: 1.4rem;
  font-weight: 800;
  color: var(--ws-ink);
}

.login-summary__metric-value--warn { color: #b85f29; }
.login-summary__metric-value--alert { color: #832a1a; }

.login-summary__failures h4 {
  margin: 8px 0 6px;
  font-size: 0.78rem;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--ws-muted);
  font-weight: 700;
}

.login-summary__failures ul {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.login-summary__failures li {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
  padding: 6px 8px;
  background: #fff;
  border: 1px solid var(--ws-border);
  border-radius: var(--ws-radius-sm);
}

.login-summary__email {
  font-family: 'JetBrains Mono', Consolas, monospace;
  font-size: 0.78rem;
  color: var(--ws-ink);
}

.login-summary__count {
  font-weight: 800;
  color: #832a1a;
  font-variant-numeric: tabular-nums;
}

.login-summary__no-failures {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.85rem;
  color: var(--ws-muted);
  padding: 8px;
}

.login-list-wrap { overflow: hidden; }

.login-list__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  border-bottom: 1px solid var(--ws-border);
  flex-wrap: wrap;
}

.login-list__header h3 {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 800;
  color: var(--ws-blue);
}

.login-list__filters { display: flex; gap: 8px; }
.login-filter { width: 180px; }

.login-list__loading {
  padding: 24px;
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--ws-muted);
}

.login-list {
  list-style: none;
  margin: 0;
  padding: 0;
  max-height: 540px;
  overflow-y: auto;
}

.login-row {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 16px;
  padding: 12px 16px;
  border-bottom: 1px solid var(--ws-border);
  align-items: center;
}

.login-row:last-child { border-bottom: none; }
.login-row:hover { background: rgba(47, 109, 196, 0.04); }

.login-row__main {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.login-row__email {
  font-weight: 600;
  color: var(--ws-ink);
  font-size: 0.9rem;
}

.login-row__sub {
  font-size: 0.76rem;
  color: var(--ws-muted);
}

.login-row__unknown { font-style: italic; }
.login-row__reason { margin-left: 4px; }

.login-row__meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 2px;
  font-size: 0.74rem;
  color: var(--ws-muted);
  font-variant-numeric: tabular-nums;
  text-align: right;
}

.login-row__ip { font-family: 'JetBrains Mono', Consolas, monospace; }

.login-tag {
  display: inline-flex;
  align-items: center;
  font-size: 0.66rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  padding: 3px 8px;
  border-radius: 2px;
  border: 1px solid transparent;
  width: fit-content;
}

.login-tag--success { background: #e6f0fb; color: #14467f; border-color: #b8d2f0; }
.login-tag--failed  { background: #f7e7e3; color: #832a1a; border-color: #e6bdb1; }
.login-tag--locked  { background: #f1ecdf; color: #6b4e1e; border-color: #d8c89c; }

/* Password reset dialog */
.ws-dialog-card {
  border-radius: 22px;
  width: min(480px, 96vw);
  overflow: hidden;
  box-shadow: 0 28px 60px rgba(17, 39, 72, 0.22);
}

.ws-dialog-card__head {
  background: linear-gradient(90deg, #183154 0%, #245ea8 54%, #2f76d4 100%);
  color: #fff;
  padding: 18px 24px;
}

.ws-dialog-card__head .text-h6 {
  font-weight: 800;
}

.ws-form-grid {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.ws-form-grid__actions {
  padding-top: 8px;
}

.reset-target {
  font-size: 0.9rem;
  color: var(--ws-ink);
  background: #f3f6fb;
  padding: 12px;
  border-radius: var(--ws-radius-sm);
  border: 1px solid var(--ws-border);
}

.reset-target__email {
  display: block;
  font-size: 0.78rem;
  color: var(--ws-muted);
  margin-top: 2px;
}

/* Permissions editor dialog */
.permissions-dialog {
  width: min(820px, 96vw);
  max-height: 92vh;
  display: flex;
  flex-direction: column;
}

.permissions-dialog__sub {
  font-size: 0.82rem;
  opacity: 0.85;
  margin-top: 2px;
}

.permissions-dialog__role {
  font-weight: 800;
  letter-spacing: 0.04em;
}

.permissions-dialog__body {
  overflow-y: auto;
  flex: 1;
  padding: 16px 20px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.permissions-summary {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
}

.permissions-summary__pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.78rem;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 999px;
  border: 1px solid;
}

.permissions-summary__pill--default {
  background: #e6f0fb;
  color: #14467f;
  border-color: #b8d2f0;
}
.permissions-summary__pill--grant {
  background: #e3eef9;
  color: #2f6dc4;
  border-color: #a9c8e8;
}
.permissions-summary__pill--deny {
  background: #f7e7e3;
  color: #832a1a;
  border-color: #e6bdb1;
}

.permissions-summary__effective {
  margin-left: auto;
  font-size: 0.82rem;
  color: var(--ws-muted);
}

.permissions-summary__effective strong {
  color: var(--ws-ink);
  font-size: 1rem;
}

.permissions-help {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  background: #f3f6fb;
  border: 1px solid var(--ws-border);
  border-radius: var(--ws-radius-sm);
  padding: 10px 12px;
  font-size: 0.82rem;
  color: var(--ws-ink);
}

.permissions-help .q-icon {
  color: var(--ws-blue);
  margin-top: 1px;
  flex-shrink: 0;
}

.permissions-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.permissions-group {
  border: 1px solid var(--ws-border);
  border-radius: var(--ws-radius-sm);
  overflow: hidden;
}

.permissions-group__header {
  background: #eaeff7;
  padding: 10px 14px;
  font-size: 0.72rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #14467f;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.permissions-group__count {
  font-size: 0.7rem;
  font-weight: 700;
  color: #2f6dc4;
  background: #fff;
  padding: 2px 8px;
  border-radius: 999px;
  border: 1px solid #b8d2f0;
  font-variant-numeric: tabular-nums;
}

.permissions-group__items {
  display: flex;
  flex-direction: column;
}

.perm-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  padding: 12px 14px;
  border-top: 1px solid var(--ws-border);
  background: #fff;
  transition: background 0.15s ease;
}

.permissions-group__items .perm-row:first-child { border-top: none; }
.perm-row:hover { background: rgba(47, 109, 196, 0.04); }
.perm-row--granted { background: rgba(47, 109, 196, 0.06); }
.perm-row--denied  { background: rgba(176, 60, 38, 0.05); }
.perm-row--locked  { opacity: 0.7; }

.perm-row__info {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
  flex: 1;
}

.perm-row__label {
  font-weight: 600;
  color: var(--ws-ink);
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 6px;
}

.perm-row__lock-icon {
  color: var(--ws-muted);
}

.perm-row__meta {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.perm-row__key {
  font-family: 'JetBrains Mono', Consolas, monospace;
  font-size: 0.7rem;
  color: var(--ws-muted);
  background: #f3f6fb;
  padding: 1px 6px;
  border-radius: 2px;
}

.perm-row__badge {
  font-size: 0.62rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  padding: 1px 6px;
  border-radius: 2px;
  border: 1px solid transparent;
}

.perm-row__badge--default {
  background: #e6f0fb;
  color: #14467f;
  border-color: #b8d2f0;
}

.perm-row__badge--off {
  background: #eef0f4;
  color: #6b7892;
  border-color: #d3d8e1;
}

.perm-row__controls {
  flex-shrink: 0;
  width: 280px;
}

/* Three-state button group: Block / Allow|Off / Grant */
.perm-toggle {
  border: 1px solid var(--ws-border);
  border-radius: var(--ws-radius-sm);
  overflow: hidden;
  background: #f7f9fc;
}

.perm-toggle :deep(.q-btn) {
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  padding: 4px 8px;
  min-height: 28px;
  border-radius: 0;
  background: transparent;
  color: var(--ws-muted);
  border-right: 1px solid var(--ws-border);
}

.perm-toggle :deep(.q-btn:last-child) {
  border-right: none;
}

.perm-toggle :deep(.q-btn .q-icon) {
  margin-right: 4px;
  font-size: 14px;
}

.perm-toggle :deep(.q-btn--active) {
  background: var(--ws-blue);
  color: #fff;
}

.perm-toggle--denied :deep(.q-btn--active) {
  background: #832a1a;
  color: #fff;
}

.perm-toggle--granted :deep(.q-btn--active) {
  background: #14467f;
  color: #fff;
}

.perm-toggle--default :deep(.q-btn--active) {
  background: #2f6dc4;
  color: #fff;
}

.perm-toggle--off :deep(.q-btn--active) {
  background: #6b7892;
  color: #fff;
}

.permissions-dialog__footer {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
}
</style>
