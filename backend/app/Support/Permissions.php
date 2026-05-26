<?php

namespace App\Support;

/**
 * Single source of truth for the system's permission catalog and per-role defaults.
 * The frontend's permission editor and the backend's authorisation checks both read
 * from here, so adding a new feature only requires editing this file.
 */
class Permissions
{
    /**
     * Permission catalog grouped for the UI. Each permission has:
     *   key:   stable identifier used by checks
     *   label: human description
     *   roles: roles that get this permission by default
     */
    public const CATALOG = [
        [
            'group' => 'Property Records',
            'permissions' => [
                ['key' => 'property.view',     'label' => 'View property records and dossiers', 'roles' => ['admin', 'assessor', 'records_staff', 'viewer']],
                ['key' => 'property.create',   'label' => 'Create new property records',         'roles' => ['admin', 'assessor', 'records_staff']],
                ['key' => 'property.update',   'label' => 'Update property master data',         'roles' => ['admin', 'assessor', 'records_staff']],
                ['key' => 'property.approve',  'label' => 'Approve property records',            'roles' => ['admin', 'assessor']],
                ['key' => 'property.delete',   'label' => 'Permanently delete property records', 'roles' => ['admin']],
            ],
        ],
        [
            'group' => 'Tax Declarations',
            'permissions' => [
                ['key' => 'td.view',    'label' => 'View tax declaration history',    'roles' => ['admin', 'assessor', 'records_staff', 'viewer']],
                ['key' => 'td.create',  'label' => 'Add new tax declarations',        'roles' => ['admin', 'assessor', 'records_staff']],
                ['key' => 'td.update',  'label' => 'Update tax declarations',         'roles' => ['admin', 'assessor', 'records_staff']],
                ['key' => 'td.approve', 'label' => 'Approve tax declarations',        'roles' => ['admin', 'assessor']],
                ['key' => 'td.cancel',  'label' => 'Cancel tax declarations',         'roles' => ['admin', 'assessor', 'records_staff']],
                ['key' => 'td.delete',  'label' => 'Permanently delete tax declarations', 'roles' => ['admin']],
            ],
        ],
        [
            'group' => 'Assessments',
            'permissions' => [
                ['key' => 'assessment.create', 'label' => 'Add assessment records (land, building, machinery)', 'roles' => ['admin', 'assessor', 'records_staff']],
                ['key' => 'assessment.update', 'label' => 'Update assessment records',                          'roles' => ['admin', 'assessor', 'records_staff']],
                ['key' => 'assessment.delete', 'label' => 'Remove assessment records',                          'roles' => ['admin']],
            ],
        ],
        [
            'group' => 'Documents & Physical Records',
            'permissions' => [
                ['key' => 'document.upload',   'label' => 'Upload and register documents',           'roles' => ['admin', 'assessor', 'records_staff']],
                ['key' => 'document.update',   'label' => 'Update document metadata',                'roles' => ['admin', 'assessor', 'records_staff']],
                ['key' => 'document.digitize', 'label' => 'Digitize physical files (scan + OCR)',    'roles' => ['admin', 'assessor', 'records_staff']],
                ['key' => 'document.move',     'label' => 'Record physical record movements',        'roles' => ['admin', 'assessor', 'records_staff']],
                ['key' => 'document.delete',   'label' => 'Archive / delete documents',              'roles' => ['admin']],
            ],
        ],
        [
            'group' => 'Imports & Exports',
            'permissions' => [
                ['key' => 'export.csv',     'label' => 'Export property records to CSV',     'roles' => ['admin', 'assessor', 'records_staff', 'viewer']],
                ['key' => 'export.dossier', 'label' => 'Export property dossier (PDF/HTML)', 'roles' => ['admin', 'assessor', 'records_staff', 'viewer']],
                ['key' => 'backup.export',  'label' => 'Download full system backup',        'roles' => ['admin', 'assessor']],
                ['key' => 'import.bulk',    'label' => 'Bulk import properties from CSV',    'roles' => ['admin', 'assessor']],
            ],
        ],
        [
            'group' => 'Audit & Security',
            'permissions' => [
                ['key' => 'audit.view',             'label' => 'View audit log feed',                'roles' => ['admin', 'assessor', 'records_staff']],
                ['key' => 'audit.export',           'label' => 'Export audit log to CSV',            'roles' => ['admin', 'assessor', 'records_staff']],
                ['key' => 'security.manage',        'label' => 'Open Security & Access module',     'roles' => ['admin']],
                ['key' => 'security.login_activity','label' => 'View login activity log',            'roles' => ['admin']],
            ],
        ],
        [
            'group' => 'Administration',
            'permissions' => [
                ['key' => 'user.view',       'label' => 'View user accounts',                              'roles' => ['admin']],
                ['key' => 'user.create',     'label' => 'Create user accounts',                            'roles' => ['admin']],
                ['key' => 'user.update',     'label' => 'Update user accounts (role, status, password)',  'roles' => ['admin']],
                ['key' => 'user.deactivate', 'label' => 'Deactivate user accounts',                        'roles' => ['admin']],
                ['key' => 'user.permissions','label' => 'Modify per-user permission overrides',           'roles' => ['admin']],
            ],
        ],
    ];

