<?php

namespace Bitrix\Extranet\Contract\Repository;

use Bitrix\Extranet\Entity;
use Bitrix\Extranet\Entity\Collection\ExtranetUserCollection;
use Bitrix\Main\Result;
use Bitrix\Extranet\Enum;

interface ExtranetUserRepository
{
	public function getByUserId(int $userId): ?Entity\ExtranetUser;

	public function getById(int $id): ?Entity\ExtranetUser;

	public function upsert(Entity\ExtranetUser $extranetUser): Result;

	public function add(Entity\ExtranetUser $extranetUser): Result;

	public function update(Entity\ExtranetUser $extranetUser): Result;

	public function getAll(int $limit = 100): ExtranetUserCollection;

	public function getAllByRole(Enum\User\ExtranetRole $role, int $limit = 100): ExtranetUserCollection;

	public function getAllUserIdsByRole(Enum\User\ExtranetRole $role): array;

	/**
	 * @param Enum\User\ExtranetRole[] $roles
	 */
	public function getAllUserIdsByRoles(array $roles): array;

	public function getAllUserIds(): array;

	public function deleteById(int $id): Result;

	public function deleteByUserId(int $userId): Result;
}
