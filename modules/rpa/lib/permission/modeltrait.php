<?php

namespace Bitrix\Rpa\Permission;

use Bitrix\Rpa\Model\PermissionTable;
use Bitrix\Rpa\UserPermissions;

trait ModelTrait
{
	protected $permissions;

	public function getPermissions(bool $isFromCache = true): array
	{
		if($this->permissions === null || !$isFromCache)
		{
			$this->permissions = PermissionTable::getList([
				'filter' => [
					'=ENTITY' => $this->getPermissionEntity(),
					'=ENTITY_ID' => $this->getId(),
				]
			])->fetchAll();
		}

		return $this->permissions;
	}

	protected function getUserCodesForAction(string $action): array
	{
		$result = [];
		foreach($this->getPermissions() as $permission)
		{
			if($permission['ACTION'] === $action && $permission['PERMISSION'] > UserPermissions::PERMISSION_NONE)
			{
				$result[] = $permission['ACCESS_CODE'];
			}
		}

		return $result;
	}

	abstract public function getPermissionEntity(): string;
}