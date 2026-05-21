/**
 * Sidebar module definitions. Each child maps to a vue-router route name.
 * Visibility uses role lists and/or can(user) callbacks aligned with API permissions.
 */

export const workspaceModules = [
  {
    id: 'dashboard',
    label: 'Dashboard',
    icon: 'dashboard',
    children: [
      {
        name: 'workspace-dashboard',
        label: 'Overview',
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
    id: 'digitization',
    label: 'Digitization',
    icon: 'scanner',
    children: [
      {
        name: 'workspace-digitize',
        label: 'Scanning Queue',
        icon: 'inventory_2',
        caption: 'Physical records awaiting scan'
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
        label: 'System Activity',
        icon: 'history',
        caption: 'Recent actions across records'
      }
    ]
  },
  {
    id: 'data',
    label: 'Data',
    icon: 'cloud_upload',
    roles: ['admin', 'assessor'],
    children: [
      {
        name: 'workspace-import',
        label: 'Bulk Import',
        icon: 'upload_file',
        caption: 'CSV property and TD import'
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
