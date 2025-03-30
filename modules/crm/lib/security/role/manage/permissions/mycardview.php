<?php

namespace Bitrix\Crm\Security\Role\Manage\Permissions;

use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlMapper\BaseControlMapper;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlMapper\Toggler;

class MyCardView extends Permission
{
	public const CODE = 'MYCARDVIEW';

    public function code(): string
    {
        return self::CODE;
    }

    public function name(): string
    {
        return GetMessage('CRM_SECURITY_ROLE_PERMS_HEAD_MYCARDVIEW_MSGVER_1');
    }

    public function canAssignPermissionToStages(): bool
	{
		return false;
	}

	public function getDefaultAttribute(): ?string
	{
		return \Bitrix\Crm\Service\UserPermissions::PERMISSION_NONE;
	}

	protected function createDefaultControlMapper(): BaseControlMapper
	{
		return new Toggler();
	}

	public function getDeputyDefaultAttributeValue(): ?string
	{
		return \Bitrix\Crm\Service\UserPermissions::PERMISSION_ALL;
	}
}
