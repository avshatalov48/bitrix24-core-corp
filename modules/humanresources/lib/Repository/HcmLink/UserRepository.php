<?php

namespace Bitrix\HumanResources\Repository\HcmLink;

use Bitrix\HumanResources\Item\Collection\HcmLink\MappingEntityCollection;
use Bitrix\HumanResources\Item\HcmLink\MappingEntity;
use Bitrix\Main\UserTable;
use Bitrix\HumanResources\Contract;

class UserRepository implements Contract\Repository\HcmLink\UserRepository
{
	private const AVATAR_SIZE_HEIGHT = 36;
	private const AVATAR_SIZE_WIDTH = 36;
	private const AVATAR_DEFAULT_PATH = '/bitrix/js/socialnetwork/entity-selector/src/images/default-user.svg';

	public function getMappingEntityCollectionByUserIds(array $userIds, int $limit = 20, int $offset = 0): MappingEntityCollection
	{
		$collection = new MappingEntityCollection();

		if (empty($userIds))
		{
			return $collection;
		}

		$usersDb = UserTable::query()
			->setSelect(['NAME', 'LAST_NAME', 'WORK_POSITION', 'PERSONAL_PHOTO'])
			->whereIn('ID', $userIds)
			->setLimit($limit)
			->setOffset($offset)
			->fetchCollection()
			->getAll()
		;

		foreach ($usersDb as $user)
		{
			$collection->add(new MappingEntity(
				id: $user->getId(),
				name: $user->getName() . ' ' . $user->getLastName(),
				avatarLink: $this->getAvatarLink($user->getPersonalPhoto()),
				position: $user->getWorkPosition(),
			));
		}

		return $collection;
	}

	private function getAvatarLink(int $imageId): mixed
	{
		$avatarLink = self::AVATAR_DEFAULT_PATH;

		if ($imageId > 0)
		{
			$image = \CFile::resizeImageGet(
				$imageId,
				[
					'width' => self::AVATAR_SIZE_WIDTH,
					'height' => self::AVATAR_SIZE_HEIGHT,
				],
				BX_RESIZE_IMAGE_EXACT,
			);

			$avatarLink = !empty($image['src'])
				? $image['src']
				: self::AVATAR_DEFAULT_PATH
			;
		}

		return $avatarLink;
	}
}