#!/usr/bin/env python3
"""Apply phased workspace enhancements to SearchPage.vue."""

from pathlib import Path

path = Path(r"c:\provincial-assessor-system\frontend\src\pages\SearchPage.vue")
text = path.read_text(encoding="utf-8")

replacements = [
    (
        '        <q-badge outline color="white" class="q-mr-sm" :label="`${sessionUser.name} · ${roleLabel(sessionUser.role)}`" />',
        '''        <q-badge
          outline
          :color="apiHealth.online ? 'positive' : 'warning'"
          class="q-mr-sm"
          :label="apiHealth.online ? 'API online' : 'API offline'"
        />
        <q-badge outline color="white" class="q-mr-sm" :label="`${sessionUser.name} · ${roleLabel(sessionUser.role)}`" />''',
    ),
    (
        "      </section>\n\n      <section class=\"search-band\">",
        '''      </section>

      <q-tabs v-model="workspaceNav" dense align="left" active-color="primary" class="workspace-nav q-mt-md">
        <q-tab name="records" icon="search" label="Records search" />
        <q-tab name="digitize" icon="scanner" label="Digitization queue" />
        <q-tab name="activity" icon="history" label="System activity" />
        <q-tab v-if="canAdminister" name="staff" icon="group" label="Staff accounts" />
      </q-tabs>

      <section v-if="workspaceNav === 'records' && recentProperties.length" class="recent-band">
        <div class="section-kicker">Recently opened</div>
        <motion.div class="recent-chips">
          <q-chip
            v-for="item in recentProperties"
            :key="item.id"
            clickable
            outline
            color="primary"
            icon="history"
            @click="openRecentProperty(item.id)"
          >
            {{ item.lot_number || item.pin }} · {{ item.municipality }}
          </q-chip>
        </div>
      </section>

      <section v-show="workspaceNav === 'records'" class="search-band">'''.replace("motion.div", "div"),
    ),
    (
        "      <div class=\"content-grid\">",
        '''      <DigitizationQueuePanel v-if="workspaceNav === 'digitize'" ref="digitizePanel" @open-property="openPropertyById" />
      <SystemActivityPanel v-else-if="workspaceNav === 'activity'" @open-property="openPropertyById" />
      <StaffUsersPanel v-else-if="workspaceNav === 'staff' && canAdminister" />

      <div v-show="workspaceNav === 'records'" class="content-grid">''',
    ),
    (
        "                          <q-btn v-if=\"canManage\" flat round color=\"primary\" icon=\"sync_alt\" aria-label=\"Move physical record\" @click=\"openMovementDialog(document)\">",
        '''                          <q-btn flat round color="primary" icon="timeline" aria-label="Movement history" @click="openMovementHistoryDialog(document)">
                            <q-tooltip>Movement history</q-tooltip>
                          </q-btn>
                          <q-btn v-if="canManage" flat round color="primary" icon="sync_alt" aria-label="Move physical record" @click="openMovementDialog(document)">''',
    ),
    (
        "                        <q-btn outline dense no-caps color=\"negative\" icon=\"delete\" label=\"Remove\" @click=\"removeAssessmentRecord(selectedTdEntry.tax_declaration, record)\" />",
        '''                        <q-btn v-if="canManage" outline dense no-caps color="primary" icon="edit" label="Edit" @click="openAssessmentEditDialog(selectedTdEntry.tax_declaration, record)" />
                        <q-btn outline dense no-caps color="negative" icon="delete" label="Remove" @click="removeAssessmentRecord(selectedTdEntry.tax_declaration, record)" />''',
    ),
    (
        "                <motion.div class=\"ui-block-head\">",
        "                <div class=\"ui-block-head\">",
    ),
    (
        '''                <div class="ui-block-head">
                  <div class="ui-block-title">Activity log</motion.div>
                  <q-select''',
        '''                <div class="ui-block-head">
                  <div class="ui-block-title">Activity log</div>
                  <div class="row items-center q-gutter-sm">
                  <q-btn v-if="selected" outline dense no-caps color="primary" icon="download" label="Export CSV" @click="exportActivityCsv" />
                  <q-select''',
    ),
    (
        '''                    style="min-width: 200px"
                  />
                </div>''',
        '''                    style="min-width: 200px"
                  />
                  </div>
                </div>''',
    ),
    (
        "            <div class=\"text-h6\">Add Assessment Detail</div>",
        "            <div class=\"text-h6\">{{ assessmentDialogTitle }}</div>",
    ),
    (
        "              <q-btn color=\"primary\" icon=\"save\" label=\"Save Assessment\" type=\"submit\" :loading=\"saving\" />",
        "              <q-btn color=\"primary\" icon=\"save\" :label=\"assessmentSubmitLabel\" type=\"submit\" :loading=\"saving\" />",
    ),
    (
        "    <q-dialog v-model=\"entryDialog\" persistent>",
        '''    <q-dialog v-model="movementHistoryDialog">
      <q-card class="entry-card compact-card">
        <q-card-section class="row items-center justify-between">
          <div>
            <div class="text-h6">Physical movement history</div>
            <div class="text-caption text-blue-grey-7">{{ selectedDocument?.reference_number || selectedDocument?.file_name }}</div>
          </div>
          <q-btn flat round icon="close" v-close-popup />
        </q-card-section>
        <q-separator />
        <q-card-section>
          <q-inner-loading :showing="movementHistoryLoading" />
          <q-list v-if="movementHistory.length" bordered separator>
            <q-item v-for="movement in movementHistory" :key="movement.id">
              <q-item-section>
                <q-item-label>{{ movement.movement_type }} · {{ movement.to_location || movement.from_location }}</q-item-label>
                <q-item-label caption>{{ movement.user?.name || 'System' }} · {{ dateFormat(movement.moved_at || movement.created_at) }}</q-item-label>
                <q-item-label caption>{{ movement.remarks || 'No remarks' }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
          <motion.div v-else class="empty-state compact">No movement history recorded.</div>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="entryDialog" persistent>'''.replace("motion.div", "motion.div").replace('<motion.div v-else', '<div v-else').replace('</motion.div>', '</div>'),
    ),
    (
        """  updateTaxDeclaration
} from '../services/api';
import { referenceOptions } from '../data/mockRecords';
""",
        """  updateTaxDeclaration,
  updateAssessment,
  fetchApiHealth,
  fetchDocumentMovements,
  downloadPropertyDossierExport,
  downloadPropertyActivityCsv
} from '../services/api';
import { referenceOptions } from '../data/mockRecords';
import { loadRecentProperties, rememberProperty } from '../utils/recentProperties';
import DigitizationQueuePanel from '../components/workspace/DigitizationQueuePanel.vue';
import SystemActivityPanel from '../components/workspace/SystemActivityPanel.vue';
import StaffUsersPanel from '../components/workspace/StaffUsersPanel.vue';
""",
    ),
    (
        "const auditTdFilter = ref(null);",
        """const auditTdFilter = ref(null);
const workspaceNav = ref('records');
const apiHealth = reactive({ online: false, status: 'checking' });
const recentProperties = ref(loadRecentProperties());
const movementHistoryDialog = ref(false);
const movementHistoryLoading = ref(false);
const movementHistory = ref([]);
const editingAssessmentId = ref(null);
const digitizePanel = ref(null);
const canAdminister = computed(() => Boolean(sessionUser.value?.can_administer));""",
    ),
    (
        "const declarationSubmitLabel = computed(() => editingDeclarationId.value ? 'Save TD Changes' : 'Add To History');",
        """const declarationSubmitLabel = computed(() => editingDeclarationId.value ? 'Save TD Changes' : 'Add To History');
const assessmentDialogTitle = computed(() => editingAssessmentId.value ? 'Edit Assessment Detail' : 'Add Assessment Detail');
const assessmentSubmitLabel = computed(() => editingAssessmentId.value ? 'Save Changes' : 'Save Assessment');""",
    ),
    (
        """async function loadDossier(propertyId) {
  const data = await fetchPropertyDossier(propertyId);

  if (data?.property) {
    dossier.value = data;
    selected.value = data.property;
    await nextTick();
    pickDefaultTd();
    return;
  }
""",
        """async function loadDossier(propertyId) {
  const data = await fetchPropertyDossier(propertyId);

  if (data?.property) {
    dossier.value = data;
    selected.value = data.property;
    recentProperties.value = rememberProperty(data.property);
    await nextTick();
    pickDefaultTd();
    return;
  }
""",
    ),
    (
        "function exportRecord() {\n  const payload = dossier.value || { property: selected.value };\n  const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' });\n  const url = URL.createObjectURL(blob);\n  const link = document.createElement('a');\n  link.href = url;\n  link.download = `property-${selected.value.pin || selected.value.id}-record.json`;\n  link.click();\n  URL.revokeObjectURL(url);\n}",
        """async function exportRecord() {
  if (!selected.value?.id) return;

  try {
    await downloadPropertyDossierExport(selected.value.id);
    $q.notify({ type: 'positive', message: 'Property dossier downloaded.' });
  } catch {
    $q.notify({ type: 'negative', message: 'Unable to export property dossier.' });
  }
}

async function exportActivityCsv() {
  if (!selected.value?.id) return;

  try {
    await downloadPropertyActivityCsv(selected.value.id);
    $q.notify({ type: 'positive', message: 'Activity log exported.' });
  } catch {
    $q.notify({ type: 'negative', message: 'Unable to export activity log.' });
  }
}""",
    ),
]

