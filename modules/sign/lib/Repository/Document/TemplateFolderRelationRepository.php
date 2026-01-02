<?php

namespace Bitrix\Sign\Repository\Document;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\Result;
use Bitrix\Sign\Internal;
use Bitrix\Sign\Internal\Document\TemplateTable;
use Bitrix\Sign\Internal\Document\Template\TemplateFolderRelationTable;
use Bitrix\Sign\Item\Document\Template\TemplateFolderRelation;
use Bitrix\Sign\Item\Document\Template\TemplateFolderRelationCollection;
use Bitrix\Sign\Result\Service\Sign\Template\CreateTemplateFolderRelationResult;
use Bitrix\Sign\Type\Template\EntityType;

class TemplateFolderRelationRepository
{
	public function add(TemplateFolderRelation $item): Result
	{
		$result = TemplateFolderRelationTable::add([
			'ENTITY_ID' => $item->entityId,
			'PARENT_ID' => $item->parentId,
			'ENTITY_TYPE' => $item->entityType->value,
			'DEPTH_LEVEL' => $item->depthLevel,
			'CREATED_BY_ID' => (int)CurrentUser::get()->getId(),
		]);

		if (!$result->isSuccess())
		{
			return (new Result())->addErrors($result->getErrors());
		}

		$item->id = $result->getId();

		return new CreateTemplateFolderRelationResult($item);
	}

	/**
	 * @param list<int> $updatableEntityIds
	 * @return Result
	 */
	public function updateParent(int $parentId, array $updatableEntityIds, EntityType $entityType): Result
	{
		if (empty($updatableEntityIds))
		{
			return new Result();
		}

		try
		{
			$depthLevel = $this->calculateDepthLevelForNewChild($parentId);

			TemplateFolderRelationTable::updateByFilter(
				['@ENTITY_ID' => $updatableEntityIds, '=ENTITY_TYPE' => $entityType->value],
				['DEPTH_LEVEL' => $depthLevel, 'PARENT_ID' => $parentId],
			);

			TemplateTable::updateByFilter(
				['@ID' => $updatableEntityIds],
				['FOLDER_ID' => $parentId],
			);
		}
		catch (ArgumentException $e)
		{
			return (new Result())->addError(new Error($e->getMessage()));
		}

		return new Result();
	}

	/**
	 * @throws ObjectNotFoundException
	 */
	private function calculateDepthLevelForNewChild(int $parentId): int
	{
		if ($parentId === 0)
		{
			return 0;
		}

		$depthLevel = $this->getByEntityIdAndType($parentId, EntityType::FOLDER)->depthLevel;
		if ($depthLevel === null)
		{
			throw new ObjectNotFoundException("Folder with ID $parentId not found");
		}

		$depthLevel++;

		return $depthLevel;
	}

	/**
	 * @param list<int> $ids
	 * @return Result
	 */
	public function deleteByIdsAndType(array $ids, EntityType $entityType): Result
	{
		if (empty($ids))
		{
			return new Result();
		}

		try
		{
			TemplateFolderRelationTable::deleteByFilter([
				'@ENTITY_ID' => $ids,
				'=ENTITY_TYPE' => $entityType->value,
			]);
		}
		catch (ArgumentException $e)
		{
			return (new Result())->addError(new Error($e->getMessage()));
		}

		return new Result();
	}

	public function deleteByIdAndType(int $id, EntityType $entityType): Result
	{
		try
		{
			TemplateFolderRelationTable::deleteByFilter([
				'=ENTITY_ID' => $id,
				'=ENTITY_TYPE' => $entityType->value,
			]);
		}
		catch (ArgumentException $e)
		{
			return (new Result())->addError(new Error($e->getMessage()));
		}

		return new Result();
	}

	public function getByParentIdAndType(int $parentId, EntityType $entityType): ?TemplateFolderRelation
	{

		$model = TemplateFolderRelationTable::query()
			->setSelect(['*'])
			->where('PARENT_ID', $parentId)
			->where('ENTITY_TYPE', $entityType->value)
			->setLimit(1)
			->fetchObject()
		;

		if ($model === null)
		{
			return null;
		}

		return $this->extractItemFromModel($model);
	}

	/**
	 * @param int $parentId
	 * @param EntityType $entityType
	 * @return TemplateFolderRelationCollection
	 */
	public function getAllByParentIdAndType(int $parentId, EntityType $entityType): TemplateFolderRelationCollection
	{
		$models = TemplateFolderRelationTable::query()
			->setSelect(['*'])
			->where('PARENT_ID', $parentId)
			->where('ENTITY_TYPE', $entityType->value)
			->fetchCollection()
		;

		return $this->extractItemCollectionFromModelCollection($models);
	}

	/**
	 * @param list<int> $parentIds
	 * @param EntityType $entityType
	 * @return TemplateFolderRelationCollection
	 */
	public function getAllByParentIdsAndType(array $parentIds, EntityType $entityType, int $limit = 1000): TemplateFolderRelationCollection
	{
		$models = TemplateFolderRelationTable::query()
			->setSelect(['*'])
			->whereIn('PARENT_ID', $parentIds)
			->where('ENTITY_TYPE', $entityType->value)
			->setLimit($limit)
			->fetchCollection()
		;

		return $this->extractItemCollectionFromModelCollection($models);
	}

	public function getByEntityIdAndType(int $entityId, EntityType $entityType): ?TemplateFolderRelation
	{
		$model = TemplateFolderRelationTable::query()
			->setSelect(['*'])
			->where('ENTITY_ID', $entityId)
			->where('ENTITY_TYPE', $entityType->value)
			->setLimit(1)
			->fetchObject()
		;

		if ($model === null)
		{
			return null;
		}

		return $this->extractItemFromModel($model);
	}

	public function getTemplateIdsByIdsMap(array $folderIds): TemplateFolderRelationCollection
	{
		if (empty($folderIds))
		{
			return new TemplateFolderRelationCollection();
		}

		$models = TemplateFolderRelationTable::query()
			->setSelect(['*'])
			->where('ENTITY_TYPE', EntityType::TEMPLATE->value)
			->whereIn('PARENT_ID', $folderIds)
			->fetchCollection();

		return $this->extractItemCollectionFromModelCollection($models);
	}

	private function extractItemFromModel(Internal\Document\Template\TemplateFolderRelation $model): TemplateFolderRelation
	{
		return new TemplateFolderRelation(
			entityId: $model->getEntityId(),
			entityType: EntityType::from($model->getEntityType()),
			createdById: $model->getCreatedById(),
			parentId: $model->getParentId(),
			depthLevel: $model->getDepthLevel(),
			id: $model->getId()
		);
	}

	private function extractItemCollectionFromModelCollection(Internal\Document\Template\TemplateFolderRelationCollection $models): TemplateFolderRelationCollection
	{
		$items = new TemplateFolderRelationCollection();

		foreach ($models as $model)
		{
			$items->add($this->extractItemFromModel($model));
		}

		return $items;
	}
}