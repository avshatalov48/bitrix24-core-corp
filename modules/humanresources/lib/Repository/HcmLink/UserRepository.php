<?php

namespace Bitrix\HumanResources\Repository\HcmLink;

use Bitrix\HumanResources\Item\Collection\HcmLink\MappingEntityCollection;
use Bitrix\HumanResources\Item\Collection\UserCollection;
use Bitrix\HumanResources\Item\HcmLink\MappingEntity;
use Bitrix\Main\EO_User;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Search\Content;
use Bitrix\Main\UserTable;
use Bitrix\HumanResources\Contract;
use Bitrix\HumanResources\Item\User;

class UserRepository implements Contract\Repository\HcmLink\UserRepository
{
	private const AVATAR_SIZE_HEIGHT = 36;
	private const AVATAR_SIZE_WIDTH = 36;
	private const AVATAR_DEFAULT_PATH = '/bitrix/js/socialnetwork/entity-selector/src/images/default-user.svg';

	public function getMappingEntityCollectionByUserIds(
		array $userIds,
		int $limit = 20,
		int $offset = 0,
		?string $searchName = null
	): MappingEntityCollection
	{
		$collection = new MappingEntityCollection();

		if (empty($userIds))
		{
			return $collection;
		}

		$usersQuery = UserTable::query()
			->setSelect(['NAME', 'LAST_NAME', 'WORK_POSITION', 'PERSONAL_PHOTO'])
			->whereIn('ID', $userIds)
		;

		if (!empty($searchName))
		{
			$this->injectSearchQuery($usersQuery, $searchName);
		}

		$usersDb = $usersQuery->setLimit($limit)
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

	public function getUsersIdBySearch(string $searchName, array $excludeIds, int $limit = 10): UserCollection
	{
		$usersQuery = UserTable::query()
			->setSelect(['ID']);

		$users = $this->injectSearchQuery($usersQuery, $searchName)
			->whereNotIn('ID', $excludeIds)
			->setLimit($limit)
			->fetchCollection()
			->getAll();

		$result = array_map([$this, 'extractItemFromModel'], $users);

		return new UserCollection(...$result);
	}

	private function extractItemFromModel(EO_User $model): User
	{
		return new User(
			$model->getId(),
			$model->getName(),
			$model->getLastName(),
			$model->getSecondName(),
			$model->getPersonalPhoto(),
			$model->getWorkPosition(),
			$model->getPersonalGender(),
		);
	}

	private function injectSearchQuery(\Bitrix\Main\ORM\Query\Query $query, string $searchName)
	{
		$query->registerRuntimeField(
			new Reference(
				'USER_INDEX',
				\Bitrix\Main\UserIndexTable::class,
				Join::on('this.ID', 'ref.USER_ID'),
				['join_type' => 'INNER'],
			),
		)->whereMatch(
			'USER_INDEX.SEARCH_USER_CONTENT',
			\Bitrix\Main\ORM\Query\Filter\Helper::matchAgainstWildcard(
				Content::prepareStringToken($searchName), '*', 1,
			),
		);

		return $query;
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