<?php

namespace Bitrix\HumanResources\Repository;

use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\EO_User;
use Bitrix\Main\EO_User_Collection;
use Bitrix\Main\ObjectPropertyException;
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
	public function getUserCollectionByMemberCollection(Item\Collection\NodeMemberCollection $nodeMemberCollection): Item\Collection\UserCollection
	{
		$userIds = [];

		foreach ($nodeMemberCollection as $nodeMember)
		{
			if ($nodeMember->entityType === MemberEntityType::USER && $nodeMember->entityId)
			{
				$userIds[] = $nodeMember->entityId;
			}
		}

		$usersCollection = new Item\Collection\UserCollection();
		if (!$userIds)
		{
			return $usersCollection;
		}

		$users = UserTable::query()
			->whereIn('ID', $userIds)
			->fetchCollection()
		;

		return $this->extractItemCollectionFromModelCollection($users);
	}

	private function extractItemFromModel(EO_User $model): Item\User
	{
		return new Item\User(
			$model->getId(),
			$model->getName(),
			$model->getLastName(),
			$model->getSecondName(),
			$model->getPersonalPhoto()
		);
	}

	/**
	 * @throws WrongStructureItemException
	 */
	private function extractItemCollectionFromModelCollection(
		EO_User_Collection $userCollection
	): Item\Collection\UserCollection
	{
		$models = $userCollection->getAll();
		$items = array_map([$this, 'extractItemFromModel'], $models);

		return new  Item\Collection\UserCollection(...$items);
	}
}