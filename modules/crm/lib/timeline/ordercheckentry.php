<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Main;
use Bitrix\Crm\Timeline\Entity\TimelineTable;

class OrderCheckEntry extends TimelineEntry
{
	public static function create(array $params)
	{
		[$authorId, $created, $settings, $bindings] = self::fetchParams($params);

		if (!is_array($params['BINDINGS']) || empty($params['BINDINGS']))
		{
			throw new Main\ArgumentException('Empty bindings for check entity.', 'Bindings');
		}

		$entityId = isset($params['ENTITY_ID']) ? (int)$params['ENTITY_ID'] : 0;
		if ($entityId <= 0 && !isset($settings['FAILURE']))
		{
			throw new Main\ArgumentException('Entity ID must be greater than zero.', 'entityID');
		}

		$result = TimelineTable::add([
			'TYPE_ID' => TimelineType::ORDER_CHECK,
			'TYPE_CATEGORY_ID' => isset($params['TYPE_CATEGORY_ID']) ? (int)$params['TYPE_CATEGORY_ID'] : 0,
			'CREATED' => $created,
			'AUTHOR_ID' => $authorId,
			'SETTINGS' => $settings,
			'ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::OrderCheck,
			'ASSOCIATED_ENTITY_CLASS_NAME' => $params['ENTITY_CLASS_NAME'] ?? '',
			'ASSOCIATED_ENTITY_ID' => $entityId
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
