<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Main\Type\DateTime;
use CCrmOwnerType;

class RestorationEntry extends TimelineEntry
{
	public static function create(array $params)
	{
		$entityTypeId = self::fetchEntityTypeId($params);
		$entityId = self::fetchEntityId($params);
		[$authorId, $created, $settings, $bindings] = self::fetchParams($params);

		$result = TimelineTable::add([
			'TYPE_ID' => TimelineType::RESTORATION,
			'TYPE_CATEGORY_ID' => 0,
			'CREATED' => $created,
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

		if (empty($bindings))
		{
			$bindings[] = [
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => $entityId
			];
		}

		self::registerBindings($createdId, $bindings);

		if ($entityTypeId === CCrmOwnerType::Activity)
		{
			self::buildSearchContent($createdId);
		}

		return $createdId;
	}

	public static function attach($srcEntityTypeID, $srcEntityID, $targEntityTypeID, $targEntityID)
	{
		Entity\TimelineBindingTable::attach(
			$srcEntityTypeID,
			$srcEntityID,
			$targEntityTypeID,
			$targEntityID,
			[TimelineType::RESTORATION]
		);
	}

	public static function shiftEntity($entityTypeId, $entityId, DateTime $time)
	{
		$dbResult = Entity\TimelineTable::getList(
			array(
				'select' => ['ID'],
				'filter' => [
					'=ASSOCIATED_ENTITY_TYPE_ID' => $entityTypeId,
					'=ASSOCIATED_ENTITY_ID' => $entityId,
					'=TYPE_ID' => TimelineType::RESTORATION
				],
				'limit' => 1
			)
		);

		$fields = $dbResult->fetch();
		if (is_array($fields))
		{
			TimelineEntry::shift($fields['ID'], $time);
		}
	}
}
