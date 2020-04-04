<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Main;
use Bitrix\Crm;

trait ObserverControllerMixin
{
	use BaseControllerMixin;

	/**
	 * Suspend Observers.
	 * @param int $entityID Entity ID.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @return void
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\ObjectException
	 */
	protected function suspendObservers($entityID, $recyclingEntityID)
	{
		Crm\Observer\ObserverManager::transferOwnership(
			$this->getEntityTypeID(), $entityID,
			$this->getSuspendedEntityTypeID(), $recyclingEntityID
		);
	}

	/**
	 * Recover Suspended Observers.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @param int $newEntityID New Entity ID.
	 * @return void
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\ObjectException
	 */
	protected function recoverObservers($recyclingEntityID, $newEntityID)
	{
		Crm\Observer\ObserverManager::transferOwnership(
			$this->getSuspendedEntityTypeID(), $recyclingEntityID,
			$this->getEntityTypeID(), $newEntityID
		);
	}

	/**
	 * Erase Suspended Observers.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @return void
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentOutOfRangeException
	 */
	protected function eraseSuspendedObservers($recyclingEntityID)
	{
		Crm\Observer\ObserverManager::deleteByOwner($this->getSuspendedEntityTypeID(), $recyclingEntityID);
	}
}