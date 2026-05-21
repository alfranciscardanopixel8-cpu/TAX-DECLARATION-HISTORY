<template>
  <div class="ws-page">
    <WorkspacePageHeader
      module="Digitization"
      title="Scanning Queue"
      lead="Physical records marked for scanning across all properties."
    >
      <template #actions>
        <q-btn outline no-caps color="primary" icon="refresh" label="Refresh" :loading="loading" @click="loadQueue" />
      </template>
    </WorkspacePageHeader>

    <q-banner v-if="offline" rounded class="bg-amber-1 text-amber-10">
      Showing offline demo data. Connect the API for the live queue.
    </q-banner>

    <div class="ws-card ws-card--flush">
      <q-table
        flat
        class="ws-table"
        :loading="loading"
        :rows="items"
        :columns="columns"
        row-key="id"
        :pagination="{ rowsPerPage: 15 }"
      >
        <template #body-cell-property="props">
          <q-td :props="props">
            <div class="text-weight-medium">{{ props.row.property?.pin || '—' }}</div>
            <div class="text-caption text-blue-grey-6">{{ props.row.property?.lot_number }}</div>
          </q-td>
        </template>
        <template #body-cell-location="props">
          <q-td :props="props">
            {{ props.row.property?.barangay }}, {{ props.row.property?.municipality }}
          </q-td>
        </template>
        <template #body-cell-td="props">
          <q-td :props="props">{{ props.row.tax_declaration?.td_number || 'Property-level' }}</q-td>
        </template>
        <template #body-cell-status="props">
          <q-td :props="props">
            <q-badge color="amber-8" :label="props.row.physical_copy_status || 'For Scanning'" />
          </q-td>
        </template>
        <template #body-cell-open="props">
          <q-td :props="props">
            <q-btn flat no-caps color="primary" icon="folder_open" label="Open" @click="$emit('open-property', props.row.property?.id)" />
          </q-td>
        </template>
      </q-table>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { fetchDigitizationQueue, getStoredToken } from '../../services/api';
import WorkspacePageHeader from '../../components/layout/WorkspacePageHeader.vue';

defineEmits(['open-property']);

const loading = ref(false);
const items = ref([]);
const offline = ref(false);

const columns = [
  { name: 'property', label: 'Property', field: 'property', align: 'left' },
  { name: 'location', label: 'Location', field: 'location', align: 'left' },
  { name: 'document_type', label: 'Document', field: 'document_type', align: 'left' },
  { name: 'td', label: 'TD', field: 'td', align: 'left' },
  { name: 'reference_number', label: 'Reference', field: 'reference_number', align: 'left' },
  { name: 'status', label: 'Status', field: 'status', align: 'left' },
  { name: 'open', label: '', field: 'open', align: 'right' }
];

async function loadQueue() {
  loading.value = true;

  try {
    const data = await fetchDigitizationQueue();
    items.value = data.items || [];
    offline.value = getStoredToken() === 'offline-demo-token';
  } finally {
    loading.value = false;
  }
}

onMounted(loadQueue);

defineExpose({ reload: loadQueue });
</script>
