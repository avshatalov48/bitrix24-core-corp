<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\Timeline\Entity\TimelineTable;

class ScoringEntry extends TimelineEntry
{
	public static function create(array $params)
	{
		[$authorId, $created, $settings, $bindings] = self::fetchParams($params);
		$entityId = self::fetchEntityId($params);
		
		$result = TimelineTable::add([
			'TYPE_ID' => TimelineType::SCORING,
			'TYPE_CATEGORY_ID' => 0,
			'CREATED' => new DateTime(),
			'ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::Scoring,
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
}
