<?php

namespace Bitrix\ImConnector\Connectors;

use Bitrix\ImConnector\Error;
use Bitrix\ImConnector\Output;
use Bitrix\ImConnector\Result;
use Bitrix\ImConnector\Status;
use Bitrix\ImConnector\Connector;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\ImOpenLines;

/**
 * Class TelegramBot
 * @package Bitrix\ImConnector\Connectors
 */
class TelegramBot extends Base implements MessengerUrl
{
	private const TELEGRAM_BOT = 'telegrambot';

	public function __construct()
	{
		parent::__construct(self::TELEGRAM_BOT);
	}

	/**
	 * Command handler for message like
	 * /start
	 * /start smth
	 * /start btrxSmth
	 *
	 * @param string $command
	 * @param array $message
	 * @param int $line
	 * @return Result
	 */
	public function processingInputCommand(string $command, array $message, int $line): Result
	{
		$result = new Result();

		// getting user id
		$userResult = $this->processingUser($message['user']);
		if ($userResult->isSuccess())
		{
			$userId = $userResult->getResult();
		}
		else
		{
			return $result->addErrors($userResult->getErrors());
		}

		// chat id
		$chat = $this->getChat([
			'USER_CODE' => $this->generateChatCode($line, (int)$message['user']['id'], (int)$userId),
			'USER_ID' => $userId,
			'CONNECTOR' => $message,
		]);
		$chatId = $chat->getData('ID');
		if (!$chatId)
		{
			return $result->addError(new Error(
				'Failed to create chat',
				'ERROR_IMCONNECTOR_FAILED_CHAT',
				__METHOD__,
				$message
			));
		}

		$statusData = Status::getInstance(self::TELEGRAM_BOT, $line)->getData();

		if (!empty($statusData['welcome_message']))
		{
			$this->sendMessage($statusData['welcome_message'], $chatId);
		}

		if (
			!empty($statusData['eshop_enabled'])
			&& $statusData['eshop_enabled'] == 'Y'
		)
		{
			$messageToSend = [
				'chatId' => $message['chat']['id'],
				'userId' => $userId,
				'lineId' => $line,
			];
			$connectorOutput = new Output(self::TELEGRAM_BOT, $line);
			$connectorOutput->registerEshop($messageToSend);
		}

		if ($result->isSuccess())
		{
			// getting user id
			$message['user'] = $userId;

			$result->setResult($message);
		}

		return $result;
	}

	/**
	 * @param array $message
	 * @param int $line
	 * @return Result
	 * @deprecated
	 */
	public function processingInputWelcomeMessage(array $message, int $line): Result
	{
		$result = new Result();

		$telegramUserId = (int)$message['user']['id'];

		$userId = 0;
		$user = $this->getUserByUserCode(['id' => $telegramUserId]);
		if (!$user->isSuccess())
		{
			$addResult = $this->addUser($message['user']);
			if ($addResult->isSuccess())
			{
				$userId = (int)$addResult->getResult();
			}
			else
			{
				$result->addErrors($addResult->getErrors());
			}
		}
		else
		{
			$userId = (int)$user->getResult()['ID'];
		}
		if (!$userId)
		{
			return $result;
		}

		$fullUserCode = $this->generateChatCode($line, $telegramUserId, $userId);
		$chat = $this->getChat([
			'USER_CODE' => $fullUserCode,
			'USER_ID' => $userId,
			'CONNECTOR' => $message,
		]);
		$chatId = $chat->getData('ID');
		if (!$chatId)
		{
			return $result;
		}

		// CRM expectation
		if (
			!empty($message['ref']['source']) // start parameter
			&& strpos($message['ref']['source'], self::REF_PREFIX) === 0 // start parameter begins with "btrx" prefix
			&& Loader::includeModule('imopenlines')
		)
		{
			$session = new ImOpenLines\Session();
			$session->setChat($chat);

			$session->load([
				'USER_CODE' => $fullUserCode,
				'CONFIG_ID' => $line,
				'USER_ID' => $userId,
				'SOURCE' => self::TELEGRAM_BOT,
				'MODE' => ImOpenLines\Session::MODE_INPUT,
				'CRM_TRACKER_REF' => $message['ref']['source'] ?? '',
			]);
		}

		$connectorOutput = new Output(self::TELEGRAM_BOT, $line);
		$statusData = Status::getInstance(self::TELEGRAM_BOT, $line)->getData();

		$messageToSend = [
			'chatId' => $message['chat']['id'],
			'userId' => $userId,
			'lineId' => $line,
		];
		if (!$statusData['welcome_message'])
		{
			if ($statusData['eshop_url'])
			{
				return $connectorOutput->registerEshop($messageToSend);
			}

			return $result;
		}

		if (!empty($statusData['welcome_message']))
		{
			$this->sendMessage($statusData['welcome_message'], $chatId);
		}

		$connectorOutput->registerEshop($messageToSend);

		return $result;
	}

