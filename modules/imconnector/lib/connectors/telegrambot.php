<?php

namespace Bitrix\ImConnector\Connectors;

use Bitrix\ImConnector\Output;
use Bitrix\ImConnector\Result;
use Bitrix\ImConnector\Status;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;

/**
 * Class TelegramBot
 * @package Bitrix\ImConnector\Connectors
 */
class TelegramBot extends Base
{
	private const TELEGRAM_BOT = 'telegrambot';

	public function __construct()
	{
		parent::__construct(self::TELEGRAM_BOT);
	}

	/**
	 * @param array $message
	 * @param int $line
	 * @return Result
	 */
	public function processingInputWelcomeMessage(array $message, int $line): Result
	{
		$result = new Result();

		$connectorOutput = new Output(self::TELEGRAM_BOT, $line);
		$statusData = Status::getInstance(self::TELEGRAM_BOT, $line)->getData();

		$user = $this->getUserByUserCode(['id' => $message['user']['id']]);
		if (!$user->isSuccess())
		{
			$addResult = $this->addUser($message['user']);
			if ($addResult->isSuccess())
			{
				$userId = $addResult->getResult();
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

		$fullUserCode = "telegrambot|{$line}|{$message['user']['id']}|{$userId}";
		$chatId = $this->getChatId([
			'USER_CODE' => $fullUserCode,
			'USER_ID' => $userId,
			'CONNECTOR' => $message,
		]);
		if (!$chatId)
		{
			return $result;
		}

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

		$this->sendWelcomeMessage($statusData['welcome_message'], $chatId);
		$connectorOutput->registerEshop($messageToSend);

		return $result;
	}

	/**
	 * @param array $params
	 * @return array|bool|mixed|null
	 */
	private function getChatId(array $params)
	{
		if (!Loader::includeModule('imopenlines'))
		{
			return false;
		}

		$chat = new \Bitrix\ImOpenLines\Chat();
		$chat->load($params);

		return $chat->getData('ID');
	}

	public function sendWelcomeMessage(string $messageText, int $chatId)
	{
		if (empty($messageText))
		{
			return null;
		}

		return $this->sendMessage($messageText, $chatId);
	}

	public function sendAutomaticMessage(string $messageText, string $crmEntityType, int $crmEntityId): ?int
	{
		if (!Loader::includeModule('imopenlines'))
		{
			return null;
		}
		$entityData = \Bitrix\ImOpenLines\Crm\Common::get($crmEntityType, $crmEntityId, true);

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
		$chatId = $this->getChatId(['USER_CODE' => $telegramUserCode]);
		if (!$chatId)
		{
			return null;
		}

		return $this->sendMessage($messageText, $chatId);
	}

	private function sendMessage(string $messageText, int $chatId)
	{
		if (empty($messageText) || $chatId <= 0)
		{
			return null;
		}

		/** @var \Bitrix\ImOpenLines\Services\Message $messenger */
		$messenger = ServiceLocator::getInstance()->get('ImOpenLines.Services.Message');
		return $messenger->addMessage([
			'TO_CHAT_ID' => $chatId,
			'MESSAGE' => $messageText,
			'SYSTEM' => 'Y',
			'IMPORTANT_CONNECTOR' => 'Y',
			'NO_SESSION_OL' => 'Y',
		]);
	}
}