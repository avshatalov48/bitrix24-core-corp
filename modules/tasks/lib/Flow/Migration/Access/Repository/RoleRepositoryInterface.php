<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Migration\Access\Repository;

interface RoleRepositoryInterface
{
	public function getList(int $startFromId, int $limit): array;

	public function setPermissionForRoleId(int $roleId): void;
}