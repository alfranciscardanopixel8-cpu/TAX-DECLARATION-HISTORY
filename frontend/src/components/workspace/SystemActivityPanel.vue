<template>
  <div class="ws-page">
    <WorkspacePageHeader
      module="Operations"
      title="System Activity"
      lead="Latest actions across all property records."
    >
      <template #actions>
        <q-btn outline no-caps color="primary" icon="refresh" label="Refresh" :loading="loading" @click="loadActivity" />
      </template>
    </WorkspacePageHeader>

    <div class="ws-card ws-card--flush">
      <q-list v-if="activity.length" separator class="ws-list">
        <q-item v-for="log in activity" :key="log.id">
          <q-item-section avatar>
            <q-icon color="primary" name="history" />
          </q-item-section>
          <q-item-section>
            <q-item-label>{{ log.description }}</q-item-label>
            <q-item-label caption>
              {{ log.action }} · {{ log.user?.name || 'System' }} · Property #{{ log.property_id }}
            </q-item-label>
            <q-item-label caption>{{ formatDate(log.created_at) }}</q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-btn flat no-caps color="primary" icon="folder_open" label="Open" @click="$emit('open-property', log.property_id)" />
          </q-item-section>
        </q-item>
      </q-list>
      <div v-else class="ws-empty">No recent activity recorded.</div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { fetchDashboard } from '../../services/api';
import WorkspacePageHeader from '../../components/layout/WorkspacePageHeader.vue';

defineEmits(['open-property']);

const loading = ref(false);
const activity = ref([]);

function formatDate(value) {
  if (!value) return '';
  return new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}

async function loadActivity() {
  loading.value = true;

  try {
    const data = await fetchDashboard();
    activity.value = data.recent_activity || [];
  } finally {
    loading.value = false;
  }
}

onMounted(loadActivity);

defineExpose({ reload: loadActivity });
</script>