for old, new in replacements:
    if old not in text:
        print(f"MISSING: {old[:60]}...")
    else:
        text = text.replace(old, new, 1)

# printRecord enhancement
old_print = "  const rows = taxHistory.value.map((td) => `"
if old_print in text and "documentRows" not in text:
    text = text.replace(
        "  const rows = taxHistory.value.map((td) => `",
        """  const documentRows = (dossier.value?.property_documents || [])
    .concat(...(taxDeclarationTimeline.value || []).flatMap((entry) => entry.documents || []))
    .map((document) => `
    <tr>
      <td>${escapeHtml(document.document_type)}</td>
      <td>${escapeHtml(document.reference_number || document.file_name || '')}</td>
      <td>${escapeHtml(document.physical_copy_status || '')}</td>
      <td>${escapeHtml(document.storage_location || '')}</td>
    </tr>
  `).join('');

  const rows = taxHistory.value.map((td) => `""",
    )
    text = text.replace(
        "          <tbody>${rows}</tbody>\n        </table>",
        """          <tbody>${rows}</tbody>
        </table>
        <h2 style="margin-top:28px;font-size:16px;">Documents</h2>
        <table>
          <thead>
            <tr>
              <th>Type</th>
              <th>Reference</th>
              <th>Physical Status</th>
              <th>Location</th>
            </tr>
          </thead>
          <tbody>${documentRows || '<tr><td colspan="4">No documents</td></tr>'}</tbody>
        </table>""",
    )

