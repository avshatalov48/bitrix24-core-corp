<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Main;

trait ProductRowControllerMixin
{
	use BaseControllerMixin;

	/**
	 * Get Product Row Owner Type
	 * @return string
	 */
	public abstract function getProductRowOwnerType();

	/**
	 * Get Product Row Suspended Owner Type
	 * @return string
	 */
	public abstract function getProductRowSuspendedOwnerType();

	/**
	 * Suspend Product Rows.
	 * @param int $entityID Entity ID.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @throws Main\ArgumentException
	 */
	protected function suspendProductRows($entityID, $recyclingEntityID)
	{
		$ownerType = $this->getProductRowOwnerType();
		$suspendedOwnerType = $this->getProductRowSuspendedOwnerType();

		\CCrmProductRow::Rebind($ownerType, $entityID, $suspendedOwnerType, $recyclingEntityID);
		\CCrmProductRow::RebindSettings($ownerType, $entityID, $suspendedOwnerType, $recyclingEntityID);
	}

	/**
	 * Recover Product Rows.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @param int $newEntityID New Entity ID.
	 * @throws Main\ArgumentException
	 */
	protected function recoverProductRows($recyclingEntityID, $newEntityID)
	{
		$ownerType = $this->getProductRowOwnerType();
		$suspendedOwnerType = $this->getProductRowSuspendedOwnerType();

		\CCrmProductRow::Rebind($suspendedOwnerType, $recyclingEntityID, $ownerType, $newEntityID);
		\CCrmProductRow::RebindSettings($suspendedOwnerType, $recyclingEntityID, $ownerType, $newEntityID);
	}

	/**
	 * Erase suspended Product Rows.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 */
	protected function eraseSuspendProductRows($recyclingEntityID)
	{
		$suspendedOwnerType = $this->getProductRowSuspendedOwnerType();

		\CCrmProductRow::DeleteByOwner($suspendedOwnerType, $recyclingEntityID);
		\CCrmProductRow::DeleteSettings($suspendedOwnerType, $recyclingEntityID);
	}
}