<?php
namespace Bitrix\ImConnector\Connectors;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

use Bitrix\ImConnector\Chat;
use Bitrix\ImConnector\Result;
use Bitrix\ImConnector\Library;

use Bitrix\ImOpenLines\Session;

Loc::loadMessages(__FILE__);

/**
 * Class FbInstagramDirect
 * @package Bitrix\ImConnector\Connectors
 */
class FbInstagramDirect extends InstagramBase
{
	//User
	/**
	 * @param array $params
	 * @param Result $result
	 * @return Result
	 */
	protected function getUserData(array $params, Result $result): Result
	{
		$result->setResult([
			'ID' => $params['ID_FB_INSTAGRAM_DIRECT'],
			'MD5' => $params['MD5_FB_INSTAGRAM_DIRECT']
		]);

		return $result;
	}

	//Input
	/**
	 * @param $message
	 * @param $line
	 * @return Result
	 */
	public function processingInputNewMessage($message, $line): Result
	{
		if(!empty($message['message']['unsupported_message']))
		{
			unset($message['message']['unsupported_message']);

			$message['message']['text'] = Loc::getMessage('IMCONNECTOR_MESSAGE_UNSUPPORTED_INSTAGRAM');
		}
		else
		{
			if($message['extra']['comment'] === true)
			{
				$message['extra']['last_message_id'] = $message['message']['id'];

				$message = $this->processingLastMessage($message);

				if(!empty($message['message']['url']))
				{
					$message['message']['description'] =
						Loc::getMessage('IMCONNECTOR_MESSAGE_LINK_COMMENT_MEDIA_INSTAGRAM', [
							'#URL#' => htmlspecialcharsbx($message['message']['url'])
						]);

					unset($message['message']['url']);
				}
			}
			else
			{
				Chat::deleteLastMessage($message['chat']['id'], $this->idConnector);
			}

			unset($message['extra']['comment']);
		}

		return parent::processingInputNewMessage($message, $line);
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

		if(!Library::isEmpty($message['message']['text']))
		{
			$lastMessageId = Chat::getChatLastMessageId($message['chat']['id'], $this->idConnector);

			if (!empty($lastMessageId))
			{
				$message['extra']['comment_id'] = $lastMessageId;

				Chat::deleteLastMessage($message['chat']['id'], $this->idConnector);
			}
		}

		return $message;
	}

	/**
	 * @param Session $session
	 * @return bool
	 */
	public function isEnableSendSystemMessage(Session $session): bool
	{
		$result = true;
		if (
			Loader::includeModule('imopenlines')
			&& $session->getData('STATUS') < Session::STATUS_CLIENT
		)
		{
			$externalChatId = Session\Common::parseUserCode($session->getData('USER_CODE'))['EXTERNAL_CHAT_ID'];
			if (!empty($externalChatId))
			{
				$lastMessageId = Chat::getChatLastMessageId($externalChatId, $session->getData('SOURCE'));

				if (!empty($lastMessageId))
				{
					$result = false;
				}
			}

		}

		return $result;
	}
	//END Output

	/**
	 * @param $paramsError
	 * @param string $message
	 * @return bool
	 */
	protected function receivedErrorNotSendMessageChat($paramsError, string $message = ''): bool
	{
		if (!empty($paramsError['params']))
		{
			if ($paramsError['params']['additionalCode'] === 'ERROR_INSTAGRAM_NOT_SEND_MESSAGE_FOR_COMMENT')
			{
				$paramsError['messageConnector'] = '';
				$message = Loc::getMessage('IMCONNECTOR_FBINSTAGRAMDIRECT_NOT_SEND_MESSAGE_FOR_COMMENT');
			}
			elseif (
				(int)$paramsError['params']['errorCode'] === 10
				&& (int)$paramsError['params']['errorSubCode'] === 2534022
			)
			{
				$paramsError['messageConnector'] = '';
				$message = Loc::getMessage('IMCONNECTOR_FBINSTAGRAMDIRECT_NOT_SEND_MESSAGE_OUTSIDE_ALLOWED_WINDOW');
			}
		}

		return parent::receivedErrorNotSendMessageChat($paramsError, $message);
	}
}
