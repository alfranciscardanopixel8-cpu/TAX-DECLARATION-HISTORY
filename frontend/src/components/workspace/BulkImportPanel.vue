<template>
  <div class="ws-page">
    <WorkspacePageHeader
      module="Data"
      title="Bulk Property Import"
      lead="Upload a CSV to create or update property and tax declaration records."
    >
      <template #actions>
        <q-btn outline no-caps color="primary" icon="download" label="Download template" @click="downloadTemplate" />
      </template>
    </WorkspacePageHeader>

    <div class="ws-card">
      <div class="ws-form-stack">
        <q-file
          v-model="importFile"
          outlined
          dense
          label="CSV file"
          accept=".csv,text/csv"
          clearable
        >
          <template #prepend>
            <q-icon name="upload_file" />
          </template>
        </q-file>

        <q-btn
          unelevated
          no-caps
          color="primary"
          icon="cloud_upload"
          label="Import records"
          :disable="!importFile"
          :loading="loading"
          @click="runImport"
        />
      </div>
    </div>

    <q-banner v-if="result" rounded class="bg-blue-grey-1">
      <div>Created: {{ result.created }} · Updated: {{ result.updated }} · Errors: {{ result.error_count }}</div>
      <ul v-if="result.errors && Object.keys(result.errors).length" class="q-mt-sm q-pl-md">
        <li v-for="(message, line) in result.errors" :key="line">Row {{ line }}: {{ message }}</li>
      </ul>
    </q-banner>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useQuasar } from 'quasar';
import { downloadPropertyImportTemplate, importPropertiesCsv } from '../../services/api';
import WorkspacePageHeader from '../../components/layout/WorkspacePageHeader.vue';

const $q = useQuasar();
const importFile = ref(null);
const loading = ref(false);
const result = ref(null);

async function downloadTemplate() {
  try {
    await downloadPropertyImportTemplate();
  } catch {
    $q.notify({ type: 'negative', message: 'Unable to download import template.' });
  }
}

async function runImport() {
  if (!importFile.value) return;

  loading.value = true;
  result.value = null;

  try {
    result.value = await importPropertiesCsv(importFile.value);
    $q.notify({ type: 'positive', message: 'Import completed.' });
    importFile.value = null;
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Import failed.'
    });
  } finally {
    loading.value = false;
  }
}
</script>
