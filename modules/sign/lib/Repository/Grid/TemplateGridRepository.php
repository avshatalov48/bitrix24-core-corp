<?php

namespace Bitrix\Sign\Repository\Grid;

use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Sign\Internal\Document\Template\TemplateFolderRelationTable;
use Bitrix\Sign\Item\DocumentTemplateGrid\QueryOptions;
use Bitrix\Sign\Item\DocumentTemplateGrid\Row;
use Bitrix\Sign\Item\DocumentTemplateGrid\RowCollection;
use Bitrix\Sign\Type\DateTime;
use Bitrix\Sign\Type\Template\EntityType;
use Bitrix\Sign\Type\Template\Status;
use Bitrix\Sign\Type\Template\Visibility;

class TemplateGridRepository
{
	public function listFoldersAndTemplates(QueryOptions $options): RowCollection
	{
		$query = $this->selectTemplateFolderRelationQuery($options);
		$collection = $query->fetchCollection();

		$data = [];
		foreach ($collection as $item)
		{
			$entityType = $item->get('ENTITY_TYPE');
			if (EntityType::from($entityType)->isFolder())
			{
				$folder = $item->get('FOLDER');
				if ($folder !== null)
				{
					$data[] = new Row(
						id: $folder->getId(),
						title: $folder->getTitle(),
						createdById: $folder->getCreatedById(),
						entityType: EntityType::from($entityType),
						modifiedById: $folder->getModifiedById(),
						dateModify: new DateTime($folder->getDateModify()),
						dateCreate: new DateTime($folder->getDateCreate()),
						visibility: Visibility::tryFromInt((int)$folder->getVisibility()),
						status: null,
					);
				}
			}
			elseif (EntityType::from($entityType)->isTemplate())
			{
				$template = $item->get('TEMPLATE');
				if ($template !== null)
				{
					$data[] = new Row(
						id: $template->getId(),
						title: $template->getTitle(),
						createdById: $template->getCreatedById(),
						entityType: EntityType::from($entityType),
						uid: $template->getUid(),
						modifiedById: $template->getModifiedById(),
						dateModify: new DateTime($template->getDateModify()),
						dateCreate: new DateTime($template->getDateCreate()),
						visibility: Visibility::tryFromInt((int)$template->getVisibility()),
						status: Status::tryFromInt($template->getStatus()) ?? Status::NEW,
					);
				}
			}
		}

		return new RowCollection(...$data);
	}

	public function listTemplatesByFolderId(int $folderId, QueryOptions $options): RowCollection
	{
		$query = $this->selectTemplateFolderRelationQueryByFolderId($folderId, $options);
		$collection = $query->fetchCollection();

		$data = [];
		foreach ($collection as $item)
		{
			$template = $item->get('TEMPLATE');
			$entityType = $item->get('ENTITY_TYPE');

			$data[] = new Row(
				id: $template->getId(),
				title: $template->getTitle(),
				createdById: $template->getCreatedById(),
				entityType: EntityType::from($entityType),
				uid: $template->getUid(),
				modifiedById: $template->getModifiedById(),
				dateModify: new DateTime($template->getDateModify()),
				dateCreate: new DateTime($template->getDateCreate()),
				visibility: Visibility::tryFromInt((int)$template->getVisibility()),
				status: Status::tryFromInt($template->getStatus()) ?? Status::NEW,
			);
		}

		return new RowCollection(...$data);
	}

	public function listByDepthAndEntityType(
		int $depthLevel,
		EntityType $entityType,
		ConditionTree $filter,
		int $limit = 1000
	): RowCollection
	{
		$query = TemplateFolderRelationTable::query()
			->setSelect(['ENTITY_TYPE', 'FOLDER.*'])
			->setLimit($limit) // hardcoded value
			->where('ENTITY_TYPE', $entityType->value)
			->where('DEPTH_LEVEL', $depthLevel)
			->where($filter)
			->addOrder('FOLDER.DATE_CREATE', 'DESC');

		$collection = $query->fetchCollection();

		$data = [];
		foreach ($collection as $item)
		{
			$folder = $item->get('FOLDER');
			$data[] = new Row(
				id: $folder->getId(),
				title: $folder->getTitle(),
				createdById: $folder->getCreatedById(),
				entityType: EntityType::FOLDER,
				modifiedById: $folder->getModifiedById(),
				dateModify: new DateTime($folder->getDateModify()),
				dateCreate: new DateTime($folder->getDateCreate()),
				visibility: Visibility::tryFromInt((int)$folder->getVisibility()),
				status: null,
			);
		}

		return new RowCollection(...$data);
	}

	public function getListCount(QueryOptions $options, int $folderId = 0): int
	{
		if ($folderId > 0)
		{
			return (int)$this->selectTemplateFolderRelationQueryByFolderId($folderId, $options)->queryCountTotal();
		}

		return (int)$this->selectTemplateFolderRelationQuery($options)->queryCountTotal();
	}

	private function selectTemplateFolderRelationQuery(QueryOptions $options): Query
	{
		$visibleRelationFilter = Query::filter()
			->logic('or')
			->where('ENTITY_TYPE', EntityType::FOLDER->value)
			->where(
				Query::filter()
					->where('ENTITY_TYPE', EntityType::TEMPLATE->value)
					->where('TEMPLATE.HIDDEN', false)
			)
		;

		return TemplateFolderRelationTable::query()
			->addSelect('ENTITY_ID')
			->addSelect('ENTITY_TYPE')
			->addSelect('PARENT_ID')
			->addSelect('ID')
			->addSelect('FOLDER.*')
			->addSelect('TEMPLATE.*')
			->addOrder('ENTITY_TYPE')
			->addOrder('FOLDER.DATE_CREATE', 'DESC')
			->addOrder('TEMPLATE.DATE_CREATE', 'DESC')
			->where('DEPTH_LEVEL', 0)
			->where($visibleRelationFilter)
			->where($options->filter)
			->setLimit($options->limit)
			->setOffset($options->offset)
		;
	}

	private function selectTemplateFolderRelationQueryByFolderId(int $folderId, QueryOptions $options): Query
	{
		return TemplateFolderRelationTable::query()
			->addSelect('ENTITY_ID')
			->addSelect('ENTITY_TYPE')
			->addSelect('PARENT_ID')
			->addSelect('ID')
			->addSelect('TEMPLATE.*')
			->where('DEPTH_LEVEL', 1)
			->where('PARENT_ID', $folderId)
			->where('ENTITY_TYPE', EntityType::TEMPLATE->value)
			->where('TEMPLATE.HIDDEN', false)
			->where($options->filter)
			->setLimit($options->limit)
			->setOffset($options->offset)
			->addOrder('TEMPLATE.DATE_CREATE', 'DESC')
		;
	}
}