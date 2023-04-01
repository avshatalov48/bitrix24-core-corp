<?php

namespace Bitrix\Crm\Timeline;

/**
 * Class FinalSummaryEntry
 * @package Bitrix\Crm\Timeline
 */
class FinalSummaryEntry extends TimelineEntry
{
	protected const TIMELINE_ENTRY_TYPE = TimelineType::FINAL_SUMMARY;

	public static function create(array $params)
	{
		[$authorId, $created, $settings, $bindings] = self::fetchParams($params);
		$entityId = self::fetchEntityId($params);
		$entityTypeId = self::fetchEntityTypeId($params);
		$categoryId = self::fetchCategoryId($params);
		$authorId = self::fetchAuthorId($params);

		$result = Entity\TimelineTable::add([
			'TYPE_ID' => static::TIMELINE_ENTRY_TYPE,
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

		if (empty($bindings))
		{
			$bindings[] = ['ENTITY_TYPE_ID' => $entityTypeId, 'ENTITY_ID' => $entityId];
		}
		self::registerBindings($createdId, $bindings);

		return $createdId;
	}
}
