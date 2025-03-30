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
		$model = UserTable::getById($userId)->fetch();

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
			$nodeMemberCollection->map(
				fn($nodeMember) => $nodeMember->entityType === MemberEntityType::USER ? $nodeMember->entityId : null,
			),
		);

		$usersCollection = new Item\Collection\UserCollection();

		if (empty($userIds))
		{
			return $usersCollection;
		}

		$userIds = array_flip($userIds);

		foreach ($userIds as $index => $userId)
		{
			if (isset($cachedUsers[$index]))
			{
				$usersCollection->add($cachedUsers[$index]);
				unset($userIds[$index]);
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
			'CONFIRM_CODE',
			'ACTIVE',
		];

		$eoUserCollection = UserTable::query()
			->setSelect($select)
			->whereIn('ID', array_keys($userIds))
			->exec()
		;

		while ($model = $eoUserCollection->fetch())
		{
			$user = $this->extractItemFromModel($model);
			$cachedUsers[$model['ID']] = $user;
			$usersCollection->add($user);
		}

		return $usersCollection;
	}

	private function extractItemFromModel(array $model): Item\User
	{
		return new Item\User(
			$model['ID'],
			$model['NAME'],
			$model['LAST_NAME'],
			$model['SECOND_NAME'],
			$model['PERSONAL_PHOTO'],
			$model['WORK_POSITION'],
			$model['PERSONAL_GENDER'],
			active: $model['ACTIVE'] === 'Y',
			hasConfirmCode: $model['CONFIRM_CODE'] !== null && $model['CONFIRM_CODE'] !== '',
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
			->fetchAll()
		;

		return $this->extractItemCollectionFromModelCollection($users);
	}

	private function extractItemCollectionFromModelCollection(
		array $items
	): Item\Collection\UserCollection
	{
		$items = array_map([$this, 'extractItemFromModel'], $items);

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

		return $this->extractItemCollectionFromModelCollection($ormQuery->fetchAll());
	}
}