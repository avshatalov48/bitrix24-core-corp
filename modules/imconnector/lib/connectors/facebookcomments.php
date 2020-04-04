<?php
namespace Bitrix\ImConnector\Connectors;

use \Bitrix\Main\UserTable,
	\Bitrix\Main\Loader,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\ImConnector\Chat,
	\Bitrix\ImConnector\Status,
	\Bitrix\ImConnector\Output,
	\Bitrix\ImConnector\Library,
	\Bitrix\ImConnector\Connector,
	\Bitrix\ImOpenLines\Model\SessionTable;

Loc::loadMessages(__FILE__);

/**
 * Class Instagram
 * @package Bitrix\ImConnector\Connectors
 */
class FacebookComments
{
	/**
	 * @param $value
	 * @param $connector
	 *
	 * @return mixed
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function sendMessageProcessing($value, $connector)
	{
		if(($connector == Library::ID_FB_COMMENTS_CONNECTOR) && !Library::isEmpty($value['message']['text']))
		{
			$lastMessageId = Chat::getChatLastMessageId($value['chat']['id'], $connector);

			if (!empty($lastMessageId))
			{
				$value['extra']['last_message_id'] = $lastMessageId;
			}
		}

		return $value;
	}

}