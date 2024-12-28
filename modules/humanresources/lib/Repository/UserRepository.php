<?php

namespace Bitrix\HumanResources\Repository;

use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Item\Collection\UserCollection;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Item\User;
use Bitrix\HumanResources\Model\NodeMemberTable;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\EO_User;
use Bitrix\Main\EO_User_Collection;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Search\Content;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;
use Bitrix\HumanreSources\Item;
use Bitrix\HumanResources\Contract;

final class UserRepository implements Contract\Repository\UserRepository
{
	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getById(int $userId): ?Item\User
	{
		$model = UserTable::getById($userId)->fetchObject();

		return $model !== null ? $this->extractItemFromModel($model) : null;
	}

	/**
	 * @throws WrongStructureItemException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getUserCollectionByMemberCollection(
		Item\Collection\NodeMemberCollection $nodeMemberCollection,
	): Item\Collection\UserCollection
	{
		static $cachedUsers = [];

		$userIds = array_filter(
			array_map(
				fn($nodeMember) => $nodeMember->entityType === MemberEntityType::USER ? $nodeMember->entityId : null,
				iterator_to_array($nodeMemberCollection),
			),
		);

		$usersCollection = new Item\Collection\UserCollection();

		if (empty($userIds))
		{
			return $usersCollection;
		}

		$userIds = array_flip($userIds);

		foreach ($cachedUsers as $userId => $cachedUser)
		{
			if (empty($userIds))
			{
				break;
			}

			if (isset($userIds[$userId]))
			{
				$usersCollection->add($cachedUser);
				unset($userIds[$userId]);
			}
		}

		if (empty($userIds))
		{
			return $usersCollection;
		}
		$select = [
			'ID',
			'NAME',
			'LAST_NAME',
			'SECOND_NAME',
			'PERSONAL_PHOTO',
			'WORK_POSITION',
			'PERSONAL_GENDER',
		];

		$eoUserCollection = UserTable::query()
			->setSelect($select)
			->whereIn('ID', array_keys($userIds))
			->fetchCollection()
		;

		foreach ($eoUserCollection->getAll() as $model)
		{
			$user = $this->extractItemFromModel($model);
			$cachedUsers[$model->getId()] = $user;
			$usersCollection->add($user);
		}

		return $usersCollection;
	}

	private function extractItemFromModel(EO_User $model): Item\User
	{
		return new Item\User(
			$model->getId(),
			$model->getName(),
			$model->getLastName(),
			$model->getSecondName(),
			$model->getPersonalPhoto(),
			$model->getWorkPosition(),
			$model->getPersonalGender(),
		);
	}

	public function getByIds(array $userIds): UserCollection
	{
		$usersCollection = new Item\Collection\UserCollection();
		if (empty($userIds))
		{
			return $usersCollection;
		}

		$users = UserTable::query()
			->whereIn('ID', $userIds)
			->fetchCollection()
		;

		return $this->extractItemCollectionFromModelCollection($users);
	}

	private function extractItemCollectionFromModelCollection(
		EO_User_Collection $userCollection
	): Item\Collection\UserCollection
	{
		$models = $userCollection->getAll();
		$items = array_map([$this, 'extractItemFromModel'], $models);

		return new Item\Collection\UserCollection(...$items);
	}

	public function findByNodeAndSearchQuery(Node $node, string $searchQuery): UserCollection
	{
		$selectFields = [
			'ID',
			'ACTIVE',
			'LAST_NAME',
			'NAME',
			'SECOND_NAME',
			'LOGIN',
			'EMAIL',
			'TITLE',
			'PERSONAL_GENDER',
			'PERSONAL_PHOTO',
			'WORK_POSITION',
			'CONFIRM_CODE',
			'EXTERNAL_AUTH_ID',
		];

		$ormQuery = UserTable::query();
		$ormQuery->setSelect(array_unique($selectFields));

		$ormQuery->registerRuntimeField(
			new Reference(
				'USER_INDEX',
				\Bitrix\Main\UserIndexTable::class,
				Join::on('this.ID', 'ref.USER_ID'),
				['join_type' => 'INNER'],
			),
		)->registerRuntimeField(
			new Reference(
				'NODE_MEMBER',
				NodeMemberTable::class,
				Join::on('this.ID', 'ref.ENTITY_ID')
				->where('ref.ENTITY_TYPE', MemberEntityType::USER->value)
				->where('ref.NODE_ID', $node->id),
				['join_type' => 'INNER'],
			),
		)->where('ACTIVE', 'Y')
		;

		$ormQuery->whereMatch(
			'USER_INDEX.SEARCH_USER_CONTENT',
			\Bitrix\Main\ORM\Query\Filter\Helper::matchAgainstWildcard(
				Content::prepareStringToken($searchQuery), '*', 1,
			),
		);

		return $this->extractItemCollectionFromModelCollection($ormQuery->fetchCollection());
	}
}