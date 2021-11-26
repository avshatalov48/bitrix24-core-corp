<?php

namespace Bitrix\Crm\Security\Controller;

use Bitrix\Crm;

class Company extends Base
{
	/** @var string */
	protected static $permissionEntityType = 'COMPANY';

	public function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Company;
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
			'IS_MY_COMPANY',
		];
	}

	protected function extractIsAlwaysReadableFromFields(array $fields): bool
	{
		return (isset($fields['IS_MY_COMPANY']) && $fields['IS_MY_COMPANY'] === 'Y');
	}

	protected static function getEnabledFlagOptionName(): string
	{
		return '~CRM_SECURITY_COMPANY_CONTROLLER_ENABLED';
	}
}