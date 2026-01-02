<?php

namespace Bitrix\Crm\Security\Role;

class RoleRelationHelper
{
	public function getAdminGroupRelationAccessCode(): string
	{
		return 'G1';
	}

	public function getAllUsersGroupRelationAccessCode(): ?string
	{

		if (defined('WIZARD_EMPLOYEES_GROUP') && WIZARD_EMPLOYEES_GROUP > 0)
		{
			return 'G' . WIZARD_EMPLOYEES_GROUP;
		}

		$employeesGroupId = $this->getEmployeesGroupId();
		if ($employeesGroupId)
		{
			return 'G' . $employeesGroupId;
		}

		return null;
	}

	private function getEmployeesGroupId(): int
	{
		static $employeesGroupId = null;
		if (is_null($employeesGroupId))
		{
			$employeesGroupId = (int)\CGroup::GetIDByCode('EMPLOYEES_' . \Bitrix\Main\SiteTable::getDefaultSiteId());
		}

		return $employeesGroupId;
	}
}