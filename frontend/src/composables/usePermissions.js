import { computed } from 'vue';
import { useAuth } from './useAuth';

/**
 * Capability-gating helpers backed by the API's effective-permission set.
 * Use these in templates to show/hide actions, e.g.:
 *
 *   const { can } = usePermissions();
 *   <q-btn v-if="can('property.delete')" ... />
 */
export function usePermissions() {
  const { sessionUser } = useAuth();

  const permissions = computed(() => sessionUser.value?.permissions || []);

  function can(permission) {
    if (!permission) return true;
    if (!sessionUser.value) return false;
    // Treat the legacy admin shortcut as a wildcard.
    if (sessionUser.value.role === 'admin' && (sessionUser.value.permissions === undefined)) {
      return true;
    }
    return permissions.value.includes(permission);
  }

  function canAny(...keys) {
    return keys.some((key) => can(key));
  }

  function canAll(...keys) {
    return keys.every((key) => can(key));
  }

  return { permissions, can, canAny, canAll };
}
