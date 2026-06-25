<?php

namespace App\Enums;

enum ActivityAction: string
{
    case LOGIN = 'login';
    case LOGOUT = 'logout';
    case TOKEN_REFRESH = 'token.refresh';
    case USER_REGISTERED = 'auth.register';
    case ROLE_CREATED = 'role.created';
    case ROLE_UPDATED = 'role.updated';
    case ROLE_DELETED = 'role.deleted';
    case ROLE_PERMISSIONS_SYNCED = 'role.permissions.synced';
    case USER_ROLES_SYNCED = 'user.roles.synced';
    case USER_PERMISSIONS_SYNCED = 'user.permissions.synced';

    case USER_CREATED = 'user.created';
    case USER_UPDATED = 'user.updated';
    case USER_DELETED = 'user.deleted';

    case PROFILE_UPDATED = 'profile.updated';
    case PROFILE_PASSWORD_UPDATED = 'profile.password.updated';

    case MODULE_CREATED = 'module.created';
    case MODULE_UPDATED = 'module.updated';
    case MODULE_DELETED = 'module.deleted';
    case MODULE_LAYOUT_UPDATED = 'module.layout.updated';

    case MODULE_FIELD_CREATED = 'module.field.created';
    case MODULE_FIELD_UPDATED = 'module.field.updated';
    case MODULE_FIELD_DELETED = 'module.field.deleted';

    case RECORD_CREATED = 'record.created';
    case RECORD_UPDATED = 'record.updated';
    case RECORD_DELETED = 'record.deleted';
    case RECORD_MOVED = 'record.moved';

    case RECORD_STAGE_CALLBACK_SENT = 'record.stage.callback.sent';
    case RECORD_STAGE_CALLBACK_SUCCESS = 'record.stage.callback.success';
    case RECORD_STAGE_CALLBACK_FAILED = 'record.stage.callback.failed';

    case CONNECTION_CREATED = 'connection.created';
    case CONNECTION_UPDATED = 'connection.updated';
    case CONNECTION_DELETED = 'connection.deleted';
    case CONNECTION_TESTED = 'connection.tested';

    case REPORT_CREATED = 'report.created';
    case REPORT_UPDATED = 'report.updated';
    case REPORT_DELETED = 'report.deleted';

    case RECORD_NOTE_UPDATED = 'record.note.updated';
}
