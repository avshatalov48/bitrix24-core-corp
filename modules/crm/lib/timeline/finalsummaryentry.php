<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Main;
use Bitrix\Main\Type\DateTime;

/**
 * Class FinalSummaryEntry
 * @package Bitrix\Crm\Timeline
 */
class FinalSummaryEntry extends TimelineEntry
{
	protected const TIMELINE_ENTRY_TYPE = TimelineType::FINAL_SUMMARY;

	/**
	 * @param array $params
	 * @return array|int
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectException
	 */
	public static function create(array $params)
	{
		$entityId = $params['ENTITY_ID'] ?? 0;
		if ($entityId <= 0)
		{
			throw new Main\ArgumentException('Entity ID must be greater than zero.', 'entityID');
		}

		$entityTypeId = $params['ENTITY_TYPE_ID'] ?? 0;
		if ($entityTypeId <= 0)
		{
			throw new Main\ArgumentException('Entity ID must be greater than zero.', 'entityID');
		}

		$categoryId = $params['TYPE_CATEGORY_ID'] ?? 0;
		if ($categoryId <= 0)
		{
			throw new Main\ArgumentException('Category Id must be greater than zero.', 'authorID');
		}

		$authorID = (int)$params['AUTHOR_ID'] ?? 0;
		if ($authorID <= 0)
		{
			throw new Main\ArgumentException('Author ID must be greater than zero.', 'authorID');
		}

		if (isset($params['CREATED']) && ($params['CREATED'] instanceof DateTime))
		{
			$created = $params['CREATED'];
		}
		else
		{
			$created = new DateTime();
		}

		$settings = $params['SETTINGS'] ?? [];

		$result = Entity\TimelineTable::add([
			'TYPE_ID' => static::TIMELINE_ENTRY_TYPE,
			'TYPE_CATEGORY_ID' => $categoryId,
			'ASSOCIATED_ENTITY_ID' => $params['ENTITY_ID'] ?? '',
			'ASSOCIATED_ENTITY_TYPE_ID' => $params['ENTITY_TYPE_ID'] ?? '',
			'CREATED' => $created,
			'AUTHOR_ID' => $authorID,
			'SETTINGS' => $settings
		]);

		if (!$result->isSuccess())
		{
			return 0;
		}

		$id = $result->getId();

		$bindings = $params['BINDINGS'] ?? [];
		if (empty($bindings))
		{
			$bindings[] = ['ENTITY_TYPE_ID' => $entityTypeId, 'ENTITY_ID' => $entityId];
		}
		self::registerBindings($id, $bindings);

		return $id;
	}
}