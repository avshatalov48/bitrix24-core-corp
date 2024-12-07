<?php

namespace Bitrix\HumanResources\Contract\Repository;

use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Item\Collection\RoleCollection;

interface RoleRepository
{
	public function create(Item\Role $role): Item\Role;

	public function update(Item\Role $role): void;

	public function remove(Item\Role $role): void;

	public function list(int $limit = 50, int $offset = 0): Item\Collection\RoleCollection;

	public function findByXmlId(string $xmlId): ?Item\Role;

	/**
	 * @param array<int> $ids
	 * @return RoleCollection
	 */
	public function findByIds(array $ids): Item\Collection\RoleCollection;

	public function getById(int $id): ?Item\Role;
}