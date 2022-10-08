<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Timeline\Entity\TimelineTable;

class BizprocEntry extends TimelineEntry
{
	public static function create(array $params)
	{
		[$authorId, $created, $settings, $bindings] = self::fetchParams($params);

		$result = TimelineTable::add([
			'TYPE_ID' => TimelineType::BIZPROC,
			'TYPE_CATEGORY_ID' => 0,
			'CREATED' => $created,
			'AUTHOR_ID' => $authorId,
			'SETTINGS' => $settings,
			'ASSOCIATED_ENTITY_TYPE_ID' => 0,
			'ASSOCIATED_ENTITY_ID' => 0
		]);
		if (!$result->isSuccess())
		{
			return 0;
		}

		$createdId = $result->getId();

		self::registerBindings($createdId, $bindings);

		return $createdId;
	}
}
