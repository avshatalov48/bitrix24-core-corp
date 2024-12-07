<?php

namespace Bitrix\Sign\Repository\Blank;

use Bitrix\Main;
use Bitrix\Sign\Internal;
use Bitrix\Sign\Item;

class ResourceRepository
{
	public function list(int $blankId): Item\Blank\ResourceCollection
	{
		$query = Internal\Blank\ResourceTable
			::query()
			->addSelect('*')
			->where('BLANK_ID', $blankId)
		;
		$items = array_map(
			fn(Internal\Blank\Resource $model) => $this->extractItemFromModel($model),
			$query->fetchCollection()->getAll()
		);

		return new Item\Blank\ResourceCollection(...$items);
	}

	public function getOne(int $id): ?Item\Blank\Resource
	{
		$query = Internal\Blank\ResourceTable
			::query()
			->addSelect('*')
			->where('ID', $id)
			->setLimit(1)
		;
		$model = $query->fetchObject();

		return $model ? $this->extractItemFromModel($model) : null;
	}

	public function getFirstByBlankId(int $blankId): ?Item\Blank\Resource
	{
		$query = Internal\Blank\ResourceTable
			::query()
			->addSelect('*')
			->where('BLANK_ID', $blankId)
			->addOrder('ID')
			->setLimit(1)
		;
		$model = $query->fetchObject();

		return $model ? $this->extractItemFromModel($model) : null;
	}

	public function add(Item\Blank\Resource $item): Main\Result
	{
		$result = new Main\Result();
		$saveResult = $this->extractModelFromItem($item)->save();
		if ($saveResult->isSuccess() === false)
		{
			return $result->addErrors($saveResult->getErrors());
		}
		$item->id = $saveResult->getId();

		return $result->setData(['resource' => $item]);
	}

	public function deleteById(int $id): Main\Entity\DeleteResult
	{
		return Internal\Blank\ResourceTable::delete($id);
	}

	private function extractItemFromModel(Internal\Blank\Resource $model): Item\Blank\Resource
	{
		return new Item\Blank\Resource(
			$model->getId(),
			$model->getBlankId(),
			$model->getFileId(),
		);
	}

	private function extractModelFromItem(Item\Blank\Resource $item): Internal\Blank\Resource
	{
		$model = Internal\Blank\ResourceTable::createObject()
			->setBlankId($item->blankId)
			->setFileId($item->fileId)
		;

		if ($item->id)
		{
			$model->setId($item->id);
		}

		return $model;
	}
}
