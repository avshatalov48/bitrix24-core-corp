<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Activity\Provider\CallTracker;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main;

class CallTrackerEntry extends TimelineEntry
{
	public static function create(array $params)
	{
		$entityId = (isset($params['ENTITY_ID']) ? (int)$params['ENTITY_ID'] : 0);
		if ($entityId <= 0)
		{
			throw new Main\ArgumentException(
				'Entity ID must be greater than zero.',
				'entityID'
			);
		}

		$authorId = (isset($params['AUTHOR_ID']) ? (int)$params['AUTHOR_ID'] : 0);
		if ($authorId <= 0)
		{
			throw new Main\ArgumentException(
				'Author ID must be greater than zero.',
				'authorID'
			);
		}

		$created = (
			isset($params['CREATED']) && ($params['CREATED'] instanceof DateTime)
				? $params['CREATED']
				: new DateTime()
		);

		$settings = (
			isset($params['SETTINGS']) && is_array($params['SETTINGS'])
				? $params['SETTINGS']
				: []
		);

		$result = TimelineTable::add(
			array(
				'TYPE_ID' => TimelineType::ACTIVITY,
				'TYPE_CATEGORY_ID' => \CCrmActivityType::Provider,
				'CREATED' => $created,
				'AUTHOR_ID' => $authorId,
				'SETTINGS' => $settings,
				'ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::Activity,
				'ASSOCIATED_ENTITY_CLASS_NAME' => CallTracker::PROVIDER_ID,
				'ASSOCIATED_ENTITY_ID' => $entityId
			)
		);

		if(!$result->isSuccess())
		{
			return 0;
		}

		$id = $result->getId();
		$bindings = (
			isset($params['BINDINGS']) && is_array($params['BINDINGS'])
				? $params['BINDINGS']
				: []
		);
		self::registerBindings($id, $bindings);
		self::buildSearchContent($id);
		return $id;
	}

	public static function rebind($entityTypeId, $oldEntityId, $newEntityId): void
	{
		Entity\TimelineBindingTable::rebind(
			$entityTypeId, $oldEntityId, $newEntityId,
			[TimelineType::ACTIVITY]
		);
	}
}