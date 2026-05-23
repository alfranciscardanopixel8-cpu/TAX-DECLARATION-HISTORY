<template>
  <div class="app-sidebar" :class="{ 'app-sidebar--mini': mini }">
    <div class="app-sidebar-top">
      <q-avatar v-if="mini" square size="40px" class="sidebar-brand-mark">
        <q-icon name="account_balance" size="22px" />
      </q-avatar>
      <template v-else>
        <q-avatar square size="40px" class="sidebar-brand-mark">
          <q-icon name="account_balance" size="22px" />
        </q-avatar>
        <div class="app-sidebar-brand-text">
          <strong>Provincial Assessor</strong>
          <span>Records system</span>
        </div>
      </template>
    </div>

    <q-scroll-area class="app-sidebar-scroll">
      <q-list class="app-sidebar-list">
        <template v-for="module in modules" :key="module.id">
          <q-item-label v-if="!mini && module.label" header class="app-sidebar-module-label">
            {{ module.label }}
          </q-item-label>
          <q-separator v-else-if="mini" class="app-sidebar-mini-sep" />

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
              <q-icon :name="child.icon" size="20px" />
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
  background: linear-gradient(180deg, #153044 0%, #0f2838 100%);
  color: #e8f0f4;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.app-sidebar-top {
  align-items: center;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  gap: 12px;
  min-height: 64px;
  padding: 14px 16px;
}

.app-sidebar--mini .app-sidebar-top {
  justify-content: center;
  padding: 14px 8px;
}

.sidebar-brand-mark {
  background: rgba(215, 180, 106, 0.2);
  color: #e8c468;
}

.app-sidebar-brand-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
  line-height: 1.2;
  min-width: 0;
}

.app-sidebar-brand-text strong {
  font-size: 14px;
}

.app-sidebar-brand-text span {
  color: rgba(232, 240, 244, 0.65);
  font-size: 11px;
}

.app-sidebar-scroll {
  flex: 1 1 auto;
  min-height: 0;
}

.app-sidebar-list {
  padding: 8px 0 12px;
}

.app-sidebar-module-label {
  color: rgba(232, 240, 244, 0.45);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.08em;
  padding: 12px 20px 6px;
  text-transform: uppercase;
}

.app-sidebar-mini-sep {
  background: rgba(255, 255, 255, 0.1);
  margin: 8px 12px;
}

.app-sidebar-item {
  border-radius: 10px;
  color: rgba(232, 240, 244, 0.88);
  margin: 2px 10px;
  min-height: 44px;
}

.app-sidebar--mini .app-sidebar-item {
  justify-content: center;
  margin: 4px 8px;
  padding: 0;
}

.app-sidebar-item :deep(.q-icon) {
  color: rgba(232, 240, 244, 0.75);
}

.app-sidebar-item--active {
  background: rgba(255, 255, 255, 0.1);
  box-shadow: inset 3px 0 0 #d7b46a;
  color: #fff;
  font-weight: 600;
}

.app-sidebar-item--active :deep(.q-icon) {
  color: #e8c468;
}

.app-sidebar-footer {
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  padding: 10px;
}

.app-sidebar--mini .sidebar-footer-btn {
  width: 100%;
}

.sidebar-footer-btn {
  width: 100%;
}
</style>
