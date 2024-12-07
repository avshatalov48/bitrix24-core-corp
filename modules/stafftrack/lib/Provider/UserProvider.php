<?php

namespace Bitrix\StaffTrack\Provider;

use Bitrix\Main;
use Bitrix\Stafftrack\Integration\HumanResources\Structure;
use Bitrix\StaffTrack\Item\Collection\UserCollection;
use Bitrix\StaffTrack\Item\User;
use Bitrix\StaffTrack\Model\UserStatisticsHashTable;
use Bitrix\StaffTrack\Trait\Singleton;

class UserProvider
{
	use Singleton;

	protected const AVATAR_SIZE = 100;

	static protected array $users = [];

	public function getUsers(array $userIds): UserCollection
	{
		if (empty($userIds))
		{
			return new UserCollection([]);
		}

		$userEntities = Main\UserTable::query()
			->setSelect(['ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'PERSONAL_PHOTO', 'WORK_POSITION'])
			->whereIn('ID', $userIds)
			->fetchCollection()
		;

		$users = [];
		$nameFormat = Main\Application::getInstance()->getContext()->getCulture()->getNameFormat();
		foreach ($userEntities as $userEO)
		{
			$users[] = new User(
				id: $userEO->getId(),
				name: \CUser::FormatName($nameFormat, $userEO->collectValues(), false, false),
				avatar: $this->getUserAvatar($userEO->getPersonalPhoto()),
				workPosition: $userEO->getWorkPosition(),
			);
		}

		return new UserCollection($users);
	}

	public function getUser(int $userId): ?User
	{
		self::$users[$userId] ??= $this->loadUser($userId);

		return self::$users[$userId];
	}

	protected function loadUser(int $userId): ?User
	{
		$nameFormat = Main\Application::getInstance()->getContext()->getCulture()->getNameFormat();
		$userEO = Main\UserTable::query()
			->setSelect(['ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'PERSONAL_PHOTO', 'WORK_POSITION', 'UF_DEPARTMENT'])
			->where('ID', $userId)
			->fetchObject()
		;

		if (!$userEO)
		{
			return null;
		}

		$departments = Structure::getInstance()->getDepartmentsByUserId($userEO->getId());

		return new User(
			id: $userEO->getId(),
			name: \CUser::FormatName($nameFormat, $userEO->collectValues(), false, false),
			avatar: $this->getUserAvatar($userEO->getPersonalPhoto()),
			workPosition: $userEO->getWorkPosition(),
			hash: $this->getUserHash($userEO->getId()),
			departments: $departments,
			isAdmin: $this->isUserAdmin($userEO->getId()),
		);
	}

	protected function getUserAvatar(int $imageId): string
	{
		if ($imageId <= 0)
		{
			return '';
		}

		$image = \CFile::resizeImageGet(
			$imageId,
			['width' => self::AVATAR_SIZE, 'height' => self::AVATAR_SIZE],
			BX_RESIZE_IMAGE_EXACT,
		);

		return !empty($image['src']) ? $image['src'] : '';
	}

	protected function getUserHash(int $userId): string
	{
		$user = UserStatisticsHashTable::query()
			->setSelect(['USER_ID', 'HASH'])
			->where('USER_ID', $userId)
			->fetchObject()
		;

		if ($user !== null)
		{
			return $user->getHash();
		}

		$hash = bin2hex(random_bytes(4));

		UserStatisticsHashTable::add([
			'USER_ID' => $userId,
			'HASH' => $hash,
		]);

		return $hash;
	}

	public function isUserAdmin(int $userId): bool
	{
		return in_array(1, $this->getUserGroupIds($userId), true);
	}

	protected function getUserGroupIds(int $userId): array
	{
		$userGroupIds = Main\UserTable::getUserGroupIds($userId);

		return array_map('intval', $userGroupIds);
	}
}