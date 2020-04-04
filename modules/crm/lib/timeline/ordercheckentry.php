<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Main;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\Timeline\Entity\TimelineTable;

class OrderCheckEntry extends TimelineEntry
{
	public static function create(array $params)
	{
		if (!is_array($params['BINDINGS']) || empty($params['BINDINGS']))
		{
			throw new Main\ArgumentException('Empty bindings for check entity.', 'Bindings');
		}

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

		$created = isset($params['CREATED']) && ($params['CREATED'] instanceof DateTime)
			? $params['CREATED'] : new DateTime();

		$settings = isset($params['SETTINGS']) && is_array($params['SETTINGS']) ? $params['SETTINGS'] : array();

		$result = TimelineTable::add(
			array(
				'TYPE_ID' => TimelineType::ORDER_CHECK,
				'CREATED' => $created,
				'AUTHOR_ID' => $authorID,
				'SETTINGS' => $settings,
				'ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::OrderCheck,
				'ASSOCIATED_ENTITY_CLASS_NAME' => $entityClassName,
				'ASSOCIATED_ENTITY_ID' => $entityID
			)
		);

		if (!$result->isSuccess())
		{
			return 0;
		}

		$ID = $result->getId();
		self::registerBindings($ID, $params['BINDINGS']);
		return $ID;
	}
}