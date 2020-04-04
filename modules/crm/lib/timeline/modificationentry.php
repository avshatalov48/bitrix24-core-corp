<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Main;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\Timeline\Entity\TimelineTable;

class ModificationEntry extends TimelineEntry
{
	public static function create(array $params)
	{
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
		$bindings = isset($params['BINDINGS']) && is_array($params['BINDINGS']) ? $params['BINDINGS'] : array();
		$text = isset($params['TEXT']) ? $params['TEXT'] : null;

		$result = TimelineTable::add(
			array(
				'TYPE_ID' => TimelineType::MODIFICATION,
				'TYPE_CATEGORY_ID' => 0,
				'CREATED' => new DateTime(),
				'AUTHOR_ID' => $authorID,
				'SETTINGS' => $settings,
				'COMMENT' => $text,
				'ASSOCIATED_ENTITY_TYPE_ID' => $entityTypeID,
				'ASSOCIATED_ENTITY_ID' => $entityID
			)
		);

		if(!$result->isSuccess())
		{
			return 0;
		}

		$ID = $result->getId();
		if(empty($bindings))
		{
			$bindings[] = array('ENTITY_TYPE_ID' => $entityTypeID, 'ENTITY_ID' => $entityID);
		}
		self::registerBindings($ID, $bindings);
		if($entityTypeID === \CCrmOwnerType::Activity)
		{
			self::buildSearchContent($ID);
		}
		return $ID;
	}
}