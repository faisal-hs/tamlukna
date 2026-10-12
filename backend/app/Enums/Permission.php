<?php

namespace App\Enums;

/**
 * Every permission the platform knows. Code checks permissions, never role names.
 * Add new permissions here, map them in Role::permissions(), then re-run
 * RolesAndPermissionsSeeder.
 */
enum Permission: string
{
    // Property listings
    case PropertiesCreate = 'properties.create';
    case PropertiesManageOwn = 'properties.manage-own';
    case PropertiesViewUnpublished = 'properties.view-unpublished';
    case PropertiesModerate = 'properties.moderate';
    case PropertiesManageAny = 'properties.manage-any';

    // Platform administration (APIs arrive in Phase 2)
    case UsersViewAny = 'users.view-any';
    case UsersManage = 'users.manage';
    case RolesManage = 'roles.manage';
    case AuditLogsView = 'audit-logs.view';
}
