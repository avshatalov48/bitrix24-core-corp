<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Timeline\Entity\TimelineTable;

class DeliveryEntry extends TimelineEntry
{
	public static function create(array $params)
	{
		[$authorId, $created, $settings, $bindings] = self::fetchParams($params);

		$result = TimelineTable::add([
			'TYPE_ID' => TimelineType::DELIVERY,
			'TYPE_CATEGORY_ID' => DeliveryCategoryType::UNIVERSAL,
			'CREATED' => $created,
			'AUTHOR_ID' => $authorId,
			'SETTINGS' => $settings,
			'ASSOCIATED_ENTITY_TYPE_ID' => self::fetchEntityTypeId($params),
			'ASSOCIATED_ENTITY_ID' => self::fetchEntityId($params),
		]);
		if (!$result->isSuccess())
		{
			return null;
		}

		TimelineEntry::registerBindings($result->getId(), $bindings);

		return (int)$result->getId();
	}
}
