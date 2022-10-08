<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\Timeline\Entity\TimelineTable;

class WaitEntry extends TimelineEntry
{
	public static function create(array $params)
	{
		[$authorId, $created, $settings, $bindings] = self::fetchParams($params);
		$entityId = self::fetchEntityId($params);
		$result = TimelineTable::add([
			'TYPE_ID' => TimelineType::WAIT,
			'TYPE_CATEGORY_ID' => 0,
			'CREATED' => new DateTime(),
			'AUTHOR_ID' => $authorId,
			'ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::Wait,
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
