<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Main;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\Timeline\Entity\TimelineTable;

class CreationEntry extends TimelineEntry
{
	public static function create(array $params)
	{
		[$authorId, $created, $settings, $bindings] = self::fetchParams($params);
		$entityTypeId = self::fetchEntityTypeId($params);
		$entityId = self::fetchEntityId($params);
		$authorId = self::fetchAuthorId($params);

		$result = TimelineTable::add([
			'TYPE_ID' => TimelineType::CREATION,
			'TYPE_CATEGORY_ID' => 0,
			'CREATED' => $created,
			'AUTHOR_ID' => $authorId,
			'SETTINGS' => $settings,
			'ASSOCIATED_ENTITY_TYPE_ID' => $entityTypeId,
			'ASSOCIATED_ENTITY_CLASS_NAME' => $params['ENTITY_CLASS_NAME'] ?? '',
			'ASSOCIATED_ENTITY_ID' => $entityId
		]);
		if(!$result->isSuccess())
		{
			return 0;
		}

		$createdId = $result->getId();
		if (empty($bindings))
		{
			$bindings[] = ['ENTITY_TYPE_ID' => $entityTypeId, 'ENTITY_ID' => $entityId];
		}

		self::registerBindings($createdId, $bindings);
		if ($entityTypeId === \CCrmOwnerType::Activity)
		{
			self::buildSearchContent($createdId);
		}

		return $createdId;
	}
	
	public static function rebind($entityTypeId, $oldEntityID, $newEntityID)
	{
		Entity\TimelineBindingTable::rebind(
			$entityTypeId,
			$oldEntityID,
			$newEntityID,
			[TimelineType::CREATION]
		);
	}
	
	public static function attach($srcEntityTypeID, $srcEntityID, $targEntityTypeID, $targEntityID)
	{
		Entity\TimelineBindingTable::attach(
			$srcEntityTypeID,
			$srcEntityID,
			$targEntityTypeID,
			$targEntityID,
			[TimelineType::CREATION]
		);
	}
	
	public static function shiftEntity($entityTypeId, $entityId, DateTime $time)
	{
		$dbResult = Entity\TimelineTable::getList(
			array(
				'select' => array('ID'),
				'filter' => array(
					'=ASSOCIATED_ENTITY_TYPE_ID' => $entityTypeId,
					'=ASSOCIATED_ENTITY_ID' => $entityId,
					'=TYPE_ID' => TimelineType::CREATION
				),
				'limit' => 1
			)
		);

		$fields = $dbResult->fetch();
		if(is_array($fields))
		{
			TimelineEntry::shift($fields['ID'], $time);
		}
	}
}
