<?php

namespace Bitrix\Crm\Security\Controller;

use Bitrix\Crm;

class Contact extends Base
{
	/** @var string */
	protected static $permissionEntityType = 'CONTACT';

	public function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Contact;
	}

	public function isPermissionEntityTypeSupported($entityType): bool
	{
		return $entityType === self::$permissionEntityType;
	}

	protected function getSelectFields(): array
	{
		return [
			'ID',
			'ASSIGNED_BY_ID',
			'OPENED',
		];
	}

	protected static function getEnabledFlagOptionName(): string
	{
		return '~CRM_SECURITY_CONTACT_CONTROLLER_ENABLED';
	}
}