<?php
namespace Bitrix\ImOpenLines\Im\Messages;

use Bitrix\ImOpenLines\Im;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class Error
 * @package Bitrix\ImOpenLines\Im
 */
class Error
{
	/**
	 * @param $chatId
	 * @param $message
	 * @param string $messageExternalError
	 * @return bool|int
	 */
	public static function addErrorNotSendChat($chatId, $message, string $messageExternalError = '')
	{
		return self::sendError($chatId, $message, $messageExternalError);
	}

	/**
	 * @param $chatId
	 * @param $message
	 * @param string $messageExternalError
	 * @return bool|int
	 */
	public static function addErrorNotDeleteChat($chatId, $message, string $messageExternalError = '')
	{
		return self::sendError($chatId, $message, $messageExternalError);
	}

	/**
	 * @param $chatId
	 * @param $message
	 * @param string $messageExternalError
	 * @return bool|int
	 */
	protected static function sendError($chatId, $message, string $messageExternalError = '')
	{
		if(!empty($messageExternalError))
		{
			$message .= "[BR][BR]";
			$message .= "------------------------------------------------------\n";
			$message .= $messageExternalError;
			$message .= "\n------------------------------------------------------";
		}

		return Im::addMessage([
			'TO_CHAT_ID' => $chatId,
			'MESSAGE' => $message,
			'SYSTEM' => 'Y',
			'URL_PREVIEW' => 'N',
			'NO_SESSION_OL' => 'Y',
		]);
	}
}