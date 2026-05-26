/**
 * Sidebar module definitions. Each child maps to a vue-router route name.
 * Visibility uses role lists and/or can(user) callbacks aligned with API permissions.
 */

export const workspaceModules = [
  {
    id: 'dashboard',
    label: '',
    icon: 'dashboard',
    children: [
      {
        name: 'workspace-dashboard',
        label: 'Dashboard',
        icon: 'dashboard',
        caption: 'Office-wide summary and quick access'
      }
    ]
  },
  {
    id: 'records',
    label: 'Records',
    icon: 'folder_open',
    children: [
      {
        name: 'workspace-records',
        label: 'Property & TD Search',
        icon: 'search',
        caption: 'Search and open property jackets'
      }
    ]
  },
  {
    id: 'operations',
    label: 'Operations',
    icon: 'insights',
    children: [
      {
        name: 'workspace-activity',
        label: 'Audit Logs',
        icon: 'fact_check',
        caption: 'Searchable audit trail of every change'
      }
    ]
  },
  {
    id: 'administration',
    label: 'Administration',
    icon: 'admin_panel_settings',
    can: (user) => Boolean(user?.can_administer),
    children: [
      {
        name: 'workspace-staff',
        label: 'Staff Accounts',
        icon: 'group',
        caption: 'User access management'
      },
      {
        name: 'workspace-security',
        label: 'Security & Roles',
        icon: 'shield',
        caption: 'Roles, permissions, and login activity'
      }
    ]
  }
];

export function moduleVisible(module, user) {
  if (!user) {
    return false;
  }

  if (typeof module.can === 'function') {
    return module.can(user);
  }

  if (module.roles?.length) {
    return module.roles.includes(user.role);
  }

  return true;
}

export function visibleModules(user) {
  return workspaceModules
    .filter((module) => moduleVisible(module, user))
    .map((module) => ({
      ...module,
      children: module.children
    }));
}

export function findRouteMeta(routeName) {
  for (const module of workspaceModules) {
    const child = module.children.find((item) => item.name === routeName);

    if (child) {
      return {
        moduleLabel: module.label,
        pageLabel: child.label,
        caption: child.caption
      };
    }
  }

  return null;
}
