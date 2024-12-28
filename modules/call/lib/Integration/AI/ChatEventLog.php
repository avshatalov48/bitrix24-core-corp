<?php

namespace Bitrix\Call\Integration\AI;

use Bitrix\Main\Config\Option;
use Bitrix\Im\V2\Chat;
use Bitrix\Im\V2\Message;

class ChatEventLog
{
	protected const DEBUG_OPTION = 'call_debug_chats';
	protected static ?array $chatIds = null;
	public static function registerHandlers(): void
	{
		static $setup = false;
		if (!$setup)
		{
			$eventManager = \Bitrix\Main\EventManager::getInstance();
			$eventManager->addEventHandler('call', 'onCallTrackReady', [static::class, 'onCallTrackReady']);
			$eventManager->addEventHandler('call', 'onCallTrackError', [static::class, 'onCallTrackError']);
			$eventManager->addEventHandler('call', 'onCallAiOutcome', [static::class, 'onCallAiOutcome']);
			$eventManager->addEventHandler('call', 'onCallAiFailed', [static::class, 'onCallAiFailed']);
			$eventManager->addEventHandler('call', 'onCallAiTask', [static::class, 'onCallAiTask']);
			$setup = true;
		}
	}

	//region Option

	public static function getChatDebug(): array
	{
		if (self::$chatIds === null)
		{
			self::$chatIds = array_filter(array_map('intVal', explode(',', Option::get('call', self::DEBUG_OPTION, ''))));
		}

		return self::$chatIds;
	}

	public static function isChatDebugEnabled(int $chatId): bool
	{
		self::getChatDebug();
		return !empty(self::$chatIds) && in_array($chatId, self::$chatIds);
	}

	public static function chatDebugEnable(int $chatId): void
	{
		if ($chatId)
		{
			self::getChatDebug();
			if (!in_array($chatId, self::$chatIds))
			{
				self::$chatIds[] = $chatId;
				Option::set('call', self::DEBUG_OPTION, implode(',', self::$chatIds));
			}
		}
	}

	public static function chatDebugDisable(int $chatId): void
	{
		if ($chatId)
		{
			self::getChatDebug();
			if (($inx = array_search($chatId, self::$chatIds)) !== false)
			{
				unset(self::$chatIds[$inx]);
				if (!empty(self::$chatIds))
				{
					Option::set('call', self::DEBUG_OPTION, implode(',', self::$chatIds));
				}
				else
				{
					Option::delete('call', ['name' => self::DEBUG_OPTION]);
				}
			}
		}
	}

	//endregion

	/**
	 * @see \Bitrix\Call\Track\TrackService::fireTrackReadyEvent
	 */
	public static function onCallTrackReady(\Bitrix\Main\Event $event): void
	{
		$track = $event->getParameters()['track'] ?? null;
		if ($track instanceof \Bitrix\Call\Track)
		{
			if (self::isChatDebugEnabled($track->fillCall()->getChatId()))
			{
				\Bitrix\Main\Loader::includeModule('im');

				$formatSize = \CFile::FormatSize($track->getFileSize());

				$message = new Message();
				$url = \Bitrix\Call\Library::getCallSliderUrl($track->getCallId());
				$message->setMessage(
					"[b]Got track file[/b] for call #[url={$url}]{$track->getCallId()}[/url]"
				);

				$attach = new \CIMMessageParamAttach();
				$attach->AddMessage(
					"Name: {$track->getFileName()}"
					. "[br]Size: {$formatSize}"
					. "[br]Type: {$track->getType()}"
					. "[br]Url: [url={$track->getUrl()}]{$track->getUrl()}[/url]"
				);
				$message->setAttach($attach);

				$chat = Chat::getInstance($track->fillCall()->getChatId());
				$chat->sendMessage($message);
			}
		}
	}

	/**
	 * @see \Bitrix\Call\Track\TrackService::fireTrackErrorEvent
	 */
	public static function onCallTrackError(\Bitrix\Main\Event $event): void
	{
		$track = $event->getParameters()['track'] ?? null;
		$error = $event->getParameters()['error'] ?? null;
		if (
			$track instanceof \Bitrix\Call\Track
			&& $error instanceof \Bitrix\Main\Error
		)
		{
			if (self::isChatDebugEnabled($track->fillCall()->getChatId()))
			{
				\Bitrix\Main\Loader::includeModule('im');

				$message = new Message();
				$url = \Bitrix\Call\Library::getCallSliderUrl($track->getCallId());
				$message->setMessage(
					"[b]Got error with track[/b] for call #[url={$url}]{$track->getCallId()}[/url]"
				);

				$attach = new \CIMMessageParamAttach();
				$attach->AddMessage(
					"Error: ".$error->getMessage()
					. "[br]Error code: ".$error->getCode()
					. "[br]File name: {$track->getFileName()}"
					. "[br]Type: {$track->getType()}"
					. "[br]Url: [url={$track->getUrl()}]{$track->getUrl()}[/url]"
				);
				$message->setAttach($attach);

				$chat = Chat::getInstance($track->fillCall()->getChatId());
				$chat->sendMessage($message);
			}
		}
	}