    /** Tones drive the UI palette per role. */
    public const ROLE_TONES = [
        'admin' => 'navy',
        'assessor' => 'blue',
        'records_staff' => 'steel',
        'viewer' => 'slate',
    ];

    public const ROLE_LABELS = [
        'admin' => 'Administrator',
        'assessor' => 'Assessor',
        'records_staff' => 'Records Staff',
        'viewer' => 'Viewer',
    ];

    public const ROLE_DESCRIPTIONS = [
        'admin' => 'Full access. Manage users, delete records, approve declarations.',
        'assessor' => 'Approve tax declarations and edit any property record.',
        'records_staff' => 'Encode and update properties, declarations, documents, and physical movements.',
        'viewer' => 'Read-only access to all property records and dossiers.',
    ];

    /** Flattened list of every permission key. */
    public static function allKeys(): array
    {
        $keys = [];
        foreach (self::CATALOG as $group) {
            foreach ($group['permissions'] as $permission) {
                $keys[] = $permission['key'];
            }
        }
        return $keys;
    }

    /** Default permission keys for a given role. */
    public static function defaultsForRole(?string $role): array
    {
        if (! $role) {
            return [];
        }

        $keys = [];
        foreach (self::CATALOG as $group) {
            foreach ($group['permissions'] as $permission) {
                if (in_array($role, $permission['roles'], true)) {
                    $keys[] = $permission['key'];
                }
            }
        }
        return $keys;
    }

    /**
     * Compute the effective permission set: role defaults + grants - denies.
     */
    public static function effective(?string $role, array $grants = [], array $denies = []): array
    {
        $defaults = self::defaultsForRole($role);
        $merged = array_unique(array_merge($defaults, array_filter($grants)));
        $deniesSet = array_filter($denies);

        return array_values(array_diff($merged, $deniesSet));
    }

    /** Roles in the order admins should see them. */
    public static function roles(): array
    {
        return [
            [
                'value' => 'admin',
                'label' => self::ROLE_LABELS['admin'],
                'description' => self::ROLE_DESCRIPTIONS['admin'],
                'tone' => self::ROLE_TONES['admin'],
                'defaults' => self::defaultsForRole('admin'),
            ],
            [
                'value' => 'assessor',
                'label' => self::ROLE_LABELS['assessor'],
                'description' => self::ROLE_DESCRIPTIONS['assessor'],
                'tone' => self::ROLE_TONES['assessor'],
                'defaults' => self::defaultsForRole('assessor'),
            ],
            [
                'value' => 'records_staff',
                'label' => self::ROLE_LABELS['records_staff'],
                'description' => self::ROLE_DESCRIPTIONS['records_staff'],
                'tone' => self::ROLE_TONES['records_staff'],
                'defaults' => self::defaultsForRole('records_staff'),
            ],
            [
                'value' => 'viewer',
                'label' => self::ROLE_LABELS['viewer'],
                'description' => self::ROLE_DESCRIPTIONS['viewer'],
                'tone' => self::ROLE_TONES['viewer'],
                'defaults' => self::defaultsForRole('viewer'),
            ],
        ];
    }
}
