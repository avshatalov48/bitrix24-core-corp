<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Main\Type\DateTime;

abstract class RelationEntry extends TimelineEntry
{
	public static function create(array $params)
	{
		[$authorId, $created, $settings, $bindings] = self::fetchParams($params);
		$entityTypeId = self::fetchEntityTypeId($params);
		$entityId = self::fetchEntityId($params);
		$authorId = self::fetchAuthorId($params);

		$entryAddResult = TimelineTable::add([
			'TYPE_ID' => static::getTimelineEntryType(),
			'TYPE_CATEGORY_ID' => 0,
			'CREATED' => $created,
			'AUTHOR_ID' => $authorId,
			'SETTINGS' => $settings,
			'ASSOCIATED_ENTITY_TYPE_ID' => $entityTypeId,
			'ASSOCIATED_ENTITY_ID' => $entityId,
		]);
		if (!$entryAddResult->isSuccess())
		{
			return 0;
		}

		$createdId = $entryAddResult->getId();

		if (empty($bindings))
		{
			$bindings[] = ['ENTITY_TYPE_ID' => $entityTypeId, 'ENTITY_ID' => $entityId];
		}
		self::registerBindings($createdId, $bindings);

		return $createdId;
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
