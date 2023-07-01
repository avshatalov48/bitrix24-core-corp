<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Main;
use Bitrix\Crm\Timeline\Entity\TimelineTable;

class ActivityEntry extends TimelineEntry
{
	public static function create(array $params)
	{
		[$authorId, $created, $settings, $bindings] = self::fetchParams($params);
		$entityId = self::fetchEntityId($params);
		$activityTypeId = isset($params['ACTIVITY_TYPE_ID']) ? (int)$params['ACTIVITY_TYPE_ID'] : 0;
		if ($activityTypeId <= 0)
		{
			throw new Main\ArgumentException('Activity Type ID must be greater than zero.', 'activityTypeID');
		}

		$activityProviderId = $params['ACTIVITY_PROVIDER_ID'] ?? '';

		$data = [
			'TYPE_ID' => TimelineType::ACTIVITY,
			'TYPE_CATEGORY_ID' => $activityTypeId,
			'CREATED' => $created,
			'AUTHOR_ID' => $authorId,
			'ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::Activity,
			'ASSOCIATED_ENTITY_CLASS_NAME' => $activityProviderId,
			'ASSOCIATED_ENTITY_ID' => $entityId,
		];

		if (isset($params['SOURCE_ID']))
		{
			$data['SOURCE_ID'] = (string)$params['SOURCE_ID'];
		}

		$result = TimelineTable::add($data);
		if (!$result->isSuccess())
		{
			return 0;
		}

		$createdId = $result->getId();

		self::registerBindings($createdId, $bindings);
		self::buildSearchContent($createdId);

		return $createdId;
	}

	public static function rebind($entityTypeID, $oldEntityID, $newEntityID)
	{
		Entity\TimelineBindingTable::rebind(
			$entityTypeID,
			$oldEntityID,
			$newEntityID,
			[TimelineType::ACTIVITY]
		);
	}

	public static function attach($srcEntityTypeID, $srcEntityID, $targEntityTypeID, $targEntityID)
	{
		Entity\TimelineBindingTable::attach(
			$srcEntityTypeID,
			$srcEntityID,
			$targEntityTypeID,
			$targEntityID,
			[TimelineType::ACTIVITY]
		);
	}
}