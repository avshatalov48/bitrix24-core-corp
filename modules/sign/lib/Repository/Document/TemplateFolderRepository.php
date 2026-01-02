<?php

namespace Bitrix\Sign\Repository\Document;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Sign\Helper\IterationHelper;
use Bitrix\Sign\Internal\Document\TemplateFolder;
use Bitrix\Sign\Internal\Document\Template\TemplateFolderRelationTable;
use Bitrix\Sign\Internal\Document\TemplateFolderCollection;
use Bitrix\Sign\Internal\Document\TemplateFolderTable;
use Bitrix\Sign\Item;
use Bitrix\Sign\Model\ItemBinder\BaseItemToModelBinder;
use Bitrix\Sign\Result\Service\Sign\Template\UpdateTemplateFolderResult;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type;
use Bitrix\Sign\Type\Template\EntityType;
use Bitrix\Main\Security\Random;
use Bitrix\Sign\Result\Service\Sign\Template\CreateTemplateFolderResult;

class TemplateFolderRepository
{
	public function add(Item\Document\TemplateFolder $item): Result
	{
		$model = $this
			->extractModelFromItem($item)
		;

		$saveResult = $model->save();
		if (!$saveResult->isSuccess())
		{
			return (new Result())->addErrors($saveResult->getErrors());
		}

		$item->id = $saveResult->getId();
		$item->initOriginal();

		return (new CreateTemplateFolderResult($item));
	}

	public function update(Item\Document\TemplateFolder $item): Result
	{
		$item->dateModify = new Type\DateTime();
		$model = TemplateFolderTable::getByPrimary($item->id)->fetchObject();

		$binder = new BaseItemToModelBinder($item, $model);
		$binder->setChangedItemPropertiesToModel();

		$saveResult = $model->save();

		if (!$saveResult->isSuccess())
		{
			return (new Result())->addErrors($saveResult->getErrors());
		}

		$item->initOriginal();

		return (new UpdateTemplateFolderResult($item));
	}

	public function getById(int $id): ?Item\Document\TemplateFolder
	{
		$model = TemplateFolderTable::getByPrimary($id)->fetchObject();

		if ($model === null)
		{
			return null;
		}

		return $this->extractItemFromModel($model);
	}

	/**
	 * @param list<int> $ids
	 * @return Item\Document\TemplateFolderCollection
	 */
	public function getByIds(array $ids): Item\Document\TemplateFolderCollection
	{
		if (empty($ids))
		{
			return new Item\Document\TemplateFolderCollection();
		}

		$models = TemplateFolderTable::query()
			->setSelect(['*'])
			->whereIn('ID', $ids)
			->fetchCollection()
		;

		return $this->extractItemCollectionFromModelCollection($models);
	}

	public function deleteById(int $id): Result
	{
		try
		{
			$container = Container::instance();
			$templateFolderRelationRepository = $container->getTemplateFolderRelationRepository();

			$result = $templateFolderRelationRepository->deleteByIdAndType($id, EntityType::FOLDER);
			if (!$result->isSuccess())
			{
				return (new Result())->addErrors($result->getErrors());
			}

			$templatesByFolderIdQuery = TemplateFolderRelationTable::query()
				->setSelect(['ENTITY_ID'])
				->setFilter([
				'=ENTITY_TYPE' => EntityType::TEMPLATE->value,
				'=PARENT_ID' => $id
			]);

			$templatesByFolderIdQuery->exec();
			$templatesByFolderId = $templatesByFolderIdQuery->fetchCollection();

			$templateIds = [];
			foreach ($templatesByFolderId as $template)
			{

				$templateIds[] = $template->getEntityId();
			}

			TemplateFolderRelationTable::deleteByFilter([
				'=PARENT_ID' => $id,
				'=ENTITY_TYPE' => EntityType::TEMPLATE->value,
			]);

			if ($templateIds != [])
			{
				$templateRepository = $container->getDocumentTemplateRepository();

				$result = $templateRepository->deleteByIds($templateIds);
				if (!$result->isSuccess())
				{
					return (new Result())->addErrors($result->getErrors());
				}
			}

			TemplateFolderTable::delete($id);
		}
		catch (ArgumentException $e)
		{
			return (new Result())->addError(new Error($e->getMessage()));
		}

		return (new Result());
	}

