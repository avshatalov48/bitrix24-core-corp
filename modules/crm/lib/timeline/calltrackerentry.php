<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Activity\Provider\CallTracker;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Main;

class CallTrackerEntry extends TimelineEntry
{
	public static function create(array $params)
	{
		[$authorId, $created, $settings, $bindings] = self::fetchParams($params);
		$entityId = self::fetchEntityId($params);
		$authorId = self::fetchAuthorId($params);

		$result = TimelineTable::add([
			'TYPE_ID' => TimelineType::ACTIVITY,
			'TYPE_CATEGORY_ID' => \CCrmActivityType::Provider,
			'CREATED' => $created,
			'AUTHOR_ID' => $authorId,
			'SETTINGS' => $settings,
			'ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::Activity,
			'ASSOCIATED_ENTITY_CLASS_NAME' => CallTracker::PROVIDER_ID,
			'ASSOCIATED_ENTITY_ID' => $entityId
		]);
		if (!$result->isSuccess())
		{
			return 0;
		}

		$createdId = $result->getId();

		self::registerBindings($createdId, $bindings);
		self::buildSearchContent($createdId);

		return $createdId;
	}

	public static function rebind($entityTypeId, $oldEntityId, $newEntityId): void
	{
		Entity\TimelineBindingTable::rebind(
			$entityTypeId,
			$oldEntityId,
			$newEntityId,
			[TimelineType::ACTIVITY]
		);
	}
}
