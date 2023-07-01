<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Timeline\Entity\TimelineBindingTable;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use CCrmOwnerType;

class LogMessageEntry extends TimelineEntry
{
	public static function create(array $params)
	{
		[$authorId, $created, $settings, $bindings] = self::fetchParams($params);
		$entityTypeId = self::fetchEntityTypeId($params);
		$entityId = self::fetchEntityId($params);
		$categoryId = self::fetchCategoryId($params);

		$result = TimelineTable::add([
			'TYPE_ID' => TimelineType::LOG_MESSAGE,
			'TYPE_CATEGORY_ID' => $categoryId,
			'CREATED' => $created,
			'AUTHOR_ID' => $authorId,
			'SETTINGS' => $settings,
			'SOURCE_ID' => $params['SOURCE_ID'] ?? '',
			'ASSOCIATED_ENTITY_TYPE_ID' => $params['ASSOCIATED_ENTITY_TYPE_ID'] ?? $entityTypeId,
			'ASSOCIATED_ENTITY_ID' => $params['ASSOCIATED_ENTITY_ID'] ?? $entityId,
		]);
		if (!$result->isSuccess())
		{
			return 0;
		}

		$createdId = $result->getId();

		if (empty($bindings))
		{
			$bindings[] = ['ENTITY_TYPE_ID' => $entityTypeId, 'ENTITY_ID' => $entityId];
		}

		self::registerBindings($createdId, $bindings);

		if ($entityTypeId === CCrmOwnerType::Activity)
		{
			self::buildSearchContent($createdId);
		}
		
		return $createdId;
	}

	public static function rebind($entityTypeId, $oldEntityId, $newEntityId): void
	{
		TimelineBindingTable::rebind(
			$entityTypeId,
			$oldEntityId,
			$newEntityId,
			[TimelineType::LOG_MESSAGE]
		);
	}

	public static function detectIdByParams(string $sourceId, int $typeCategoryId): ?int
	{
		$row = TimelineTable::getRow([
			'select' => ['ID'],
			'filter' => [
				'=SOURCE_ID' => $sourceId,
				'=TYPE_ID' => TimelineType::LOG_MESSAGE,
				'=TYPE_CATEGORY_ID' => $typeCategoryId,
				'!=ASSOCIATED_ENTITY_TYPE_ID' => CCrmOwnerType::Activity
			],
			'order' => ['ID' => 'DESC']
		]);

		if (empty($row))
		{
			return null;
		}

		return $row['ID'];
	}
}
