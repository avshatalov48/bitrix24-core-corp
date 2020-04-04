<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Main;
use Bitrix\Crm;

trait WaitingControllerMixin
{
	use BaseControllerMixin;

	/**
	 * Suspend Waitings.
	 * @param int $entityID Entity ID.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @throws Main\ArgumentException
	 */
	protected function suspendWaitings($entityID, $recyclingEntityID)
	{
		Crm\Pseudoactivity\WaitEntry::transferOwnership(
			$this->getEntityTypeID(), $entityID,
			$this->getSuspendedEntityTypeID(), $recyclingEntityID
		);
	}

	/**
	 * Recover Waitings.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @param int $newEntityID New Entity ID.
	 * @throws Main\ArgumentException
	 */
	protected function recoverWaitings($recyclingEntityID, $newEntityID)
	{
		Crm\Pseudoactivity\WaitEntry::transferOwnership(
			$this->getSuspendedEntityTypeID(), $recyclingEntityID,
			$this->getEntityTypeID(), $newEntityID
		);
	}

	/**
	 * Erase Suspended Waitings.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 */
	protected function eraseSuspendedWaitings($recyclingEntityID)
	{
		Crm\Pseudoactivity\WaitEntry::deleteByOwner($this->getSuspendedEntityTypeID(), $recyclingEntityID);
	}
}