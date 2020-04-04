<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Main;
use Bitrix\Crm;

trait ChatControllerMixin
{
	use BaseControllerMixin;

	/**
	 * Suspend Chats.
	 * @param int $entityID Entity ID.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @throws Main\ArgumentException
	 */
	protected function suspendChats($entityID, $recyclingEntityID)
	{
		Crm\Integration\Im\Chat::transferOwnership(
			$this->getEntityTypeID(), $entityID,
			$this->getSuspendedEntityTypeID(), $recyclingEntityID
		);
	}

	/**
	 * Recover Suspended Chats.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @param int $newEntityID New Entity ID.
	 * @throws Main\ArgumentException
	 */
	protected function recoverChats($recyclingEntityID, $newEntityID)
	{
		Crm\Integration\Im\Chat::transferOwnership(
			$this->getSuspendedEntityTypeID(), $recyclingEntityID,
			$this->getEntityTypeID(), $newEntityID
		);
	}

	/**
	 * Erase Suspended Chats.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 */
	protected function eraseSuspendedChats($recyclingEntityID)
	{
		Crm\Integration\Im\Chat::deleteChat(
			array(
				'ENTITY_TYPE_ID' => $this->getSuspendedEntityTypeID(),
				'ENTITY_ID' => $recyclingEntityID
			)
		);
	}
}