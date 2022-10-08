<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Main;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\Timeline\Entity\TimelineTable;

class MarkEntry extends TimelineEntry
{
	public static function create(array $params)
	{
		$entityTypeId = self::fetchEntityTypeId($params);
		$entityId = self::fetchEntityId($params);
		$markTypeId = isset($params['MARK_TYPE_ID']) ? (int)$params['MARK_TYPE_ID'] : 0;
		if ($markTypeId <= 0)
		{
			throw new Main\ArgumentException('Mark Type ID must be greater than zero.', 'markTypeID');
		}
		[$authorId, $created, $settings, $bindings] = self::fetchParams($params);

		$result = TimelineTable::add([
			'TYPE_ID' => TimelineType::MARK,
			'TYPE_CATEGORY_ID' => $markTypeId,
			'CREATED' => new DateTime(),
			'AUTHOR_ID' => $authorId,
			'SETTINGS' => $settings,
			'ASSOCIATED_ENTITY_TYPE_ID' => $entityTypeId,
			'ASSOCIATED_ENTITY_CLASS_NAME' => $params['ENTITY_CLASS_NAME'] ?? '',
			'ASSOCIATED_ENTITY_ID' => $entityId
		]);
		if (!$result->isSuccess())
		{
			return 0;
		}

		$createdId = $result->getId();

		if (empty($bindings))
		{
			$bindings[] = array('ENTITY_TYPE_ID' => $entityTypeId, 'ENTITY_ID' => $entityId);
		}
		self::registerBindings($createdId, $bindings);

		if ($entityTypeId === \CCrmOwnerType::Activity)
		{
			self::buildSearchContent($createdId);
		}

		return $createdId;
	}
	public static function rebind($entityTypeID, $oldEntityID, $newEntityID)
	{
		Entity\TimelineBindingTable::rebind(
			$entityTypeID,
			$oldEntityID,
			$newEntityID,
			[TimelineType::MARK]
		);
	}
	public static function attach($srcEntityTypeID, $srcEntityID, $targEntityTypeID, $targEntityID)
	{
		Entity\TimelineBindingTable::attach(
			$srcEntityTypeID,
			$srcEntityID,
			$targEntityTypeID,
			$targEntityID,
			[TimelineType::MARK]
		);
	}
}
