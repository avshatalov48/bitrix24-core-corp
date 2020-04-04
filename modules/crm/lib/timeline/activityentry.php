<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Main;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\Timeline\Entity\TimelineTable;

class ActivityEntry extends TimelineEntry
{
	public static function create(array $params)
	{
		$activityTypeID = isset($params['ACTIVITY_TYPE_ID']) ? (int)$params['ACTIVITY_TYPE_ID'] : 0;
		if($activityTypeID <= 0)
		{
			throw new Main\ArgumentException('Activity Type ID must be greater than zero.', 'activityTypeID');
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

		$created = isset($params['CREATED']) && ($params['CREATED'] instanceof DateTime)
			? $params['CREATED'] : new DateTime();

		$result = TimelineTable::add(
			array(
				'TYPE_ID' => TimelineType::ACTIVITY,
				'TYPE_CATEGORY_ID' => $activityTypeID,
				'CREATED' => $created,
				'AUTHOR_ID' => $authorID,
				'ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::Activity,
				'ASSOCIATED_ENTITY_ID' => $entityID
			)
		);

		if(!$result->isSuccess())
		{
			return 0;
		}

		$ID = $result->getId();
		$bindings = isset($params['BINDINGS']) && is_array($params['BINDINGS']) ? $params['BINDINGS'] : array();
		self::registerBindings($ID, $bindings);
		return $ID;
	}
	public static function rebind($entityTypeID, $oldEntityID, $newEntityID)
	{
		Entity\TimelineBindingTable::rebind($entityTypeID, $oldEntityID, $newEntityID, array(TimelineType::ACTIVITY));
	}
	public static function attach($srcEntityTypeID, $srcEntityID, $targEntityTypeID, $targEntityID)
	{
		Entity\TimelineBindingTable::attach($srcEntityTypeID, $srcEntityID, $targEntityTypeID, $targEntityID, array(TimelineType::ACTIVITY));
	}
}