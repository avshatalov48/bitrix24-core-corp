<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Type\DateTime;

abstract class RelationEntry extends TimelineEntry
{
	public static function create(array $params)
	{
		$entityTypeId = isset($params['ENTITY_TYPE_ID']) ? (int)$params['ENTITY_TYPE_ID'] : 0;
		if ($entityTypeId <= 0)
		{
			throw new ArgumentException('Entity type ID must be greater than zero.', 'entityTypeId');
		}

		$entityId = isset($params['ENTITY_ID']) ? (int)$params['ENTITY_ID'] : 0;
		if ($entityId <= 0)
		{
			throw new ArgumentException('Entity ID must be greater than zero.', 'entityId');
		}

		$authorId = isset($params['AUTHOR_ID']) ? (int)$params['AUTHOR_ID'] : 0;
		if ($authorId <= 0)
		{
			throw new ArgumentException('Author ID must be greater than zero.', 'authorId');
		}

		$created = isset($params['CREATED']) && ($params['CREATED'] instanceof DateTime)
			? $params['CREATED'] : new DateTime();

		$settings = isset($params['SETTINGS']) && is_array($params['SETTINGS']) ? $params['SETTINGS'] : [];

		$entryAddResult = TimelineTable::add(
			[
				'TYPE_ID' => static::getTimelineEntryType(),
				'TYPE_CATEGORY_ID' => 0,
				'CREATED' => $created,
				'AUTHOR_ID' => $authorId,
				'SETTINGS' => $settings,
				'ASSOCIATED_ENTITY_TYPE_ID' => $entityTypeId,
				'ASSOCIATED_ENTITY_ID' => $entityId,
			]
		);

		if (!$entryAddResult->isSuccess())
		{
			return 0;
		}

		$bindings = isset($params['BINDINGS']) && is_array($params['BINDINGS']) ? $params['BINDINGS'] : [];
		if (empty($bindings))
		{
			$bindings[] = ['ENTITY_TYPE_ID' => $entityTypeId, 'ENTITY_ID' => $entityId];
		}
		self::registerBindings($entryAddResult->getId(), $bindings);

		return $entryAddResult->getId();
	}

	public static function shiftAllEntriesForTimelineOwner(
		int $timelineOwnerEntityTypeId,
		int $timelineOwnerEntityId,
		DateTime $time
	): void
	{
		$collection = TimelineTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=TYPE_ID' => static::getTimelineEntryType(),
				'=BINDINGS.ENTITY_TYPE_ID' => $timelineOwnerEntityTypeId,
				'=BINDINGS.ENTITY_ID' => $timelineOwnerEntityId,
			],
		])->fetchCollection();

		foreach ($collection as $timelineEntry)
		{
			static::shift($timelineEntry->getId(), $time);
		}
	}

	abstract protected static function getTimelineEntryType(): int;
}