# openMovementHistoryDialog and related
if "openMovementHistoryDialog" not in text:
    text = text.replace(
        "function openMovementDialog(document) {",
        """async function openMovementHistoryDialog(document) {
  selectedDocument.value = document;
  movementHistoryDialog.value = true;
  movementHistoryLoading.value = true;
  movementHistory.value = [];

  try {
    movementHistory.value = await fetchDocumentMovements(document.id);
  } catch {
    $q.notify({ type: 'negative', message: 'Unable to load movement history.' });
  } finally {
    movementHistoryLoading.value = false;
  }
}

function openMovementDialog(document) {""",
    )

if "openAssessmentEditDialog" not in text:
    text = text.replace(
        "function openAssessmentDialog(td = null) {",
        """function openAssessmentEditDialog(td, record) {
  editingAssessmentId.value = record.id;
  Object.assign(assessmentForm, {
    ...blankAssessmentForm(),
    ...record,
    tax_declaration_id: td.id
  });
  assessmentDialog.value = true;
}

function openAssessmentDialog(td = null) {
  editingAssessmentId.value = null;""",
    )

if "openPropertyById" not in text:
    text = text.replace(
        "async function selectRecord(_, row) {",
        """async function openPropertyById(propertyId) {
  if (!propertyId) return;
  workspaceNav.value = 'records';
  await loadDossier(propertyId);
  await nextTick();
  const panel = recordPanel.value?.$el || recordPanel.value;
  panel?.scrollIntoView?.({ behavior: 'smooth', block: 'start' });
}

async function openRecentProperty(propertyId) {
  await openPropertyById(propertyId);
}

async function selectRecord(_, row) {""",
    )

