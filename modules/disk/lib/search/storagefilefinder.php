<?php

declare(strict_types=1);

namespace Bitrix\Disk\Search;

use Bitrix\Disk;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Main\Search;

class StorageFileFinder
{
	public function __construct(
		protected int $userId,
		protected int $limit = 30,
		protected array $order = ['UPDATE_TIME' => 'DESC'],
		protected array $entityTypes = [
			Disk\ProxyType\User::class,
			Disk\ProxyType\Group::class,
			Disk\ProxyType\Common::class,
		],
	)
	{
	}

	/**
	 * Find object IDs based on a full-text search query.
	 *
	 * @param string $searchQuery
	 * @return array
	 */
	public function findIdsByText(string $searchQuery): array
	{
		if (empty($this->entityTypes))
		{
			return [];
		}

		$filter = [
			'=DELETED_TYPE' => ObjectTable::DELETED_TYPE_NONE,
			'STORAGE.USE_INTERNAL_RIGHTS' => true,
			'=STORAGE.MODULE_ID' => Driver::INTERNAL_MODULE_ID,
			'@STORAGE.ENTITY_TYPE' => $this->entityTypes,
		];

		$fulltextContent = Disk\Search\FullTextBuilder::create()
			->addText($searchQuery)
			->getSearchValue()
		;

		if (empty($fulltextContent) || !Search\Content::canUseFulltextSearch($fulltextContent))
		{
			return [];
		}

		if (Reindex\HeadIndex::isReady())
		{
			$filter['*HEAD_INDEX.SEARCH_INDEX'] = $fulltextContent;
		}
		elseif (Reindex\BaseObjectIndex::isReady())
		{
			$filter['*SEARCH_INDEX'] = $fulltextContent;
		}
		else
		{
			return [];
		}

		$securityContext = new Disk\Security\DiskSecurityContext($this->userId);
		$parameters = Driver::getInstance()->getRightsManager()->addRightsCheck(
			$securityContext,
			[
				'select' => ['ID'],
				'filter' => $filter,
				'limit' => $this->limit,
				'order' => $this->order,
			],
			['ID', 'CREATED_BY']
		);

		$objectIds = [];
		foreach (ObjectTable::getList($parameters) as $row)
		{
			$objectIds[] = $row['ID'];
		}

		return $objectIds;
	}

	/**
	 * Retrieve Disk\BaseObject models based on a search query.
	 *
	 * @param string $searchQuery
	 * @return Disk\BaseObject[]
	 */
	public function findModelsByText(string $searchQuery): array
	{
		$objectIds = $this->findIdsByText($searchQuery);

		if (empty($objectIds))
		{
			return [];
		}

		$parameters = [
			'filter' => ['@ID' => $objectIds],
			'order' => $this->order,
		];
		$objects = [];

		foreach (Disk\BaseObject::getModelList($parameters) as $object)
		{
			$objects[] = $object;
		}

		return $objects;
	}
}