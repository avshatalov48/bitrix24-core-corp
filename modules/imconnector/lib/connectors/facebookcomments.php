<?php
namespace Bitrix\ImConnector\Connectors;

use Bitrix\Main\Localization\Loc;

use	Bitrix\ImConnector\Chat;
use Bitrix\ImConnector\Result;
use Bitrix\ImConnector\Library;

Loc::loadMessages(__FILE__);

/**
 * Class FacebookComments
 * @package Bitrix\ImConnector\Connectors
 */
class FacebookComments extends Base
{
	//Input
	/**
	 * @param $message
	 * @param $line
	 * @return Result
	 */
	public function processingInputNewMessage($message, $line): Result
	{
		$message = $this->processingLastMessage($message);

		return parent::processingInputNewMessage($message, $line);
	}

	/**
	 * @param $message
	 * @param $line
	 * @return Result
	 */
	public function processingInputUpdateMessage($message, $line): Result
	{
		$message = $this->processingLastMessage($message);

		return parent::processingInputUpdateMessage($message, $line);
	}

	/**
	 * @param $message
	 * @param $line
	 * @return Result
	 */
	public function processingInputDelMessage($message, $line): Result
	{
		$message = $this->processingLastMessage($message);

		return parent::processingInputDelMessage($message, $line);
	}

	/**
	 * @param array $chat
	 * @return array
	 */
	protected function processingChat(array $chat): array
	{
		if (!empty($chat['url']))
		{
			$chat['description'] = Loc::getMessage(
				'IMCONNECTOR_LINK_TO_ORIGINAL_POST_IN_FACEBOOK',
				[
					'#LINK#' => $chat['url']
				]
			);

			unset($chat['url']);
		}

		return $chat;
	}
	//END Input

	//Output
	/**
	 * @param array $message
	 * @param $line
	 * @return array
	 */
	public function sendMessageProcessing(array $message, $line): array
	{
		$message = parent::sendMessageProcessing($message, $line);

		if(!empty($message['message']['files']) || !Library::isEmpty($message['message']['text']))
		{
			$lastMessageId = Chat::getChatLastMessageId($message['chat']['id'], $this->idConnector);

			if (!empty($lastMessageId))
			{
				$message['extra']['last_message_id'] = $lastMessageId;
			}
		}

		return $message;
	}
	//END Output
}