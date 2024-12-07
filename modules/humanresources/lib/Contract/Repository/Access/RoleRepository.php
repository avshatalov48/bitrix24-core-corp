<?php

namespace Bitrix\HumanResources\Contract\Repository\Access;

use Bitrix\HumanResources\Model\Access\AccessRoleTable;
use Bitrix\Main\Entity\DeleteResult;
use Bitrix\Main\Entity\UpdateResult;
use Bitrix\Main\ORM\Data\AddResult;

interface RoleRepository
{
	public function getRoleList(): array;

	public function create(string $roleName): AddResult;
	public function delete(int $roleId): DeleteResult;

	public function updateName(int $roleId, string $name): UpdateResult;

	public function getRoleObjectByName(string $name);

	public function areRolesDefined(): bool;
}