<template>
  <div v-if="authLoading" class="login-page boot-page">
    <q-spinner color="primary" size="50px" />
    <p class="login-page-subtitle">Loading records workspace…</p>
  </div>

  <q-layout v-else view="hHh Lpr lfr" class="app-layout">
    <q-header elevated class="app-header text-white">
      <q-toolbar class="app-toolbar">
        <q-btn
          flat
          round
          dense
          color="white"
          :icon="drawerOpen ? 'menu_open' : 'menu'"
          aria-label="Toggle navigation"
          @click="toggleDrawer"
        />

        <div class="app-brand">
          <q-icon name="account_balance" size="28px" class="app-brand-icon" />
          <div class="app-brand-text">
            <strong>Provincial Assessor's Office</strong>
            <span>Real Property Records System</span>
          </div>
        </div>

        <q-toolbar-title class="app-toolbar-title">
          <div class="toolbar-title-content">
            <q-icon :name="pageIcon" size="20px" class="toolbar-title-icon" />
            <div class="toolbar-title-text">
              <span class="toolbar-title-main">{{ pageTitle }}</span>
              <span v-if="pageCaption" class="toolbar-title-caption">{{ pageCaption }}</span>
            </div>
          </div>
        </q-toolbar-title>

        <q-badge
          outline
          :color="apiHealth.online ? 'positive' : 'warning'"
          class="q-mr-sm gt-xs"
          :label="apiHealth.online ? 'API online' : 'API offline'"
        />

        <q-btn flat round dense color="white" icon="notifications" class="q-mr-sm">
          <q-badge v-if="pendingApprovals > 0" floating color="amber" text-color="black">{{ pendingApprovals }}</q-badge>
          <q-menu anchor="bottom right" self="top right">
            <q-card style="min-width: 320px;">
              <q-card-section class="bg-primary text-white">
                <div class="text-subtitle2 text-weight-bold">Notifications</div>
              </q-card-section>
              <q-list separator>
                <q-item v-if="pendingApprovals > 0" clickable @click="goToApprovals">
                  <q-item-section avatar>
                    <q-icon name="pending_actions" color="amber-9" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>{{ pendingApprovals }} TD{{ pendingApprovals > 1 ? 's' : '' }} pending approval</q-item-label>
                    <q-item-label caption>Click to review</q-item-label>
                  </q-item-section>
                </q-item>
                <q-item v-else>
                  <q-item-section>
                    <q-item-label class="text-blue-grey-6">No pending notifications</q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </q-menu>
        </q-btn>

        <q-badge outline color="white" class="q-mr-sm gt-xs" :label="`${sessionUser?.name} · ${roleLabel(sessionUser?.role)}`" />
        <q-btn flat no-caps color="white" icon="logout" label="Logout" @click="submitLogout" />
      </q-toolbar>
    </q-header>

    <q-drawer
      v-model="drawerOpen"
      show-if-above
      bordered
      :width="248"
      :mini="drawerMini"
      :mini-width="72"
      class="app-drawer"
      :breakpoint="1024"
    >
      <AppSidebar :mini="drawerMini" @toggle-mini="drawerMini = !drawerMini" />
    </q-drawer>

    <q-page-container class="app-page-container">
      <q-page class="app-main-page">
        <div class="workspace-shell">
          <router-view />
        </div>
      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { findRouteMeta } from '../config/workspaceModules';
import { useAuth } from '../composables/useAuth';
import AppSidebar from '../components/layout/AppSidebar.vue';
import { fetchDashboard } from '../services/api';

const router = useRouter();
const route = useRoute();
const drawerOpen = ref(true);
const drawerMini = ref(false);
const pendingApprovals = ref(0);

const {
  sessionUser,
  authLoading,
  apiHealth,
  restoreSession,
  submitLogout,
  startHealthPolling,
  stopHealthPolling,
  roleLabel
} = useAuth();

async function loadPendingApprovals() {
  if (!sessionUser.value) return;
  try {
    const data = await fetchDashboard();
    if (data) {
      pendingApprovals.value = data.pending_approvals || 0;
    }
  } catch {
    // ignore
  }
}

