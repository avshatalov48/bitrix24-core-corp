<?php
namespace Bitrix\ImConnector;

use Bitrix\ImConnector\Model\DeliveryMarkTable;

class DeliveryMark
{
	/**
	 * @param int $messageId
	 * @param int $chatId
	 * @return void
	 */
	public static function setDeliveryMark(int $messageId, int $chatId): void
	{
		DeliveryMarkTable::add([
			'MESSAGE_ID' => $messageId,
			'CHAT_ID' => $chatId
		]);
	}

	/**
	 * @param int $messageId
	 * @param int $chatId
	 * @return void
	 */
	public static function unsetDeliveryMark(int $messageId, int $chatId): void
	{
		DeliveryMarkTable::deleteByFilter([
			'=CHAT_ID' => $chatId,
			'<=MESSAGE_ID' => $messageId,
		]);
	}
}
