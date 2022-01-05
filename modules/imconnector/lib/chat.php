<?php
namespace Bitrix\ImConnector;

use Bitrix\Main\ORM\Data;

use Bitrix\ImConnector\Model\ChatLastMessageTable;

class Chat
{
	/**
	 * @param $fields
	 *
	 * @return Data\AddResult|Data\UpdateResult
	 */
	public static function setLastMessage($fields)
	{
		$lastMessage = self::getLastMessage($fields['EXTERNAL_CHAT_ID'], $fields['CONNECTOR']);
		if (!empty($lastMessage['ID']))
		{
			$result = ChatLastMessageTable::update(
				$lastMessage['ID'],
				[
					'EXTERNAL_MESSAGE_ID' => $fields['EXTERNAL_MESSAGE_ID']
				]
			);
		}
		else
		{
			$result = ChatLastMessageTable::add(
				[
					'EXTERNAL_CHAT_ID' => $fields['EXTERNAL_CHAT_ID'],
					'CONNECTOR' => $fields['CONNECTOR'],
					'EXTERNAL_MESSAGE_ID' => $fields['EXTERNAL_MESSAGE_ID']
				]
			);
		}
		return $result;
	}

	/**
	 * @param $externalChatId
	 * @param $connector
	 *
	 * @return mixed
	 */
	public static function getChatLastMessageId($externalChatId, $connector)
	{
		$message = self::getLastMessage($externalChatId, $connector);

		return $message['EXTERNAL_MESSAGE_ID'];
	}

	/**
	 * @param $externalChatId
	 * @param $connector
	 *
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
	 * @param $externalChatId
	 * @param $connector
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