<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Main;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\Timeline\Entity\TimelineTable;

class MarkEntry extends TimelineEntry
{
	public static function create(array $params)
	{
		$markTypeID = isset($params['MARK_TYPE_ID']) ? (int)$params['MARK_TYPE_ID'] : 0;
		if($markTypeID <= 0)
		{
			throw new Main\ArgumentException('Mark Type ID must be greater than zero.', 'markTypeID');
		}

		$entityTypeID = isset($params['ENTITY_TYPE_ID']) ? (int)$params['ENTITY_TYPE_ID'] : 0;
		if($entityTypeID <= 0)
		{
			throw new Main\ArgumentException('Entity type ID must be greater than zero.', 'entityTypeID');
		}

		$entityID = isset($params['ENTITY_ID']) ? (int)$params['ENTITY_ID'] : 0;
		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Entity ID must be greater than zero.', 'entityID');
		}

		$authorID = isset($params['AUTHOR_ID']) ? (int)$params['AUTHOR_ID'] : 0;
		if($authorID <= 0)
		{
			$authorID = \CCrmSecurityHelper::GetCurrentUserID();
		}

		$settings = isset($params['SETTINGS']) && is_array($params['SETTINGS']) ? $params['SETTINGS'] : array();
		$result = TimelineTable::add(
			array(
				'TYPE_ID' => TimelineType::MARK,
				'TYPE_CATEGORY_ID' => $markTypeID,
				'CREATED' => new DateTime(),
				'AUTHOR_ID' => $authorID,
				'SETTINGS' => $settings,
				'ASSOCIATED_ENTITY_TYPE_ID' => $entityTypeID,
				'ASSOCIATED_ENTITY_ID' => $entityID
			)
		);

		if(!$result->isSuccess())
		{
			return 0;
		}

		$ID = $result->getId();
		$bindings = isset($params['BINDINGS']) && is_array($params['BINDINGS']) ? $params['BINDINGS'] : array();
		if(empty($bindings))
		{
			$bindings[] = array('ENTITY_TYPE_ID' => $entityTypeID, 'ENTITY_ID' => $entityID);
		}
		self::registerBindings($ID, $bindings);
		return $ID;
	}
	public static function attach($srcEntityTypeID, $srcEntityID, $targEntityTypeID, $targEntityID)
	{
		Entity\TimelineBindingTable::attach($srcEntityTypeID, $srcEntityID, $targEntityTypeID, $targEntityID, array(TimelineType::MARK));
	}
}