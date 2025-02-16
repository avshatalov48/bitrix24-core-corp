<?php

namespace Bitrix\Call\Integration\AI;

use Bitrix\Im\V2\Chat;
use Bitrix\Im\Call\Registry;
use Bitrix\Call\NotifyService;
use Bitrix\Call\Integration\AI\Outcome\OutcomeCollection;


class EventService
{
	/**
	 * @see \Bitrix\Im\Call\Call::fireCallFinishedEvent
	 */
	public static function onCallFinished(\Bitrix\Main\Event $event): void
	{
		if (!CallAISettings::isCallAIEnable())
		{
			return;
		}

		$call = $event->getParameters()['call'] ?? null;

		if (
			$call instanceof \Bitrix\Im\Call\Call
			&& $call->isAiAnalyzeEnabled()
		)
		{
			$chat = Chat::getInstance($call->getChatId());

			$minDuration = CallAISettings::getRecordMinDuration();
			if ($call->getDuration() < $minDuration)
			{
				$call
					->disableAudioRecord()
					->disableAiAnalyze()
					->save();

				return;
			}

			$message = ChatMessage::generateCallFinishedMessage($call, $chat);
			if ($message)
			{
				$message->setAuthorId($call->getInitiatorId());
				$notifyService = \Bitrix\Call\NotifyService::getInstance();
				$notifyService->sendMessageDeferred($chat, $message);
			}
		}
	}

	/**
	 * @see \Bitrix\Call\Integration\AI\CallAIService::fireCallAiTaskEvent
	 */
	public static function onCallAiTaskStart(\Bitrix\Main\Event $event): void
	{
		if (!CallAISettings::isCallAIEnable())
		{
			return;
		}

		$task = $event->getParameters()['task'] ?? null;

		if ($task instanceof \Bitrix\Call\Integration\AI\Task\TranscribeCallRecord)
		{
			/*
			$chat = Chat::getInstance($task->fillCall()->getChatId());

			$message = ChatMessage::generateTaskStartMessage($task, $chat);
			if ($message)
			{
				//$chat->sendMessage($message);
				$notifyService = \Bitrix\Call\NotifyService::getInstance();
				$notifyService->sendMessageDeferred($chat, $message);
			}
			*/
		}
	}

	/**
	 * @see \Bitrix\Call\Integration\AI\CallAIService::fireCallOutcomeEvent
	 */
	public static function onCallAiTaskComplete(\Bitrix\Main\Event $event): void
	{
		if (!CallAISettings::isCallAIEnable())
		{
			return;
		}

		$outcome = $event->getParameters()['outcome'] ?? null;
		if (
			$outcome instanceof \Bitrix\Call\Integration\AI\Outcome
			&& $outcome->getType() == SenseType::OVERVIEW->value
		)
		{
			$call = $outcome->fillCall();
			$chat = Chat::getInstance($call->getChatId());

			/*
			$messageTaskComplete = ChatMessage::generateTaskCompleteMessage($outcome, $chat);
			if ($messageTaskComplete)
			{
				$chat->sendMessage($messageTaskComplete);
			}
			*/

			$outcomeCollection = OutcomeCollection::getOutcomesByCallId($outcome->getCallId());

			$messageOutcome = ChatMessage::generateOverviewMessage($outcome->getCallId(), $outcomeCollection, $chat);
			if ($messageOutcome)
			{
				$messageOutcome->setAuthorId($call->getInitiatorId());
				$notifyService = \Bitrix\Call\NotifyService::getInstance();
				$notifyService->sendMessageDeferred($chat, $messageOutcome);

				$callInstance = \Bitrix\Im\Call\Registry::getCallWithId($outcome->getCallId());
				(new \Bitrix\Call\Analytics\FollowUpAnalytics($callInstance))->addFollowUpResultMessage();
			}
		}
	}

	/**
	 * @see \Bitrix\Call\Integration\AI\CallAIService::fireCallAiFailedEvent
	 */
	public static function onCallAiTaskFailed(\Bitrix\Main\Event $event): void
	{
		if (!CallAISettings::isCallAIEnable())
		{
			return;
		}

		$error = $event->getParameters()['error'] ?? null;
		$task = $event->getParameters()['task'] ?? null;

		if (
			$task instanceof \Bitrix\Call\Integration\AI\Task\AITask
			&& $error instanceof \Bitrix\Main\Error
		)
		{
			$call = Registry::getCallWithId($task->getCallId());
			NotifyService::getInstance()->sendTaskFailedMessage($error, $call);
		}
	}
}
