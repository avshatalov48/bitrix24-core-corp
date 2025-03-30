<?php
namespace Bitrix\ImConnector\Connectors;

use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\ImConnector\Chat;
use Bitrix\ImConnector\Result;
use Bitrix\ImConnector\Library;
use Bitrix\ImOpenLines\Session;
use Bitrix\Im\Model\MessageTable;


/**
 * Class FbInstagramDirect
 * @package Bitrix\ImConnector\Connectors
 */
class FbInstagramDirect extends InstagramBase
{
	//region User

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

	//endregion

	//region Input

	/**
	 * @param $message
	 * @param $line
	 * @return Result
	 */
	public function processingInputNewMessage($message, $line): Result
	{
		if (!empty($message['message']['unsupported_message']))
		{
			unset($message['message']['unsupported_message']);

			$message['message']['text'] = Loc::getMessage('IMCONNECTOR_MESSAGE_UNSUPPORTED_INSTAGRAM');
		}
		else
		{
			if ($message['extra']['comment'] === true)
			{
				$message['extra']['last_message_id'] = $message['message']['id'];

				$message = $this->processingLastMessage($message);

				if (!empty($message['message']['url']))
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

	//endregion

	//region Output

	/**
	 * @param array $message
	 * @param int $lineId
	 * @return array
	 */
	public function sendMessageProcessing(array $message, $lineId): array
	{
		$message = parent::sendMessageProcessing($message, $lineId);

		if (!Library::isEmpty($message['message']['text']))
		{
			$lastMessageId = $this->clearLastExtMessage($message['chat']['id']);
			if ($lastMessageId)
			{
				$message['extra']['comment_id'] = $lastMessageId;
			}
		}

		$messageId = (int)($message['im']['message_id'] ?? 0);
		if ($messageId && $this->isHumanAgent($lineId))
		{
			$chatMessage = $this->getMessageById($messageId);
			if (!empty($chatMessage['AUTHOR_ID']))
			{
				$message['message']['long'] = true;
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

	//endregion

	//region Error

	/**
	 * @param $paramsError
	 * @param string $message
	 * @return bool
	 */
	protected function receivedErrorNotSendMessageChat($paramsError, string $message = ''): bool
	{
		if (!empty($paramsError['params']))
		{
			$errCode = $paramsError['params']['additionalCode'] ?? '';
			$errSubCode = (int)($paramsError['params']['errorSubCode'] ?? -1);
			$chatId = (int)($paramsError['chatId'] ?? 0);

			if ($errCode == 'ERROR_INSTAGRAM_NOT_SEND_MESSAGE_FOR_COMMENT')
			{
				if ($chatId)
				{
					$this->clearLastExtMessage($chatId);
				}

				$paramsError['messageConnector'] = '';
				if ($errSubCode == 2534023)
				{
					Loader::includeModule('ui');
					$helpUrl = \Bitrix\UI\Util::getArticleUrlByCode(Library::CODE_ID_ARTICLE_TIME_LIMIT);

					$message = Loc::getMessage('IMCONNECTOR_FBINSTAGRAMDIRECT_NOT_SEND_MESSAGE_COMMENT_HAS_ANSWER', ['#URL#' => $helpUrl]);
				}
				else
				{
					$message = Loc::getMessage('IMCONNECTOR_FBINSTAGRAMDIRECT_NOT_SEND_MESSAGE_FOR_COMMENT_MSGVER_1');
				}
			}

			elseif (
				$errCode == 'ERROR_INSTAGRAM_NOT_SEND_MESSAGE_CHAT_LIMIT'
				|| $errSubCode == 2534022
			)
			{
				Loader::includeModule('ui');
				$helpUrl = \Bitrix\UI\Util::getArticleUrlByCode(Library::CODE_ID_ARTICLE_TIME_LIMIT);

				if ($this->hasChatMessageOutsideAllowedWindow($chatId, $paramsError['line']))
				{
					$paramsError['messageConnector'] = '';

					$message = Loc::getMessage('IMCONNECTOR_FBINSTAGRAMDIRECT_NOT_SEND_MESSAGE_CHAT_LIMIT', [
						'#A_START#' => '[URL='. $helpUrl. ']',
						'#A_END#' => '[/URL]',
					]);

					$messageId = (int)($paramsError['messageId'] ?? 0);
					if ($messageId)
					{
						$chatMessage = $this->getMessageById($messageId);
						if (!empty($chatMessage['AUTHOR_ID']))
						{
							if ($this->isHumanAgent($paramsError['line']))
							{
								$message = Loc::getMessage('IMCONNECTOR_FBINSTAGRAMDIRECT_NOT_SEND_MESSAGE_CHAT_7_DAY_LIMIT', [
									'#A_START#' => '[URL='. $helpUrl. ']',
									'#A_END#' => '[/URL]',
								]);
							}
							else
							{
								$message = Loc::getMessage('IMCONNECTOR_FBINSTAGRAMDIRECT_NOT_SEND_MESSAGE_CHAT_24_HOURS_LIMIT', [
									'#A_START#' => '[URL='. $helpUrl. ']',
									'#A_END#' => '[/URL]',
								]);
							}
						}
					}
				}
				elseif ($this->hasChatMoreThenOneAnswer($chatId, $paramsError['line']))
				{
					$paramsError['messageConnector'] = '';

					$message = Loc::getMessage('IMCONNECTOR_FBINSTAGRAMDIRECT_NOT_SEND_MESSAGE_ONLY_ONE_RESPONSE', ['#URL#' => $helpUrl]);
				}

			}
		}

		return parent::receivedErrorNotSendMessageChat($paramsError, $message);
	}

	/**
	 * Checks if the operator response was sent within the allowed 24 hours window.
	 * @param int $chatId
	 * @param int $lineId
	 * @return bool
	 */
	private function hasChatMessageOutsideAllowedWindow(int $chatId, int $lineId): bool
	{
		Loader::includeModule('im');
		Loader::includeModule('imopenlines');

		$chat = \Bitrix\Im\V2\Chat::getInstance($chatId);
		$connectorUserId = (int)\Bitrix\ImOpenLines\Chat::parseLinesChatEntityId($chat->getEntityId())['connectorUserId'];
		if ($connectorUserId)
		{
			// last connector user's message
			$messageList = MessageTable::getList([
				'select' => [
					'DATE_CREATE',
				],
				'filter' => [
					'=CHAT_ID' => $chatId,
					'=AUTHOR_ID' => $connectorUserId,
				],
				'order' => [
					'ID' => 'DESC'
				],
				'limit' => 1,
			]);
			if ($message = $messageList->fetchObject())
			{
				$diff = (new DateTime())->getDiff($message->getDateCreate());
				if ($this->isHumanAgent($lineId))
				{
					// older than 7 days
					return $diff->days > 7;
				}

				// older than 24 hours
				return $diff->days > 1;
			}
		}

		return false;
	}

	private function hasChatMoreThenOneAnswer(int $chatId, int $lineId): bool
	{
		Loader::includeModule('im');
		Loader::includeModule('imopenlines');

		$answerCount = 0;

		$chat = \Bitrix\Im\V2\Chat::getInstance($chatId);
		$connectorUserId = (int)\Bitrix\ImOpenLines\Chat::parseLinesChatEntityId($chat->getEntityId())['connectorUserId'];
		if ($connectorUserId)
		{
			// last messages
			$messageList = MessageTable::getList([
				'select' => [
					'AUTHOR_ID',
				],
				'filter' => [
					'=CHAT_ID' => $chatId,
					'>AUTHOR_ID' => 0,
				],
				'order' => [
					'ID' => 'DESC'
				],
				'limit' => 3,
			]);
			while ($message = $messageList->fetchObject())
			{
				if ($message->getAuthorId() == $connectorUserId)
				{
					break;
				}
				$answerCount ++;
			}
		}

		return $answerCount >= 1;
	}


	private function getMessageById(int $messageId): ?array
	{
		$message = null;
		if (Loader::includeModule('im'))
		{
			$message = MessageTable::getByPrimary($messageId, [
				'select' => [
					'DATE_CREATE',
					'AUTHOR_ID'
				]
			])?->fetch();
		}

		return $message;
	}

	private function clearLastExtMessage(int $chatId): ?string
	{
		$lastMessageId = Chat::getChatLastMessageId($chatId, $this->idConnector);
		if (!empty($lastMessageId))
		{
			Chat::deleteLastMessage($chatId, $this->idConnector);
		}
		return $lastMessageId;
	}

	//endregion
}
