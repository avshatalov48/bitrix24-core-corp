<?php

namespace Bitrix\Sign\Repository;

use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Result;
use Bitrix\Sign\Item;
use Bitrix\Sign\Internal;

class FieldValueRepository
{
	public function add(Item\Field\FieldValue $item): Result
	{
		$now = new DateTime();
		$filledEntity = $this
			->extractModelFromItem($item)
			->setDateCreate($now)
			->setDateModify($now)
		;

		$saveResult = $filledEntity->save();

		if (!$saveResult->isSuccess())
		{
			return (new Result())->addErrors($saveResult->getErrors());
		}

		$item->id = $saveResult->getId();

		return new Result();
	}

	private function extractItemFromModel(Internal\FieldValue\FieldValue $model): Item\Field\FieldValue
	{
		return new Item\Field\FieldValue(
			fieldName: $model->getFieldName(),
			memberId: $model->getMemberId(),
			value: $model->getValue(),
			id: $model->getId(),
		);
	}

	private function extractModelFromItem(Item\Field\FieldValue $item): Internal\FieldValue\FieldValue
	{
		return $this->getFilledModelFromItem($item);
	}

	private function extractItemCollectionFromModelCollection(
		Internal\FieldValue\FieldValueCollection $modelCollection,
	): Item\Field\FieldValueCollection
	{
		$items = array_map(
			fn(Internal\FieldValue\FieldValue $value) => $this->extractItemFromModel($value),
			$modelCollection->getAll(),
		);

		return new Item\Field\FieldValueCollection(...$items);
	}

	private function getFilledModelFromItem(Item\Field\FieldValue $item): Internal\FieldValue\FieldValue
	{
		$model = Internal\FieldValue\FieldValueTable::createObject(true);

		return $model
			->setFieldName($item->fieldName)
			->setMemberId($item->memberId)
			->setValue($item->value)
		;
	}

	public function listByMemberIds(array $memberIds): Item\Field\FieldValueCollection
	{
		if (empty($memberIds))
		{
			return new Item\Field\FieldValueCollection();
		}

		$models = Internal\FieldValue\FieldValueTable::query()
			->whereIn('MEMBER_ID', $memberIds)
			->setSelect(['*'])
			->fetchCollection()
		;

		return $this->extractItemCollectionFromModelCollection($models);
	}

	public function deleteAllByMemberId(int $memberId): void
	{
		Internal\FieldValue\FieldValueTable::deleteByFilter([
			'=MEMBER_ID' => $memberId,
		]);
	}

}