<?php

namespace Bitrix\Crm\Timeline\EmailActivityStatuses;

use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Crm\Timeline\TimelineEntry;
use Bitrix\Crm\Timeline\TimelineType;
use Bitrix\Crm\Timeline\LogMessageType;
use Bitrix\Main\Type\DateTime;

final class Entry extends TimelineEntry
{
	public static function create($params)
	{
		$authorId = $params['AUTHOR_ID'] ?? null;
		$activityId = $params['ACTIVITY_ID'] ?? null;
		$ownerTypeId = $params['OWNER_TYPE_ID'] ?? null;
		$ownerId = $params['OWNER_ID'] ?? null;

		$result = TimelineTable::add([
			'TYPE_ID' => self::getTypeId(),
			'TYPE_CATEGORY_ID' => self::getTypeCategoryId(),
			'CREATED' => new DateTime(),
			'AUTHOR_ID' => $authorId,
			'ASSOCIATED_ENTITY_TYPE_ID' => self::getOwnerTypeId(),
			'ASSOCIATED_ENTITY_ID' => $activityId,
		]);

		if (!$result->isSuccess())
		{
			return 0;
		}

		$id = $result->getId();
		$bindings = [
			[
				'ENTITY_TYPE_ID' => $ownerTypeId,
				'ENTITY_ID' => $ownerId,
			]
		];
		TimelineEntry::registerBindings($id, $bindings);

		return $id;
	}

	public static function getOwnerTypeId(): int
	{
		return TimelineType::ACTIVITY;
	}

	public static function getTypeId(): int
	{
		return TimelineType::LOG_MESSAGE;
	}

	public static function getTypeCategoryId(): int
	{
		return LogMessageType::EMAIL_ACTIVITY_STATUS_SUCCESSFULLY_DELIVERED;
	}
}