<?php

namespace Bitrix\Mobile\Collab\Enum;

enum CollabPermissionsEnum: string
{
    case ALL = 'all';
    case EMPLOYEES = 'employees';
    case OWNER_AND_MODERATORS = 'ownerAndModerators';
    case OWNER = 'owner';
}
