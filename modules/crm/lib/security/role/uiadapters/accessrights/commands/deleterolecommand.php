<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Commands;

use Bitrix\Crm\Security\Role\Repositories\PermissionRepository;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Result;

class DeleteRoleCommand
{
	use Singleton;

	private PermissionRepository $permissionRepository;

	public function __construct()
	{
		$this->permissionRepository = PermissionRepository::getInstance();
	}

	public function execute($roleId): Result
	{
		$this->permissionRepository->deleteRole($roleId);

		return new Result();
	}
}