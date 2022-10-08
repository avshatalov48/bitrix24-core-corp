<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Main\ArgumentException;
use Bitrix\Crm\Timeline\Entity\TimelineTable;

class ExternalNoticeEntry extends TimelineEntry
{
	public static function create(array $params)
	{
		[$authorId, $created, $settings, $bindings] = self::fetchParams($params);

		$entityId = self::fetchEntityId($params);
		$entityTypeId = self::fetchEntityTypeId($params);
		$categoryId = self::fetchCategoryId($params);
		$authorId = self::fetchAuthorId($params);

		$result = TimelineTable::add([
			'TYPE_ID' => TimelineType::EXTERNAL_NOTICE,
			'TYPE_CATEGORY_ID' => $categoryId,
			'ASSOCIATED_ENTITY_ID' => $params['ENTITY_ID'] ?? '',
			'ASSOCIATED_ENTITY_TYPE_ID' => $params['ENTITY_TYPE_ID'] ?? '',
			'CREATED' => $created,
			'AUTHOR_ID' => $authorId,
			'SETTINGS' => $settings
		]);
		if (!$result->isSuccess())
		{
			return 0;
		}

		$createdId = $result->getId();

		if(empty($bindings))
		{
			$bindings[] = ['ENTITY_TYPE_ID' => $entityTypeId, 'ENTITY_ID' => $entityId];
		}
		self::registerBindings($createdId, $bindings);
		
		return $createdId;
	}
}
