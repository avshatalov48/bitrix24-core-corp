<?php
/**
 * Created by PhpStorm.
 * User: varfolomeev
 * Date: 22.01.2019
 * Time: 12:18
 */

namespace Bitrix\ImConnector;

use \Bitrix\ImConnector\Model\ChatLastMessageTable;

class Chat
{
	/**
	 * @param $fields
	 *
	 * @return \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult
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
		$message = ChatLastMessageTable::getList(
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

		return $message;
	}

	/**
	 * @param $externalChatId
	 * @param $connector
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function deleteLastMessage($externalChatId, $connector)
	{
		$lastMessage = self::getLastMessage($externalChatId, $connector);

		if (!empty($lastMessage['ID']))
		{
			ChatLastMessageTable::delete($lastMessage['ID']);
		}
	}
}