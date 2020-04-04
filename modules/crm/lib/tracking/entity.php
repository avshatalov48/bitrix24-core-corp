<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking;

/**
 * Class Entity
 *
 * @package Bitrix\Crm\Tracking
 */
class Entity
{
	/*
	 * Delete trace from entity.
	 *
	 * @param int $fromEntityTypeId From entity type ID.
	 * @param int $fromEntityId From entity ID.
	 * @return void
	 */
	public static function deleteTrace($entityTypeId, $entityId)
	{
		Internals\TraceEntityTable::removeEntity($entityTypeId, $entityId);
	}

	/**
	 * Copy trace from entity to entity.
	 *
	 * @param int $fromEntityTypeId From entity type ID.
	 * @param int $fromEntityId From entity ID.
	 * @param int $toEntityTypeId To entity type ID.
	 * @param int $toEntityId To entity ID.
	 * @return void
	 */
	public static function copyTrace($fromEntityTypeId, $fromEntityId, $toEntityTypeId, $toEntityId)
	{
		$row = Internals\TraceEntityTable::getRowByEntity($fromEntityTypeId, $fromEntityId);
		if (!$row)
		{
			return;
		}

		Internals\TraceEntityTable::addEntity($row['TRACE_ID'], $toEntityTypeId, $toEntityId);
	}
}