<?php

namespace Bitrix\HumanResources\Contract\Repository\Access;

interface RoleRelationRepository
{
	public function getRolesByRelationCodes(array $relationCode): array;

	public function deleteRelationsByRoleId(int $roleId): \Bitrix\Main\DB\Result;

	public function getRelationList(array $parameters = []): array;
}