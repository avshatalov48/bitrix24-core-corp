<?php

namespace Bitrix\Sign\Repository\SignersList;

use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Result;
use Bitrix\Sign\Internal;
use Bitrix\Sign\Item\SignersListUser;
use Bitrix\Sign\Item\SignersListUserCollection;

class SignersListUserRepository
{
	public function list(
		ConditionTree $filter,
		int $limit = 0,
		int $offset = 0,
	): SignersListUserCollection
	{
		$limit = max(0, $limit);
		$offset = max(0, $offset);

		$query = $this->prepareUserListQuery($filter, $limit, $offset);
		/** @var Internal\SignersList\SignersListUserCollection $models */
		$models = $query->fetchCollection();

		return $this->extractUserItemCollectionFromModelCollection($models);
	}

	public function count(ConditionTree $filter): int
	{
		return $this->prepareUserListQuery($filter)->queryCountTotal();
	}

	public function add(SignersListUserCollection $signers): Result
	{
		if ($signers->count() === 0)
		{
			return new Result();
		}

		return $this->extractUserItemModelCollectionFromItemCollection($signers)->save();
	}

	/**
	 * @param int[] $userIds
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function deleteSignersFromList(int $listId, array $userIds): Result
	{
		if (!$userIds)
		{
			return new Result();
		}

		Internal\SignersList\SignersListUserTable::deleteByFilter([
			'=LIST_ID' => $listId,
			'=USER_ID' => $userIds,
		]);

		return new Result();
	}

	/**
	 * @param int[] $listIds
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function deleteSignerFromLists(int $userId, array $listIds): Result
	{
		if (!$listIds)
		{
			return new Result();
		}

		Internal\SignersList\SignersListUserTable::deleteByFilter([
			'=USER_ID' => $userId,
			'=LIST_ID' => $listIds,
		]);

		return new Result();
	}

	private function prepareUserListQuery(ConditionTree $filter, int $limit = 10, int $offset = 0): Query
	{
		return Internal\SignersList\SignersListUserTable::query()
			->setSelect(['*'])
			->setLimit($limit)
			->setOffset($offset)
			->where($filter)
		;
	}

	private function extractUserItemCollectionFromModelCollection(Internal\SignersList\SignersListUserCollection $models): SignersListUserCollection
	{
		$items = array_map($this->extractUserItemFromModel(...), $models->getAll());

		return new SignersListUserCollection(...$items);
	}

	private function extractUserModelFromItem(SignersListUser $item): Internal\SignersList\SignersListUser
	{
		return $this->getFilledUserModelFromItem($item, Internal\SignersList\SignersListUserTable::createObject(false));
	}


	private function getFilledUserModelFromItem(SignersListUser $item, Internal\SignersList\SignersListUser $model): Internal\SignersList\SignersListUser
	{
		return $model
			->setListId($item->listId)
			->setUserId($item->userId)
			->setCreatedById($item->createdById)
			->setDateCreate($item->dateCreate)
		;
	}

	private function extractUserItemFromModel(Internal\SignersList\SignersListUser $model): SignersListUser
	{
		return new SignersListUser(
			listId: $model->getListId(),
			userId: $model->getUserId(),
			createdById: $model->getCreatedById(),
			dateCreate: \Bitrix\Sign\Type\DateTime::createFromMainDateTime($model->getDateCreate()),
		);
	}

	private function extractUserItemModelCollectionFromItemCollection(SignersListUserCollection $collection): Internal\SignersList\SignersListUserCollection
	{
		$models = new Internal\SignersList\SignersListUserCollection();
		foreach ($collection as $item)
		{
			$models->add($this->extractUserModelFromItem($item));
		}
		return $models;
	}
}