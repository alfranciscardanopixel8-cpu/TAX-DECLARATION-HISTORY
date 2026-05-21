<template>
  <div class="app-sidebar" :class="{ 'app-sidebar--mini': mini }">
    <div class="app-sidebar-top">
      <q-avatar v-if="mini" square size="32px" class="sidebar-brand-mark">
        <q-icon name="account_balance" size="18px" />
      </q-avatar>
      <template v-else>
        <q-avatar square size="32px" class="sidebar-brand-mark">
          <q-icon name="account_balance" size="18px" />
        </q-avatar>
        <div class="app-sidebar-brand-text">
          <strong>Provincial Assessor</strong>
          <span>Records System</span>
        </div>
      </template>
    </div>

    <q-scroll-area class="app-sidebar-scroll">
      <q-list class="app-sidebar-list">
        <template v-for="module in modules" :key="module.id">
          <q-item-label v-if="!mini" header class="app-sidebar-module-label">
            {{ module.label }}
          </q-item-label>
          <q-separator v-else class="app-sidebar-mini-sep" />

          <q-item
            v-for="child in module.children"
            :key="child.name"
            :to="{ name: child.name }"
            clickable
            v-ripple
            active-class="app-sidebar-item--active"
            class="app-sidebar-item"
          >
            <q-item-section avatar>
              <q-icon :name="child.icon" size="18px" />
            </q-item-section>
            <q-item-section v-if="!mini">
              <q-item-label>{{ child.label }}</q-item-label>
            </q-item-section>
            <q-tooltip v-if="mini" anchor="center right" self="center left" :offset="[8, 0]">
              {{ child.label }}
            </q-tooltip>
          </q-item>
        </template>
      </q-list>
    </q-scroll-area>

    <div class="app-sidebar-footer">
      <q-btn
        flat
        dense
        no-caps
        color="white"
        class="sidebar-footer-btn"
        :icon="mini ? 'chevron_right' : 'chevron_left'"
        :label="mini ? undefined : 'Collapse'"
        @click="$emit('toggle-mini')"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { visibleModules } from '../../config/workspaceModules';
import { useAuth } from '../../composables/useAuth';

defineProps({
  mini: {
    type: Boolean,
    default: false
  }
});

defineEmits(['toggle-mini']);

const { sessionUser } = useAuth();
const modules = computed(() => visibleModules(sessionUser.value));
</script>

<style scoped>
.app-sidebar {
  background: #1a2332;
  color: #cbd5e1;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.app-sidebar-top {
  align-items: center;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  display: flex;
  gap: 10px;
  min-height: 48px;
  padding: 10px 14px;
}

.app-sidebar--mini .app-sidebar-top {
  justify-content: center;
  padding: 10px 8px;
}

.sidebar-brand-mark {
  background: rgba(212, 168, 67, 0.15);
  color: #d4a843;
}

.app-sidebar-brand-text {
  display: flex;
  flex-direction: column;
  gap: 1px;
  line-height: 1.2;
  min-width: 0;
}

.app-sidebar-brand-text strong {
  font-size: 0.78rem;
  color: #f1f5f9;
}

.app-sidebar-brand-text span {
  color: rgba(203, 213, 225, 0.6);
  font-size: 0.65rem;
}

.app-sidebar-scroll {
  flex: 1 1 auto;
  min-height: 0;
}

.app-sidebar-list {
  padding: 6px 0 10px;
}

.app-sidebar-module-label {
  color: rgba(203, 213, 225, 0.45);
  font-size: 0.6rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  padding: 10px 16px 4px;
  text-transform: uppercase;
}

.app-sidebar-mini-sep {
  background: rgba(255, 255, 255, 0.06);
  margin: 6px 10px;
}

.app-sidebar-item {
  border-radius: 6px;
  color: rgba(203, 213, 225, 0.85);
  margin: 1px 8px;
  min-height: 36px;
  font-size: 0.78rem;
}

.app-sidebar--mini .app-sidebar-item {
  justify-content: center;
  margin: 2px 6px;
  padding: 0;
}

.app-sidebar-item :deep(.q-icon) {
  color: rgba(203, 213, 225, 0.65);
}

.app-sidebar-item--active {
  background: rgba(255, 255, 255, 0.08);
  box-shadow: inset 3px 0 0 #d4a843;
  color: #f1f5f9;
  font-weight: 600;
}

.app-sidebar-item--active :deep(.q-icon) {
  color: #d4a843;
}

.app-sidebar-footer {
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  padding: 8px;
}

.app-sidebar--mini .sidebar-footer-btn {
  width: 100%;
}

.sidebar-footer-btn {
  width: 100%;
  font-size: 0.72rem;
}
</style>
