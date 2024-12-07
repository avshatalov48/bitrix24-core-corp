<?php

namespace Bitrix\Sign\Repository;

use Bitrix\Main;
use Bitrix\Sign\Internal;
use Bitrix\Sign\Item;

class EntityFileRepository
{
	public function list(int $entityTypeId, int $entityId): Item\EntityFileCollection
	{
		$models = Internal\FileTable
			::query()
			->addSelect('*')
			->where('ENTITY_ID', $entityId)
			->where('ENTITY_TYPE_ID', $entityTypeId)
		;
		return $this->extractItemCollectionFromModelCollection($models->fetchCollection());
	}

	public function getOne(int $entityTypeId, int $entityId, int $code): ?Item\EntityFile
	{
		$models = Internal\FileTable
			::query()
			->addSelect('*')
			->where('ENTITY_ID', $entityId)
			->where('ENTITY_TYPE_ID', $entityTypeId)
			->where('CODE', $code)
			->setLimit(1)
		;
		$model = $models->fetchObject();
		return $model ? $this->extractItemFromModel($model) : null;
	}

	/**
	 * @param \Bitrix\Sign\Item\EntityFile $item
	 *
	 * @return \Bitrix\Main\Result
	 */
	public function add(Item\EntityFile $item): Main\Result
	{
		$filledMemberEntity = $this->extractModelFromItem($item);

		$saveResult = $filledMemberEntity->save();

		if (!$saveResult->isSuccess())
		{
			return (new Main\Result())->addErrors($saveResult->getErrors());
		}

		$item->id = $saveResult->getId();

		return (new Main\Result())->setData(['file' => $item]);
	}

	public function deleteById(int $id): void
	{
		Internal\FileTable::delete($id);
	}

	/**
	 * @param \Bitrix\Sign\Internal\File|null $model
	 *
	 * @return \Bitrix\Sign\Item\EntityFile
	 */
	private function extractItemFromModel(Internal\File $model): Item\EntityFile
	{
		return new Item\EntityFile(
			id: $model->getId(),
			entityTypeId: $model->getEntityTypeId(),
			entityId: $model->getEntityId(),
			code: $model->getCode(),
			fileId: $model->getFileId(),
		);
	}

	/**
	 * @param \Bitrix\Sign\Item\EntityFile $item
	 *
	 * @return \Bitrix\Sign\Internal\File
	 */
	private function extractModelFromItem(Item\EntityFile $item): Internal\File
	{
		return $this->getFilledModelFromItem($item);
	}

	/**
	 * @param \Bitrix\Sign\Internal\FileCollection $fileCollection
	 *
	 * @return \Bitrix\Sign\Item\EntityFileCollection
	 */
	private function extractItemCollectionFromModelCollection(Internal\FileCollection $fileCollection): Item\EntityFileCollection
	{
		$models = $fileCollection->getAll();
		$items = array_map([$this, 'extractItemFromModel'], $models);
		return new Item\EntityFileCollection(...$items);
	}

	/**
	 * @param \Bitrix\Sign\Item\EntityFile $item
	 *
	 * @return \Bitrix\Sign\Internal\File
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getFilledModelFromItem(Item\EntityFile $item): Internal\File
	{
		$model = Internal\FileTable::createObject(true);

		if ($item->id)
		{
			$model->setId($item->id);
		}

		return $model
			->setEntityId($item->entityId)
			->setEntityTypeId($item->entityTypeId)
			->setCode($item->code)
			->setFileId($item->fileId)
		;
	}
}