	/**
	 * @see \Bitrix\Call\Integration\AI\CallAIService::fireCallOutcomeEvent
	 */
	public static function onCallAiOutcome(\Bitrix\Main\Event $event): void
	{
		$outcome = $event->getParameters()['outcome'] ?? null;
		if ($outcome instanceof \Bitrix\Call\Integration\AI\Outcome)
		{
			if (self::isChatDebugEnabled($outcome->fillCall()->getChatId()))
			{
				\Bitrix\Main\Loader::includeModule('im');

				$chat = Chat::getInstance($outcome->fillCall()->getChatId());
				$message = new Message();
				$url = \Bitrix\Call\Library::getCallSliderUrl($outcome->getCallId());
				$message->setMessage(
					"[b]Got AI outcome[/b] for call #[url={$url}]{$outcome->getCallId()}[/url]"
				);

				$attach = new \CIMMessageParamAttach();
				$attach->AddMessage("Outcome type: {$outcome->getType()}");
				if ($outcome->hasContent())
				{
					$attach->AddDelimiter();
					$attach->AddMessage($outcome->getContent());
				}
				foreach ($outcome->getProps() as $prop)
				{
					$attach->AddDelimiter();
					$attach->AddMessage("Property: {$prop->getCode()} [br]Content: " . $prop->getContent());
				}
				$message->setAttach($attach);

				$chat->sendMessage($message);
			}
		}
	}

	/**
	 * @see \Bitrix\Call\Integration\AI\CallAIService::fireCallAiFailedEvent
	 */
	public static function onCallAiFailed(\Bitrix\Main\Event $event): void
	{
		$error = $event->getParameters()['error'] ?? null;
		$task = $event->getParameters()['task'] ?? null;
		if (
			$task instanceof \Bitrix\Call\Integration\AI\Task\AITask
			&& $error instanceof \Bitrix\Main\Error
		)
		{
			if (self::isChatDebugEnabled($task->fillCall()->getChatId()))
			{
				\Bitrix\Main\Loader::includeModule('im');

				$chat = Chat::getInstance($task->fillCall()->getChatId());
				$message = new Message();
				$url = \Bitrix\Call\Library::getCallSliderUrl($task->getCallId());
				$message->setMessage(
					"[b]Got error from AI[/b] for call #[url={$url}]{$task->getCallId()}[/url]"
				);

				$attach = new \CIMMessageParamAttach();
				$attach->AddMessage(
					"AI task hash: ".$task->getHash()
					. "[br]Error code: ".$error->getCode()
					. "[br]Error: ".$error->getMessage()
				);
				$message->setAttach($attach);

				$chat->sendMessage($message);
			}
		}
	}

	/**
	 * @see \Bitrix\Call\Integration\AI\CallAIService::fireCallAiTaskEvent
	 */
	public static function onCallAiTask(\Bitrix\Main\Event $event): void
	{
		$task = $event->getParameters()['task'] ?? null;
		$payload = $event->getParameters()['payload'] ?? null;
		//$context = $event->getParameters()['context'] ?? null;
		$engine = $event->getParameters()['engine'] ?? null;

		if (
			$task instanceof \Bitrix\Call\Integration\AI\Task\AITask
			&& $payload instanceof \Bitrix\AI\Payload\IPayload
			&& $engine instanceof \Bitrix\AI\Engine
		)
		{
			if (self::isChatDebugEnabled($task->fillCall()->getChatId()))
			{
				\Bitrix\Main\Loader::includeModule('im');

				$chat = Chat::getInstance($task->fillCall()->getChatId());
				$message = new Message();
				$url = \Bitrix\Call\Library::getCallSliderUrl($task->getCallId());
				$message->setMessage(
					"[b]Launch AI task[/b] for call #[url={$url}]{$task->getCallId()}[/url]"
				);

				$attach = new \CIMMessageParamAttach();
				$attach->AddMessage(
					'Task type: ' . $task->getType()
					. " [br]Task id: ".$task->getId()
					. ' [br]AI engine: ' . $engine->getCode()
					. ($payload instanceof \Bitrix\AI\Payload\Prompt ? ' [br]Prompt code: ' . $payload->getPromptCode() : '')
				);
				$attach->AddDelimiter();
				$attach->AddMessage("Payload:[br]" . $task->decodePayload($payload->pack()));
				$message->setAttach($attach);

				$chat->sendMessage($message);
			}
		}
	}
}
