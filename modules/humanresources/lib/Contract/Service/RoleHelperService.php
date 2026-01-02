<?php

namespace Bitrix\HumanResources\Contract\Service;

use Bitrix\HumanResources\Item;

interface RoleHelperService
{
	public function getById(int $roleId): ?Item\Role;
	public function getEmployeeRoleId(): ?int;
	public function getHeadRoleId(): ?int;
	public function getDeputyRoleId(): ?int;
	public function getTeamHeadRoleId(): ?int;
	public function getTeamDeputyRoleId(): ?int;
	public function getTeamEmployeeRoleId(): ?int;
	public function getAllRoleCollectionForSync(): Item\Collection\RoleCollection;
	public function getAccessCodeByRoleXmlId(string $xmlId): ?string;
}