function goToApprovals() {
  router.push({ name: 'workspace-records', query: { td_status: 'For Review' } });
}

const routeMeta = computed(() => findRouteMeta(route.name) || {
  moduleLabel: 'Workspace',
  pageLabel: 'Provincial Assessor',
  caption: null
});

const pageTitle = computed(() => routeMeta.value.pageLabel);
const pageIcon = computed(() => {
  const map = {
    'workspace-dashboard': 'dashboard',
    'workspace-records': 'search',
    'workspace-activity': 'fact_check',
    'workspace-staff': 'group',
    'workspace-security': 'shield'
  };
  return map[route.name] || 'apps';
});
const pageCaption = computed(() => {
  const parts = [routeMeta.value.moduleLabel, routeMeta.value.caption].filter(Boolean);

  return parts.join(' · ');
});

function toggleDrawer() {
  drawerOpen.value = !drawerOpen.value;

  if (drawerOpen.value) {
    drawerMini.value = false;
  }
}

onMounted(async () => {
  await restoreSession();

  if (!sessionUser.value) {
    await router.replace({ name: 'login' });
    return;
  }

  drawerOpen.value = window.innerWidth >= 1024;
  startHealthPolling();
  loadPendingApprovals();
  // Refresh pending approvals every 60 seconds
  setInterval(loadPendingApprovals, 60000);
});

onUnmounted(stopHealthPolling);

watch(
  () => route.name,
  () => {
    if (window.innerWidth < 1024) {
      drawerOpen.value = false;
    }
  }
);
</script>

<style scoped>
.login-page.boot-page {
  align-items: center;
  background: linear-gradient(160deg, #e8f0ef 0%, #f6f8f8 45%, #eef3f2 100%);
  display: flex;
  flex-direction: column;
  gap: 16px;
  justify-content: center;
  min-height: 100vh;
}

.app-header {
  background: #18324a !important;
}

.app-toolbar {
  min-height: 56px;
  padding: 0 12px 0 8px;
}

.app-toolbar-title {
  font-size: 1rem;
  padding-left: 8px;
}

.toolbar-title-content {
  display: flex;
  align-items: center;
  gap: 12px;
  height: 100%;
}

.toolbar-title-icon {
  color: #ffd07e;
  background: rgba(255, 255, 255, 0.1);
  padding: 8px;
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.15);
}

.toolbar-title-text {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
  min-width: 0;
}

.toolbar-title-main {
  font-size: 1rem;
  font-weight: 800;
  color: #fff;
  letter-spacing: 0.01em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.toolbar-title-caption {
  font-size: 0.72rem;
  color: rgba(255, 255, 255, 0.72);
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.app-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0 16px;
  margin-right: 8px;
  border-right: 1px solid rgba(255, 255, 255, 0.15);
  height: 100%;
}

.app-brand-icon {
  color: #ffd07e;
}

.app-brand-text {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.app-brand-text strong {
  font-size: 0.95rem;
  font-weight: 800;
  color: #fff;
  letter-spacing: 0.02em;
}

.app-brand-text span {
  font-size: 0.72rem;
  color: rgba(255, 255, 255, 0.78);
  font-weight: 600;
}

@media (max-width: 768px) {
  .app-brand-text {
    display: none;
  }
}

.app-drawer :deep(.q-drawer__content) {
  background: transparent;
  overflow: hidden;
}

.app-page-container {
  background: #eef3f2;
}

.app-page-container :deep(.scroll) {
  overflow-x: hidden;
}

.app-main-page {
  align-items: stretch;
  display: flex;
  flex-direction: column;
  justify-content: flex-start !important;
  min-height: calc(100vh - 56px) !important;
  padding: 0 !important;
}

.app-main-page > .workspace-shell {
  align-self: center;
  flex: 0 0 auto;
}

.workspace-shell {
  box-sizing: border-box;
  margin: 0 auto;
  max-width: 1560px;
  padding: 20px 24px 32px;
  width: 100%;
}
</style>
