<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Main\Type\Collection;
use Bitrix\Main\UserTable;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Entity\UserCollection;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\UserMapper;
use Bitrix\Tasks\Util;
use CAllGroup;

class UserRepository implements UserRepositoryInterface
{
	public function __construct(
		private readonly FileRepositoryInterface $fileRepository,
		private readonly UserMapper $userMapper
	)
	{

	}

	public function getByIds(array $userIds): Entity\UserCollection
	{
		if (empty($userIds))
		{
			return new UserCollection();
		}

		$select = [
			'ID',
			'PERSONAL_PHOTO',
			'NAME',
			'LAST_NAME',
			'SECOND_NAME',
			'EXTERNAL_AUTH_ID',
			'UF_DEPARTMENT',
			'PERSONAL_GENDER',
			'EMAIL',
			'LOGIN',
		];

		$users = UserTable::query()
			->setSelect($select)
			->whereIn('ID', $userIds)
			->exec()
			->fetchAll();

		$fileIds = array_column($users, 'PERSONAL_PHOTO');

		Collection::normalizeArrayValuesByInt($fileIds, false);

		$files = $this->fileRepository->getByIds($fileIds);

		return $this->userMapper->mapToCollection($users, $files);
	}

	public function getAdmins(): Entity\UserCollection
	{
		$adminIds = CAllGroup::GetGroupUser(1);
		Collection::normalizeArrayValuesByInt($adminIds, false);

		return $this->userMapper->mapToCollection(
			array_map(static fn(int $adminId): array => ['ID' => $adminId], $adminIds),
		);
	}
}
