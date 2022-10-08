<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\Timeline\Entity\TimelineTable;

class ConversionEntry extends TimelineEntry
{
	public static function create(array $params)
	{
		[$authorId, $created, $settings, $bindings] = self::fetchParams($params);
		$entityTypeId = self::fetchEntityTypeId($params);
		$entityId = self::fetchEntityId($params);

		$result = TimelineTable::add([
			'TYPE_ID' => TimelineType::CONVERSION,
			'TYPE_CATEGORY_ID' => 0,
			'CREATED' => new DateTime(),
			'AUTHOR_ID' => $authorId,
			'SETTINGS' => $settings,
			'ASSOCIATED_ENTITY_TYPE_ID' => $entityTypeId,
			'ASSOCIATED_ENTITY_ID' => $entityId
		]);
		if (!$result->isSuccess())
		{
			return 0;
		}

		$createdId = $result->getId();

		if(empty($bindings))
		{
			$bindings[] = ['ENTITY_TYPE_ID' => $entityTypeId, 'ENTITY_ID' => $entityId];
		}
		self::registerBindings($createdId, $bindings);

		return $createdId;
	}

	public static function update($ID, array $params)
	{
		$fields = [];

		if (isset($params['SETTINGS']) && is_array($params['SETTINGS']))
		{
			$fields['SETTINGS'] = $params['SETTINGS'];
		}

		TimelineTable::update($ID, $fields);
	}
}
