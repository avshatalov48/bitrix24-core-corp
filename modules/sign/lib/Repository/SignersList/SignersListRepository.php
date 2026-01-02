<?php

namespace Bitrix\Sign\Repository\SignersList;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\Result;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Sign\Internal;
use Bitrix\Sign\Item\SignersList;
use Bitrix\Sign\Item\SignersListCollection;
use Bitrix\Sign\Item\SignersListUser;
use Bitrix\Sign\Item\SignersListUserCollection;
use Bitrix\Sign\Type\DateTime;

class SignersListRepository
{
	public function add(SignersList $list): AddResult
	{
		$model = $this->extractModelFromItem($list);
		$result = $model->save();

		if (!$result->isSuccess())
		{
			return $result;
		}

		$list->id = $result->getId();

		return $result;
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function update(SignersList $list): Result|AddResult
	{
		$model = Internal\SignersList\SignersListTable::getByPrimary($list->id)->fetchObject();

		if (!$model)
		{
			return (new Result())->addError(
				new \Bitrix\Main\Error('List not found'),
			);
		}

		return $this->getFilledModelFromItem($list, $model)->save();
	}

	/**
	 * @param int[] $listIds
	 * @throws SystemException
	 */
	public function updateModificationTime(array $listIds, int $modifyBy, DateTime $dateModify): Result
	{
		if (!$listIds)
		{
			return new Result();
		}

		Internal\SignersList\SignersListTable::updateByFilter(
			[
				'=ID' => $listIds,
			],
			[
				'DATE_MODIFY' => $dateModify,
				'MODIFIED_BY_ID' => $modifyBy,
			],
		);

		return new Result();
	}

	public function list(
		ConditionTree $filter,
		int $limit = 0,
		int $offset = 0,
	): SignersListCollection
	{
		$limit = max(0, $limit);
		$offset = max(0, $offset);

		$query = $this->prepareListQuery($filter, $limit, $offset);
		/** @var Internal\SignersList\SignersListCollection $models */
		$models = $query->fetchCollection();

		return $this->extractItemCollectionFromModelCollection($models);
	}

	public function getById(int $listId): ?SignersList
	{
		if ($listId < 1)
		{
			return null;
		}

		$model = Internal\SignersList\SignersListTable::getByPrimary($listId)->fetchObject();

		if ($model === null)
		{
			return null;
		}

		return $this->extractItemFromModel($model);
	}

	public function count(ConditionTree $filter): int
	{
		return $this->prepareListQuery($filter)->queryCountTotal();
	}

	public function delete(int $listId): Result
	{
		return Internal\SignersList\SignersListTable::delete($listId);
	}

	private function prepareListQuery(ConditionTree $filter, int $limit = 10, int $offset = 0): Query
	{
		return Internal\SignersList\SignersListTable::query()
			->setSelect(['*'])
			->setLimit($limit)
			->setOffset($offset)
			->where($filter)
			->addOrder('ID', 'DESC')
		;
	}

	private function extractItemCollectionFromModelCollection(Internal\SignersList\SignersListCollection $models): SignersListCollection
	{
		$items = array_map($this->extractItemFromModel(...), $models->getAll());

		return new SignersListCollection(...$items);
	}

	private function extractModelFromItem(SignersList $item): Internal\SignersList\SignersList
	{
		return $this->getFilledModelFromItem($item, Internal\SignersList\SignersListTable::createObject(false));
	}

	private function getFilledModelFromItem(SignersList $item, Internal\SignersList\SignersList $model): Internal\SignersList\SignersList
	{
		return $model
			->setTitle($item->title)
			->setDateCreate($item->dateCreate)
			->setDateModify($item->dateModify)
			->setCreatedById($item->createdById)
			->setModifiedById($item->modifiedById)
		;
	}

	private function extractItemFromModel(Internal\SignersList\SignersList $model): SignersList
	{
		return new SignersList(
			id: $model->getId(),
			title: $model->getTitle(),
			createdById: $model->getCreatedById(),
			modifiedById: $model->getModifiedById(),
			dateCreate: DateTime::createFromMainDateTime($model->getDateCreate()),
			dateModify: DateTime::createFromMainDateTimeOrNull($model->getDateModify()),
		);
	}
}
