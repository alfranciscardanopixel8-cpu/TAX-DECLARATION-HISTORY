#!/usr/bin/env python3
from pathlib import Path

path = Path(r"c:\provincial-assessor-system\frontend\src\pages\SearchPage.vue")
text = path.read_text(encoding="utf-8")

# Template: replace opening through toolbar (skip login, layout, summary, tabs)
start_marker = '<template>'
toolbar_marker = '          <section class="toolbar-band">'
idx = text.find(toolbar_marker)
if idx == -1:
    raise SystemExit('toolbar-band not found')

text = text[: text.find('<template>') + len('<template>\n  ')] + '<motion.div class="records-workspace q-mt-md">\n' + text[idx:]

# Remove duplicate workspace-shell wrapper line if present
text = text.replace('        <motion.div class="workspace-shell">\n          <section class="toolbar-band">', '          <section class="toolbar-band">', 1)
text = text.replace('        <div class="workspace-shell">\n          <section class="toolbar-band">', '          <section class="toolbar-band">', 1)

# Remove summary-grid block
import re
text = re.sub(
    r'\n      <section class="summary-grid">.*?</section>\n',
    '\n',
    text,
    count=1,
    flags=re.S,
)

# Remove workspace tabs and recent band
text = re.sub(
    r'\n      <q-tabs v-model="workspaceNav".*?</q-tabs>\n',
    '\n',
    text,
    count=1,
    flags=re.S,
)
text = re.sub(
    r'\n      <section v-if="workspaceNav === \'records\' && recentProperties.*?</section>\n',
    '\n',
    text,
    count=1,
    flags=re.S,
)

# Remove v-show on search-band
text = text.replace('v-show="workspaceNav === \'records\'" ', '')

# Remove workspace panels
text = re.sub(
    r'\n      <DigitizationQueuePanel.*?</StaffUsersPanel>\n\n',
    '\n',
    text,
    count=1,
    flags=re.S,
)

text = text.replace('v-show="workspaceNav === \'records\'" ', '')

# Fix closing: remove extra workspace-shell, q-page, q-page-container; close records-workspace div
text = text.replace(
    """      </div>
        </div>
      </q-page>
    </q-page-container>

    <q-dialog v-model="movementHistoryDialog">""",
    """      </div>

    <q-dialog v-model="movementHistoryDialog">""",
)

text = text.replace('  </q-layout>\n</template>', '  </div>\n</template>')

# Script updates
replacements = [
    ("import { computed, nextTick, onMounted, onUnmounted, reactive, ref, watch } from 'vue';\nimport { useQuasar } from 'quasar';",
     "import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue';\nimport { useRoute } from 'vue-router';\nimport { useQuasar } from 'quasar';\nimport { useAuth } from '../composables/useAuth';"),
    ("  fetchDashboard,\n  fetchMe,\n  fetchProperties,", "  fetchProperties,"),
    ("  getStoredToken,\n  login,\n  logout,\n  movePhysicalRecord,", "  movePhysicalRecord,"),
    ("  fetchApiHealth,\n  fetchDocumentMovements,", "  fetchDocumentMovements,"),
    ("import DigitizationQueuePanel from '../components/workspace/DigitizationQueuePanel.vue';\nimport SystemActivityPanel from '../components/workspace/SystemActivityPanel.vue';\nimport StaffUsersPanel from '../components/workspace/StaffUsersPanel.vue';\n\n",
     ""),
    ("const $q = useQuasar();\nconst authLoading = ref(true);\nconst loginLoading = ref(false);\nconst sessionUser = ref(null);",
     "const $q = useQuasar();\nconst route = useRoute();\nconst { sessionUser } = useAuth();"),
    ("const workspaceNav = ref('records');\nconst apiHealth = reactive({ online: false, status: 'checking' });\n",
     ""),
    ("const digitizePanel = ref(null);\nconst canAdminister = computed(() => Boolean(sessionUser.value?.can_administer));\n\nconst loginForm = reactive({\n  email: 'admin@assessor.local',\n  password: 'password'\n});\n\n",
     ""),
    ("const digitizeForm = reactive({\n  file: null,\n  notes: ''\n});",
     "const digitizeForm = reactive({\n  file: null,\n  notes: '',\n  ocr_text: ''\n});"),
]

for old, new in replacements:
    if old not in text:
        print('WARN missing:', old[:50])
    else:
        text = text.replace(old, new, 1)

# Remove auth functions block
text = re.sub(
    r'\nasync function checkApiHealth\(\).*?async function submitLogout\(\) \{.*?\}\n',
    '\n',
    text,
    count=1,
    flags=re.S,
)

text = text.replace("  workspaceNav.value = 'records';\n  ", '')

# onMounted
text = re.sub(
    r'let healthTimer;\n\nonMounted\(async \(\) => \{.*?\}\);\n\nonUnmounted\(.*?\}\);\n',
    '',
    text,
    count=1,
    flags=re.S,
)

if 'async function bootstrapRecords()' not in text:
    text = text.replace(
        'watch(() => ({',
        """async function bootstrapRecords() {
  await loadReferences();
  await loadRecords();

  const propertyId = Number(route.query.propertyId);
  if (propertyId) {
    await openPropertyById(propertyId);
  }
}

onMounted(bootstrapRecords);

watch(() => ({""",
    )

# digitize save ocr
text = text.replace(
    """    const updated = await digitizeDocument(selectedDocument.value.id, {
      file: digitizeForm.file,
      notes: digitizeForm.notes
    });""",
    """    const updated = await digitizeDocument(selectedDocument.value.id, {
      file: digitizeForm.file,
      notes: digitizeForm.notes,
      ocr_text: digitizeForm.ocr_text || undefined
    });""",
)

text = text.replace(
    '  digitizeForm.notes = \'\';\n',
    "  digitizeForm.notes = '';\n  digitizeForm.ocr_text = '';\n",
)

# Add ocr input in digitize dialog if missing
if 'label="OCR text' not in text:
    text = text.replace(
        '<q-input v-model="digitizeForm.notes" outlined dense type="textarea" label="Digitization notes" class="span-2" />',
        '<q-input v-model="digitizeForm.ocr_text" outlined dense type="textarea" label="OCR text (searchable)" class="span-2" hint="Paste extracted text from the scan for full-text search." />\n            <q-input v-model="digitizeForm.notes" outlined dense type="textarea" label="Digitization notes" class="span-2" />',
    )

text = text.replace('motion.div', 'div')
path.write_text(text, encoding='utf-8')
print('SearchPage.vue updated for Phase 6')