	/**
	 * @param iterable<int> $ids
	 * @return Result
	 */
	public function deleteByIds(iterable $ids): Result
	{
		$ids = IterationHelper::getArrayByIterable($ids);
		if (empty($ids))
		{
			return new Result();
		}

		try
		{
			$container = Container::instance();
			$templateFolderRelationRepository = $container->getTemplateFolderRelationRepository();

			$result = $templateFolderRelationRepository->deleteByIdsAndType($ids, EntityType::FOLDER);
			if (!$result->isSuccess())
			{
				return (new Result())->addErrors($result->getErrors());
			}

			$templatesByFolderIdsQuery = TemplateFolderRelationTable::query()
				->setSelect(['ENTITY_ID'])
				->setFilter([
					'=ENTITY_TYPE' => EntityType::TEMPLATE->value,
					'@PARENT_ID' => $ids
				]);

			$templatesByFolderIdsQuery->exec();
			$templatesByFolderIds = $templatesByFolderIdsQuery->fetchCollection();

			$templateIds = [];
			foreach ($templatesByFolderIds as $template)
			{

				$templateIds[] = $template->getEntityId();
			}

			TemplateFolderRelationTable::deleteByFilter([
				'@PARENT_ID' => $ids,
				'=ENTITY_TYPE' => EntityType::TEMPLATE->value,
			]);

			if ($templateIds != [])
			{
				$templateRepository = $container->getDocumentTemplateRepository();

				$result = $templateRepository->deleteByIds($templateIds);
				if (!$result->isSuccess())
				{
					return (new Result())->addErrors($result->getErrors());
				}
			}

			TemplateFolderTable::deleteByFilter([
				'@ID' => $ids,
			]);
		}
		catch (ArgumentException $e)
		{
			return (new Result())->addError(new Error($e->getMessage()));
		}

		return new Result();
	}

	public function getTemplateIdsByFolderId(int $folderId): array
	{
		$query = TemplateFolderRelationTable::query();
		$query->setSelect(['*']);
		$query->setFilter([
			'=ENTITY_TYPE' => EntityType::TEMPLATE->value,
			'=PARENT_ID' => $folderId
		]);

		$query->exec();
		$collection = $query->fetchCollection();

		$templateIds = [];
		foreach ($collection as $item)
		{

			$templateIds[] = $item->getEntityId();
		}

		return $templateIds;
	}

	public function updateVisibility(int $folderId, Type\Template\Visibility $visibility): Result
	{
		return TemplateFolderTable::update($folderId, ['VISIBILITY' => $visibility->toInt()]);
	}

	private function extractModelFromItem(Item\Document\TemplateFolder $item): TemplateFolder
	{
		return $this->getFilledModelFromItem($item, TemplateFolderTable::createObject(false));
	}

	private function getFilledModelFromItem(Item\Document\TemplateFolder $item, TemplateFolder $model): TemplateFolder
	{
		return $model
			->setCreatedById($item->createdById)
			->setDateCreate($item->dateCreate)
			->setTitle($item->title)
			->setVisibility($item->visibility->toInt())
			->setDateModify($item->dateModify)
			->setModifiedById($item->modifiedById)
			->setStatus($item->status->toInt())
			;
	}

	private function extractItemFromModel(TemplateFolder $model): Item\Document\TemplateFolder
	{
		return new Item\Document\TemplateFolder(
			title: $model->getTitle(),
			createdById: $model->getCreatedById(),
			id: $model->getId(),
			modifiedById: $model->getModifiedById(),
			dateModify: Type\DateTime::createFromMainDateTimeOrNull($model->getDateModify()),
			dateCreate: Type\DateTime::createFromMainDateTime($model->getDateCreate()),
			visibility: Type\Template\Visibility::tryFromInt($model->getVisibility()) ?? Type\Template\Visibility::VISIBLE,
			status: Type\Template\Status::tryFromInt($model->getStatus()) ?? Type\Template\Status::NEW,
		);
	}

	private function extractItemCollectionFromModelCollection(TemplateFolderCollection $models): Item\Document\TemplateFolderCollection
	{
		$items = new Item\Document\TemplateFolderCollection();

		foreach ($models as $model)
		{
			$items->add($this->extractItemFromModel($model));
		}

		return $items;
	}
}