if "checkApiHealth" not in text:
    text = text.replace(
        "async function restoreSession() {",
        """async function checkApiHealth() {
  const health = await fetchApiHealth();
  Object.assign(apiHealth, health);
}

async function restoreSession() {""",
    )
    text = text.replace(
        "    if (sessionUser.value) {\n      await loadReferences();\n      await loadRecords();\n    }",
        """    if (sessionUser.value) {
      await checkApiHealth();
      await loadReferences();
      await loadRecords();
    }""",
    )
    text = text.replace(
        "    sessionUser.value = await login(loginForm.email, loginForm.password);\n    await loadReferences();",
        """    sessionUser.value = await login(loginForm.email, loginForm.password);
    await checkApiHealth();
    await loadReferences();""",
    )

# saveAssessment update
text = text.replace(
    """async function saveAssessment() {
  if (!selected.value || !assessmentForm.tax_declaration_id) return;

  saving.value = true;
  const updated = await addAssessment(selected.value.id, assessmentForm.tax_declaration_id, JSON.parse(JSON.stringify(assessmentForm)));

  if (updated) {
    await replaceSelected(updated);
    assessmentDialog.value = false;
    profileTab.value = 'assessments';
    await loadRecords();
    $q.notify({ type: 'positive', message: 'Assessment detail saved.' });
  } else {
    $q.notify({ type: 'negative', message: 'Unable to save assessment detail.' });
  }

  saving.value = false;
}""",
    """async function saveAssessment() {
  if (!selected.value || !assessmentForm.tax_declaration_id) return;

  saving.value = true;

  try {
    const payload = JSON.parse(JSON.stringify(assessmentForm));
    const updated = editingAssessmentId.value
      ? await updateAssessment(selected.value.id, assessmentForm.tax_declaration_id, editingAssessmentId.value, payload)
      : await addAssessment(selected.value.id, assessmentForm.tax_declaration_id, payload);

    if (updated) {
      await replaceSelected(updated);
      assessmentDialog.value = false;
      editingAssessmentId.value = null;
      profileTab.value = 'assessments';
      await loadRecords();
      $q.notify({ type: 'positive', message: editingAssessmentId.value ? 'Assessment updated.' : 'Assessment detail saved.' });
    } else {
      $q.notify({ type: 'negative', message: 'Unable to save assessment detail.' });
    }
  } catch {
    $q.notify({ type: 'negative', message: 'Unable to save assessment detail.' });
  } finally {
    saving.value = false;
  }
}""",
)

# styles
if ".workspace-nav" not in text:
    text = text.replace(
        ".workspace-page {",
        """.workspace-nav {
  background: #fff;
  border: 1px solid #d8e2ec;
  border-radius: 12px;
}

.recent-band {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.recent-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.workspace-page {""",
    )

path.write_text(text, encoding="utf-8")
print("SearchPage.vue updated")
