<?php

namespace Bitrix\Call;

use Bitrix\Call\Integration\AI\ChatMessage;
use Bitrix\Im\V2\Chat;
use Bitrix\Im\V2\Message;
use Bitrix\Im\V2\Message\Params;
use Bitrix\Im\V2\MessageCollection;
use Bitrix\Im\V2\Service\Context;
use Bitrix\Main\Application;

class NotifyService
{
	/** @see \Bitrix\Im\Call\Integration\Chat::onStateChange */
	public const
		MESSAGE_COMPONENT_ID = 'CallMessage'
	;
	public const
		MESSAGE_TYPE_START = 'START',
		MESSAGE_TYPE_FINISH = 'FINISH',
		MESSAGE_TYPE_DECLINED = 'DECLINED',
		MESSAGE_TYPE_BUSY = 'BUSY',
		MESSAGE_TYPE_MISSED = 'MISSED',
		MESSAGE_TYPE_ERROR = 'ERROR',
		MESSAGE_TYPE_AI_OVERVIEW = 'AI_OVERVIEW',
		MESSAGE_TYPE_AI_START = 'AI_START',
		MESSAGE_TYPE_AI_FAILED = 'AI_FAILED'
	;

	private static ?NotifyService $service = null;

	private function __construct()
	{}

	public static function getInstance(): self
	{
		if (!self::$service)
		{
			self::$service = new self();
		}
		return self::$service;
	}

	public function sendMessage(Chat $chat, Message $message): void
	{
		$chat
			->setContext(new Context)
			->sendMessage($message);
	}

	public function sendError(Chat $chat, Message $message): void
	{
		$chat
			->setContext(new Context)
			->sendMessage($message);
	}

	public function sendMessageDeferred(Chat $chat, Message $message): void
	{
		Application::getInstance()->addBackgroundJob([$this, 'sendMessage'], [$chat, $message], Application::JOB_PRIORITY_LOW);
	}

	public function findMessage(Chat $chat, int $callId, string $messageType, int $depth = 100): ?Message
	{
		$messages = MessageCollection::find(['CHAT_ID' => $chat->getId()], ['ID' => 'DESC'], $depth);
		if ($messages->count() === 0)
		{
			return null;
		}

		$messages->fillParams();

		foreach ($messages as $message)
		{
			$params = $message->getParams();

			/** @see \Bitrix\Im\Call\Integration\Chat::onStateChange */
			if (
				//todo: Return COMPONENT_ID for call message
				//$params->isSet(Params::COMPONENT_ID)
				//&& $params->get(Params::COMPONENT_ID)->getValue() == self::MESSAGE_COMPONENT_ID
				$params->isSet(Params::COMPONENT_PARAMS)
				&& isset($params->get(Params::COMPONENT_PARAMS)->getValue()['MESSAGE_TYPE'])
				&& $params->get(Params::COMPONENT_PARAMS)->getValue()['MESSAGE_TYPE'] == $messageType
				&& $params->get(Params::COMPONENT_PARAMS)->getValue()['CALL_ID'] == $callId
			)
			{
				return $message;
			}
		}

		return null;
	}
}