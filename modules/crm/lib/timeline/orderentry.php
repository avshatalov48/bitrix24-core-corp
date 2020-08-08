<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Main;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\Timeline\Entity\TimelineTable;

class OrderEntry extends TimelineEntry
{
	public static function create(array $params)
	{
		$entityID = isset($params['ENTITY_ID']) ? (int)$params['ENTITY_ID'] : 0;
		if ($entityID <= 0)
		{
			throw new Main\ArgumentException('Entity ID must be greater than zero.', 'entityID');
		}

		$entityClassName = isset($params['ENTITY_CLASS_NAME']) ? $params['ENTITY_CLASS_NAME'] : '';

		$authorID = isset($params['AUTHOR_ID']) ? (int)$params['AUTHOR_ID'] : 0;
		if (!is_int($authorID))
		{
			$authorID = (int)$authorID;
		}

		if ($authorID <= 0)
		{
			throw new Main\ArgumentException('Author ID must be greater than zero.', 'authorID');
		}

		$categoryID = isset($params['TYPE_CATEGORY_ID']) ? (int)$params['TYPE_CATEGORY_ID'] : 0;
		if ($categoryID <= 0)
		{
			throw new Main\ArgumentException('Category Id must be greater than zero.', 'authorID');
		}

		$created = isset($params['CREATED']) && ($params['CREATED'] instanceof DateTime)
			? $params['CREATED'] : new DateTime();

		$settings = isset($params['SETTINGS']) && is_array($params['SETTINGS']) ? $params['SETTINGS'] : array();
		$entityTypeID = isset($params['ENTITY_TYPE_ID']) ? (int)$params['ENTITY_TYPE_ID'] : \CCrmOwnerType::Order;
		if ($entityTypeID <= 0)
		{
			throw new Main\ArgumentException('Category Id must be greater than zero.', 'entityTypeID');
		}

		$result = TimelineTable::add(
			array(
				'TYPE_ID' => TimelineType::ORDER,
				'TYPE_CATEGORY_ID' => $categoryID,
				'CREATED' => $created,
				'AUTHOR_ID' => $authorID,
				'SETTINGS' => $settings,
				'ASSOCIATED_ENTITY_TYPE_ID' => $entityTypeID,
				'ASSOCIATED_ENTITY_CLASS_NAME' => $entityClassName,
				'ASSOCIATED_ENTITY_ID' => $entityID
			)
		);

		if (!$result->isSuccess())
		{
			return 0;
		}

		$ID = $result->getId();
		$bindings = isset($params['BINDINGS']) && is_array($params['BINDINGS']) ? $params['BINDINGS'] : [];
		if (empty($bindings))
		{
			$bindings[] = ['ENTITY_TYPE_ID' => \CCrmOwnerType::Order, 'ENTITY_ID' => $entityID];
		}
		self::registerBindings($ID, $bindings);
		return $ID;
	}
}