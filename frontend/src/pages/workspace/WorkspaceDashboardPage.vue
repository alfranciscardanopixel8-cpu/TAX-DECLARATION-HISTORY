<template>
  <div class="ws-page">
    <WorkspacePageHeader
      module="Dashboard"
      title="Overview"
      lead="Summary counts for properties, tax declarations, and documents in the system."
    >
      <template #actions>
        <q-btn outline no-caps color="primary" icon="refresh" label="Refresh" :loading="loading" @click="loadDashboard" />
      </template>
    </WorkspacePageHeader>

    <section class="ws-metric-grid">
      <div class="ws-metric-tile">
        <q-icon name="home_work" />
        <div>
          <span>Properties</span>
          <strong>{{ stats.properties }}</strong>
        </div>
      </div>
      <div class="ws-metric-tile">
        <q-icon name="verified" class="success" />
        <div>
          <span>Active TDs</span>
          <strong>{{ stats.activeTaxDeclarations }}</strong>
        </div>
      </div>
      <div class="ws-metric-tile">
        <q-icon name="history" class="violet" />
        <div>
          <span>TD Records</span>
          <strong>{{ stats.taxDeclarations }}</strong>
        </div>
      </div>
      <div class="ws-metric-tile">
        <q-icon name="inventory_2" class="amber" />
        <div>
          <span>Documents</span>
          <strong>{{ stats.documents }}</strong>
        </div>
      </div>
    </section>

    <section class="ws-card">
      <div class="ws-card__title">Quick access</div>
      <div class="ws-btn-row">
        <q-btn
          v-for="link in quickLinks"
          :key="link.name"
          outline
          no-caps
          color="primary"
          :icon="link.icon"
          :label="link.label"
          :to="{ name: link.name }"
        />
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { fetchDashboard } from '../../services/api';
import WorkspacePageHeader from '../../components/layout/WorkspacePageHeader.vue';

const loading = ref(false);
const dashboard = ref(null);

const stats = computed(() => ({
  properties: dashboard.value?.properties ?? 0,
  activeTaxDeclarations: dashboard.value?.active_tax_declarations ?? 0,
  taxDeclarations: dashboard.value?.tax_declarations ?? 0,
  documents: dashboard.value?.documents ?? 0
}));

const quickLinks = [
  { name: 'workspace-records', label: 'Search records', icon: 'search' },
  { name: 'workspace-digitize', label: 'Digitization queue', icon: 'scanner' },
  { name: 'workspace-activity', label: 'System activity', icon: 'history' }
];

async function loadDashboard() {
  loading.value = true;

  try {
    dashboard.value = await fetchDashboard();
  } finally {
    loading.value = false;
  }
}

onMounted(loadDashboard);
</script>
