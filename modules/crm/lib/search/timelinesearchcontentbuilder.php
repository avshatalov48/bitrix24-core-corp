<?php

namespace Bitrix\Crm\Search;

use Bitrix\Crm\Timeline\Entity\TimelineSearchTable;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Crm\Timeline\TimelineEntry;
use Bitrix\Crm\Timeline\TimelineManager;

final class TimelineSearchContentBuilder extends SearchContentBuilder
{
	protected function prepareEntityFields(int $entityId): ?array
	{
		return TimelineEntry::getByID($entityId);
	}

	protected function prepareSearchMap(array $fields, array $options = null): SearchMap
	{
		$map = new SearchMap();
		$entityId = isset($fields['ID']) ? (int)$fields['ID'] : 0;
		if ($entityId <= 0)
		{
			return $map;
		}

		$controller = TimelineManager::resolveController($fields);
		if ($controller && method_exists($controller, 'prepareSearchContent'))
		{
			$map->add($controller->prepareSearchContent($fields));
		}

		return $map;
	}
	
	protected function prepareForBulkBuild(array $entityIds): void
	{
		$dbResult = TimelineTable::getList([
			'select' => ['AUTHOR_ID'],
			'filter' => ['@ID' => $entityIds],
		]);
		
		$userIds = [];
		while ($fields = $dbResult->Fetch())
		{
			$userIds[] = (int)$fields['AUTHOR_ID'];
		}

		if (!empty($userIds))
		{
			SearchMap::cacheUsers($userIds);
		}
	}

	protected function save(int $entityId, SearchMap $map): void
	{
		TimelineSearchTable::upsert([
			'OWNER_ID' => $entityId,
			'SEARCH_CONTENT' => $map->getString()
		]);
	}
}
