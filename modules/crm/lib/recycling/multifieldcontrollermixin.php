<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Main;

trait MultiFieldControllerMixin
{
	use BaseControllerMixin;

	/**
	 * Suspend entity multifields.
	 * @param int $entityID Entity ID.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @throws Main\ArgumentException
	 */
	protected function suspendMultiFields($entityID, $recyclingEntityID)
	{
		\CCrmFieldMulti::Rebind(
			$this->getEntityTypeName(),
			$entityID,
			$this->getSuspendedEntityTypeName(),
			$recyclingEntityID
		);
	}

	/**
	 * Recover entity multifields.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @param int $newEntityID New Entity ID.
	 * @throws Main\ArgumentException
	 */
	protected function recoverMultiFields($recyclingEntityID, $newEntityID)
	{
		\CCrmFieldMulti::Rebind(
			$this->getSuspendedEntityTypeName(),
			$recyclingEntityID,
			$this->getEntityTypeName(),
			$newEntityID
		);
	}

	/**
	 * Erase suspended entity multifields.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 */
	protected function eraseSuspendedMultiFields($recyclingEntityID)
	{
		$multifieldEntity = new \CCrmFieldMulti();
		$multifieldEntity->DeleteByElement($this->getSuspendedEntityTypeName(), $recyclingEntityID);
	}
}