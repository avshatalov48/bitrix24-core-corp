<?php

namespace Bitrix\Sign\Repository\Document;

use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Internal\Document\EO_Group_Query;
use Bitrix\Sign\Internal\Document\Group as GroupModel;
use Bitrix\Sign\Internal\Document\GroupCollection as GroupCollectionModel;
use Bitrix\Sign\Internal\Document\GroupTable;
use Bitrix\Sign\Item;
use Bitrix\Sign\Model\ItemBinder\BaseItemToModelBinder;
use Bitrix\Sign\Type;

final class GroupRepository
{
	private const DEFAULT_LIMIT = 100;

	public function add(Item\Document\Group $item): Result
	{
		$filledEntity = $this
			->extractModelFromItem($item)
		;

		$saveResult = $filledEntity->save();

		if (!$saveResult->isSuccess())
		{
			return (new Result())->addErrors($saveResult->getErrors());
		}

		$item->id = $saveResult->getId();
		$item->initOriginal();

		return (new Result());
	}

	public function getById(int $id): ?Item\Document\Group
	{
		if ($id < 1)
		{
			return null;
		}

		$model = GroupTable::query()
			->setSelect(['*'])
			->where('ID', $id)
			->setLimit(1)
			->fetchObject()
		;

		if ($model === null)
		{
			return null;
		}

		return $this->extractItemFromModel($model);
	}

	public function update(Item\Document\Group $item): Result
	{
		$now = new DateTime();
		$model = GroupTable::getByPrimary($item->id)->fetchObject();

		$binder = new BaseItemToModelBinder($item, $model);
		$binder->setChangedItemPropertiesToModel();
		$model->setDateModify($now);

		$saveResult = $model->save();

		if (!$saveResult->isSuccess())
		{
			return (new Result())->addErrors($saveResult->getErrors());
		}
		$item->initOriginal();

		return (new Result());
	}

	public function list(int $limit = self::DEFAULT_LIMIT): Item\Document\GroupCollection
	{
		if ($limit < 1)
		{
			$limit = self::DEFAULT_LIMIT;
		}

		$models = GroupTable::query()
			->setSelect(['*'])
			->setLimit($limit)
			->fetchCollection()
		;

		return $this->extractItemCollectionFromModelCollection($models);
	}

	private function extractModelFromItem(Item\Document\Group $item): GroupModel
	{
		return $this->getFilledModelFromItem($item, GroupTable::createObject(false));
	}

	private function getFilledModelFromItem(Item\Document\Group $item, GroupModel $model): GroupModel
	{
		return $model
			->setCreatedById($item->createdById)
			->setDateCreate($item->dateCreate)
			->setDateModify($item->dateModify)
		;
	}

	private function extractItemFromModel(GroupModel $model): Item\Document\Group
	{
		return new Item\Document\Group(
			createdById: $model->getCreatedById(),
			dateCreate: Type\DateTime::createFromMainDateTime($model->getDateCreate()),
			id: $model->getId(),
			dateModify: Type\DateTime::createFromMainDateTimeOrNull($model->getDateModify()),
		);
	}

	private function extractItemCollectionFromModelCollection(GroupCollectionModel $models): Item\Document\GroupCollection
	{
		$items = array_map($this->extractItemFromModel(...), $models->getAll());

		return new Item\Document\GroupCollection(...$items);
	}
}