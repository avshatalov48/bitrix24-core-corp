<?php
namespace Bitrix\ImConnector;

use Bitrix\ImConnector\Model\DeliveryMarkTable;
use Bitrix\Main\Application;

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
		$connection = Application::getInstance()->getConnection();
		$query = '
			DELETE
			FROM b_imconnectors_delivery_mark
			WHERE
				CHAT_ID = ' . $chatId . '
				AND MESSAGE_ID <= ' . $messageId . '
		';
		$connection->queryExecute($query);
	}
}
