<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Main;
use Bitrix\Crm;

trait AddressControllerMixin
{
	use BaseControllerMixin;

	/**
	 * Suspend entity addresses.
	 * @param int $entityID Entity ID.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 */
	protected function suspendAddresses($entityID, $recyclingEntityID)
	{
		Crm\EntityAddress::rebind(
			$this->getEntityTypeID(),
			$entityID,
			$this->getSuspendedEntityTypeID(),
			$recyclingEntityID
		);
	}

	/**
	 * Recover entity addresses.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @param int $newEntityID New Entity ID.
	 */
	protected function recoverAddresses($recyclingEntityID, $newEntityID)
	{
		Crm\EntityAddress::rebind(
			$this->getSuspendedEntityTypeID(),
			$recyclingEntityID,
			$this->getEntityTypeID(),
			$newEntityID
		);
	}

	/**
	 * Erase Suspended Entity Address Fields.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @param array $typeIDs Address Types.
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentOutOfRangeException
	 */
	protected function eraseSuspendedAddresses($recyclingEntityID, array $typeIDs)
	{
		$entityTypeID = $this->getSuspendedEntityTypeID();
		foreach($typeIDs as $typeID)
		{
			Crm\EntityAddress::unregister($entityTypeID, $recyclingEntityID, $typeID);
		}
	}
}