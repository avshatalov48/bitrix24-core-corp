<?php

namespace Bitrix\Call;

use Bitrix\Call\Integration\AI\ChatMessage;
use Bitrix\Call\Analytics\FollowUpAnalytics;
use Bitrix\Im\Call\Call;
use Bitrix\Im\V2\Chat;
use Bitrix\Im\V2\Chat\ChatFactory;
use Bitrix\Im\V2\Message;
use Bitrix\Im\V2\Message\Params;
use Bitrix\Im\V2\Message\Send\SendingConfig;
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
		MESSAGE_TYPE_AI_FAILED = 'AI_FAILED',
		MESSAGE_TYPE_AI_INFO = 'AI_INFO',
		MESSAGE_TYPE_AI_WAIT = 'AI_WAIT',
		MESSAGE_TYPE_AI_DESTROY = 'AI_DESTROY'
	;

	public const ADMIN_NOTIFICATION_TAG = 'call_registration';

	private static ?NotifyService $service = null;

	private array $shownMessage = [];

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

	public function sendTaskFailedMessage(\Bitrix\Main\Error $error, Call $call, int $checkDuplicateDepth = 3): void
	{
		if (isset($this->shownMessage[self::MESSAGE_TYPE_AI_FAILED][$call->getId()]))
		{
			return;
		}
		$this->shownMessage[self::MESSAGE_TYPE_AI_FAILED][$call->getId()] = true;

		$chat = Chat::getInstance($call->getChatId());

		if ($chat->getId() > 0)
		{
			if (
				$checkDuplicateDepth <= 0
				||
				$this->findMessage($chat->getId(), $call->getId(), self::MESSAGE_TYPE_AI_FAILED, $checkDuplicateDepth) === null
			)
			{
				$message = ChatMessage::generateTaskFailedMessage($call->getId(), $error, $chat);
				if ($message)
				{
					$sendingConfig = (new SendingConfig())
						->enableSkipCounterIncrements()
						->enableSkipUrlIndex()
					;
					$this->sendMessageDeferred($chat, $message, $sendingConfig);

					(new FollowUpAnalytics($call))->addFollowUpErrorMessage($error->getCode() ?? 'UNDEFINED');
				}
			}
		}
	}

	public function sendCallError(\Bitrix\Main\Error $error, Call $call, int $checkDuplicateDepth = 3): void
	{
		if (isset($this->shownMessage[self::MESSAGE_TYPE_AI_FAILED][$call->getId()]))
		{
			return;
		}
		$this->shownMessage[self::MESSAGE_TYPE_AI_FAILED][$call->getId()] = true;

		$chat = Chat::getInstance($call->getChatId());

		if ($chat->getId())
		{
			if (
				$checkDuplicateDepth <= 0
				|| $this->findMessage($chat->getId(), $call->getId(), self::MESSAGE_TYPE_AI_FAILED, $checkDuplicateDepth) === null
			)
			{
				$errorMessage = ChatMessage::generateErrorMessage($error, $chat, $call);
				if ($errorMessage)
				{
					$this->sendError($chat, $errorMessage);

					(new FollowUpAnalytics($call))->addFollowUpErrorMessage($error->getCode() ?? 'UNDEFINED');
				}
			}
		}
	}

	public function sendTaskWaitMessage(Call $call): void
	{
		if (isset($this->shownMessage[self::MESSAGE_TYPE_AI_WAIT][$call->getId()]))
		{
			return;
		}
		$this->shownMessage[self::MESSAGE_TYPE_AI_WAIT][$call->getId()] = true;

		$chat = Chat::getInstance($call->getChatId());

		if (
			$chat->getId()
			&& $this->findMessage($chat->getId(), $call->getId(), self::MESSAGE_TYPE_AI_WAIT, 3) === null
		)
		{
			$message = ChatMessage::generateWaitMessage($call, $chat);
			if ($message)
			{
				$sendingConfig = (new SendingConfig())
					->enableSkipCounterIncrements()
					->enableSkipUrlIndex()
				;
				$this->sendMessageDeferred($chat, $message, $sendingConfig);
			}
		}
	}

	public function sendOpponentBusyMessage(int $currentUserId, int $opponentUserId): void
	{
		$chat = ChatFactory::getInstance()->getPrivateChat($currentUserId, $opponentUserId);
		if ($chat->getId() > 0)
		{
			$message = ChatMessage::generateOpponentBusyMessage($opponentUserId);
			if ($message)
			{
				$sendingConfig = (new SendingConfig)
					->disableSkipCounterIncrements()
					->enableSkipUrlIndex()
				;
				$context = (new Context())->setUser($opponentUserId);
				$this->sendMessageDeferred($chat, $message, $sendingConfig, $context);
			}
		}
	}

	public function sendMessage(Chat $chat, Message $message, ?SendingConfig $sendingConfig = null, ?Context $context = null): void
	{
		$chat
			->setContext($context ?? new Context)
			->sendMessage($message, $sendingConfig);
	}

	public function sendError(Chat $chat, Message $message, ?SendingConfig $sendingConfig = null, ?Context $context = null): void
	{
		$chat
			->setContext($context ?? new Context)
			->sendMessage($message, $sendingConfig);
	}

	public function sendMessageDeferred(Chat $chat, Message $message, ?SendingConfig $sendingConfig = null, ?Context $context = null): void
	{
		Application::getInstance()->addBackgroundJob([$this, 'sendMessage'], [$chat, $message, $sendingConfig, $context], Application::JOB_PRIORITY_LOW);
	}

	public function findMessage(int $chatId, int $callId, string $messageType, int $depth = 100): ?Message
	{
		$messages = MessageCollection::find(['CHAT_ID' => $chatId], ['ID' => 'DESC'], $depth);
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
				$params->isSet(Params::COMPONENT_PARAMS)
				&& isset($params->get(Params::COMPONENT_PARAMS)->getValue()['MESSAGE_TYPE'])
				&& $params->get(Params::COMPONENT_PARAMS)->getValue()['CALL_ID'] == $callId
			)
			{
				if ($params->get(Params::COMPONENT_PARAMS)->getValue()['MESSAGE_TYPE'] == $messageType)
				{
					return $message;
				}
				if ($params->get(Params::COMPONENT_PARAMS)->getValue()['MESSAGE_TYPE'] == self::MESSAGE_TYPE_START)
				{
					break;
				}
			}
		}

		return null;
	}

	/**
	 * @param int $chatId
	 * @param int $callId
	 * @param int $depth
	 * @return MessageCollection<Message>
	 */
	public function findMessagesForCall(int $chatId, int $callId, int $depth = 100): MessageCollection
	{
		$result = new MessageCollection();

		$messages = MessageCollection::find(['CHAT_ID' => $chatId], ['ID' => 'DESC'], $depth);
		if ($messages->count() === 0)
		{
			return $result;
		}

		$messages->fillParams();

		foreach ($messages as $message)
		{
			$params = $message->getParams();

			/** @see \Bitrix\Im\Call\Integration\Chat::onStateChange */
			if (
				$params->isSet(Params::COMPONENT_PARAMS)
				&& $params->get(Params::COMPONENT_PARAMS)->getValue()['CALL_ID'] == $callId
			)
			{
				$result->add($message);

				if ($params->get(Params::COMPONENT_PARAMS)->getValue()['MESSAGE_TYPE'] == self::MESSAGE_TYPE_START)
				{
					break;
				}
			}
		}

		return $result;
	}

	public function addAdminNotify(string $message): void
	{
		\CAdminNotify::add([
			'MESSAGE' => $message,
			'TAG' => self::ADMIN_NOTIFICATION_TAG,
			'MODULE_ID' => 'call',
			'ENABLE_CLOSE' => 'Y',
			'NOTIFY_TYPE' => \CAdminNotify::TYPE_NORMAL,
		]);
	}

	public function addAdminNotifyError(string $message): void
	{
		\CAdminNotify::add([
			'MESSAGE' => $message,
			'TAG' => self::ADMIN_NOTIFICATION_TAG,
			'MODULE_ID' => 'call',
			'ENABLE_CLOSE' => 'Y',
			'NOTIFY_TYPE' => \CAdminNotify::TYPE_ERROR,
		]);
	}
	public function clearAdminNotify(): void
	{
		\CAdminNotify::DeleteByTag(self::ADMIN_NOTIFICATION_TAG);
	}
}