	private function generateChatCode(int $lineId, int $connectorUserId, int $userId): string
	{
		return implode('|', [self::TELEGRAM_BOT, $lineId, $connectorUserId, $userId]);
	}

	/**
	 * @param array $params
	 * @return ImOpenLines\Chat|null
	 */
	private function getChat(array $params): ?ImOpenLines\Chat
	{
		if (!Loader::includeModule('imopenlines'))
		{
			return null;
		}

		$chat = new ImOpenLines\Chat();
		$chat->load($params);

		return $chat;
	}

	/**
	 * @param string $messageText
	 * @param string $crmEntityType
	 * @param int $crmEntityId
	 * @return int|null
	 */
	public function sendAutomaticMessage(string $messageText, string $crmEntityType, int $crmEntityId): ?int
	{
		if (!Loader::includeModule('imopenlines'))
		{
			return null;
		}
		$entityData = ImOpenLines\Crm\Common::get(
			$crmEntityType,
			$crmEntityId,
			true,
			[
				'ID',
			]
		);

		$lastTelegramImol = null;
		if (isset($entityData['FM']['IM']['TELEGRAM']) && is_array($entityData['FM']['IM']['TELEGRAM']))
		{
			$lastTelegramImol = end($entityData['FM']['IM']['TELEGRAM']);
		}

		if (!$lastTelegramImol)
		{
			return null;
		}

		$telegramUserCode = mb_substr($lastTelegramImol, 5); //cut "imol|"
		$chat = $this->getChat(['USER_CODE' => $telegramUserCode]);
		$chatId = $chat->getData('ID');
		if (!$chatId)
		{
			return null;
		}

		return $this->sendMessage($messageText, $chatId);
	}

	/**
	 * @param string $messageText
	 * @param int $chatId
	 * @return false|int
	 */
	private function sendMessage(string $messageText, int $chatId)
	{
		if (empty($messageText) || $chatId <= 0)
		{
			return null;
		}

		/** @var ImOpenLines\Services\Message $messenger */
		$messenger = ServiceLocator::getInstance()->get('ImOpenLines.Services.Message');

		return $messenger->addMessage([
			'TO_CHAT_ID' => $chatId,
			'MESSAGE' => $messageText,
			'SYSTEM' => 'Y',
			'IMPORTANT_CONNECTOR' => 'Y',
			'NO_SESSION_OL' => 'Y',
		]);
	}

	/**
	 * Generate url to redirect into messenger app.
	 * @see https://core.telegram.org/api/links#bot-links
	 *
	 * @param int $lineId
	 * @param array|string|null $additional
	 * @return array{web: string, mob: string}
	 */
	public function getMessengerUrl(int $lineId, $additional = null): array
	{
		$result = [];
		$url = null;
		$connectorData = Connector::infoConnectorsLine($lineId);
		if (isset($connectorData[self::TELEGRAM_BOT]))
		{
			$url = $connectorData[self::TELEGRAM_BOT]['url_im'] ?? $connectorData[self::TELEGRAM_BOT]['url'] ?? '';
		}
		else
		{
			$connectorOutput = new Output(self::TELEGRAM_BOT, $lineId);
			$infoConnect = $connectorOutput->infoConnect();

			if ($infoConnect->isSuccess())
			{
				$url = $infoConnect->getData()['url'];
			}
		}

		if ($url)
		{
			$result = [
				'web' => $url,
				'mob' => str_replace('https://t.me/', 'tg://resolve?domain=', $url),
			];

			if (!empty($additional))
			{
				if (is_array($additional))
				{
					$additional = base64_encode(http_build_query($additional));
				}
				$result['web'] .= '?start='. $additional;
				$result['mob'] .= '&start='. $additional;
			}
		}

		return $result;
	}
}