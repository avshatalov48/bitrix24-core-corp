<?php

namespace Bitrix\Crm\Agent\Security\Service;

use Bitrix\Crm\Security\Role\Model\EO_Role_Collection;
use Bitrix\Crm\Security\Role\Model\RoleTable;
use Bitrix\Crm\Security\Role\Model\RolePermissionTable;
use Bitrix\Crm\Security\Role\Model\EO_RolePermission_Collection;

final class RoleCollectionSeparator
{
	/**
	 * @var EO_Role_Collection $roleCollection
	 * @var RoleSeparator[] $separators
	 */
	public function __construct(
		private readonly EO_Role_Collection $roleCollection,
		private readonly array $separators,
	)
	{
	}

	public function separate(): RoleCollectionSeparateResult
	{
		$result = new RoleCollectionSeparateResult();

		foreach ($this->roleCollection as $role)
		{
			foreach ($this->separators as $separator)
			{
				$separateResult = $separator->separate($role);
				$result->addPermissionsToRemove($separateResult->getPermissionsToRemove());

				if ($separateResult->getSeparatedRole() !== null)
				{
					$result
						->addSeparatedRole($separateResult->getSeparatedRole())
						->addChangedRole($role)
					;
				}
			}
		}

		return $result;
	}
}
