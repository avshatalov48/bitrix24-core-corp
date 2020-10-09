<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Activity\Provider\Zoom;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main;

class ZoomEntry extends TimelineEntry
{
	public static function create(array $params)
	{
		$entityID = isset($params['ENTITY_ID']) ? (int)$params['ENTITY_ID'] : 0;
		if ($entityID <= 0)
		{
			throw new Main\ArgumentException('Entity ID must be greater than zero.', 'entityID');
		}

		$authorID = isset($params['AUTHOR_ID']) ? (int)$params['AUTHOR_ID'] : 0;
		if ($authorID <= 0)
		{
			throw new Main\ArgumentException('Author ID must be greater than zero.', 'authorID');
		}

		$created = isset($params['CREATED']) && ($params['CREATED'] instanceof DateTime)
			? $params['CREATED'] : new DateTime();

		$settings = isset($params['SETTINGS']) && is_array($params['SETTINGS']) ? $params['SETTINGS'] : array();

		$result = TimelineTable::add(
			array(
				'TYPE_ID' => TimelineType::ACTIVITY,
				'TYPE_CATEGORY_ID' => \CCrmActivityType::Provider,
				'CREATED' => $created,
				'AUTHOR_ID' => $authorID,
				'SETTINGS' => $settings,
				'ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::Activity,
				'ASSOCIATED_ENTITY_CLASS_NAME' => Zoom::PROVIDER_ID,
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
		self::buildSearchContent($ID);
		return $ID;
	}

	public static function rebind($entityTypeID, $oldEntityID, $newEntityID): void
	{
		Entity\TimelineBindingTable::rebind($entityTypeID, $oldEntityID, $newEntityID, array(TimelineType::ACTIVITY));
	}
}