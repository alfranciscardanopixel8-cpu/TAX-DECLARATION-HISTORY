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

        <q-toolbar-title class="app-toolbar-title">
          <span class="text-weight-medium">{{ pageTitle }}</span>
          <div v-if="pageCaption" class="text-caption text-blue-grey-1">{{ pageCaption }}</div>
        </q-toolbar-title>

        <q-badge
          outline
          :color="apiHealth.online ? 'positive' : 'warning'"
          class="q-mr-sm gt-xs"
          :label="apiHealth.online ? 'API online' : 'API offline'"
        />
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

const router = useRouter();
const route = useRoute();
const drawerOpen = ref(true);
const drawerMini = ref(false);

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

const routeMeta = computed(() => findRouteMeta(route.name) || {
  moduleLabel: 'Workspace',
  pageLabel: 'Provincial Assessor',
  caption: null
});

const pageTitle = computed(() => routeMeta.value.pageLabel);
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
