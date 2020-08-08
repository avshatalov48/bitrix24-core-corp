<?php
namespace Bitrix\ImConnector\Connectors;

use \Bitrix\Main\Localization\Loc;
use	\Bitrix\ImConnector\Chat,
	\Bitrix\ImConnector\Library;

Loc::loadMessages(__FILE__);

/**
 * Class FacebookComments
 * @package Bitrix\ImConnector\Connectors
 */
class FacebookComments extends Base
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