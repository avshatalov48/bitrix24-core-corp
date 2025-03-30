<?php
namespace Bitrix\ImConnector;

use Bitrix\Main\ORM\Data;

use Bitrix\ImConnector\Model\ChatLastMessageTable;

class Chat
{
	/**
	 * @param string $externalChatId
	 * @param string $externalMessageId
	 * @param string $connector
	 * @return void
	 */
	public static function setLastMessage($externalChatId, $externalMessageId, $connector): void
	{
		$lastMessage = self::getLastMessage($externalChatId, $connector);
		if (!empty($lastMessage['ID']))
		{
			ChatLastMessageTable::update(
				$lastMessage['ID'],
				[
					'EXTERNAL_MESSAGE_ID' => $externalMessageId
				]
			);
		}
		else
		{
			ChatLastMessageTable::add([
				'EXTERNAL_CHAT_ID' => $externalChatId,
				'CONNECTOR' => $connector,
				'EXTERNAL_MESSAGE_ID' => $externalMessageId
			]);
		}
	}

	/**
	 * @param string $externalChatId
	 * @param string $connector
	 * @return string|null
	 */
	public static function getChatLastMessageId($externalChatId, $connector)
	{
		$message = self::getLastMessage($externalChatId, $connector);

		return $message['EXTERNAL_MESSAGE_ID'] ?? null;
	}

	/**
	 * @param string $externalChatId
	 * @param string $connector
	 * @return array|false
	 */
	public static function getLastMessage($externalChatId, $connector)
	{
		return ChatLastMessageTable::getList(
			[
				'filter' => [
					'=EXTERNAL_CHAT_ID' => $externalChatId,
					'=CONNECTOR' => $connector
				],
				'limit' => '1',
				'order' => [
					'ID' => 'DESC'
				]
			]
		)->fetch();
	}

	/**
	 * @param string $externalChatId
	 * @param string $connector
	 */
	public static function deleteLastMessage($externalChatId, $connector): void
	{
		$lastMessage = self::getLastMessage($externalChatId, $connector);

		if (!empty($lastMessage['ID']))
		{
			ChatLastMessageTable::delete($lastMessage['ID']);
		}
	}
}