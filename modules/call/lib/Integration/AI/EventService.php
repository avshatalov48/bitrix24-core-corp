<?php

namespace Bitrix\Call\Integration\AI;

use Bitrix\Im\V2\Chat;
use Bitrix\Im\Call\Registry;
use Bitrix\Call\NotifyService;
use Bitrix\Call\Integration\AI\Outcome\OutcomeCollection;
use Bitrix\Im\V2\Message\Send\SendingConfig;
use Bitrix\Call\Analytics\FollowUpAnalytics;


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
			$minDuration = CallAISettings::getRecordMinDuration();
			if ($call->getDuration() < $minDuration)
			{
				$call
					->disableAudioRecord()
					->disableAiAnalyze()
					->save();

				(new FollowUpAnalytics($call))->addErrorRecording(CallAIError::AI_RECORD_TOO_SHORT);

				return;
			}

			// Setup result waiting agent
			CallAIService::getInstance()->setupExpectation($call->getId());

			$chat = Chat::getInstance($call->getChatId());
			if ($chat->getId() == $call->getChatId())
			{
				$message = ChatMessage::generateCallFinishedMessage($call, $chat);
				if ($message)
				{
					$sendingConfig = (new SendingConfig())
						->enableSkipCounterIncrements()
						->enableSkipUrlIndex()
					;
					NotifyService::getInstance()->sendMessageDeferred($chat, $message, $sendingConfig);
				}
			}

			(new FollowUpAnalytics($call))->addStopRecording();
		}
	}

	/**
	 * @see CallAIService::fireCallAiTaskEvent
	 */
	public static function onCallAiTaskStart(\Bitrix\Main\Event $event): void
	{
		if (!CallAISettings::isCallAIEnable())
		{
			return;
		}

		/*
		$task = $event->getParameters()['task'] ?? null;

		if ($task instanceof \Bitrix\Call\Integration\AI\Task\TranscribeCallRecord)
		{
			$chat = Chat::getInstance($task->fillCall()->getChatId());

			$message = ChatMessage::generateTaskStartMessage($task->getCallId(), $chat);
			if ($message)
			{
				//$chat->sendMessage($message);
				$notifyService = \Bitrix\Call\NotifyService::getInstance();
				$notifyService->sendMessageDeferred($chat, $message);
			}
		}
		*/
	}

	/**
	 * @see CallAIService::fireCallOutcomeEvent
	 */
	public static function onCallAiTaskComplete(\Bitrix\Main\Event $event): void
	{
		if (!CallAISettings::isCallAIEnable())
		{
			return;
		}

		$waitForTasks = [SenseType::OVERVIEW->value, SenseType::INSIGHTS->value, SenseType::SUMMARY->value];
		$outcome = $event->getParameters()['outcome'] ?? null;
		if (
			$outcome instanceof Outcome
			&& in_array($outcome->getType(), $waitForTasks, true)
		)
		{
			$outcomeCollection = OutcomeCollection::getOutcomesByCallId($outcome->getCallId(), $waitForTasks);
			if ($outcomeCollection->count() >= count($waitForTasks))
			{
				$call = $outcome->fillCall();
				$chat = Chat::getInstance($call->getChatId());
				if ($chat->getId() == $call->getChatId())
				{
					$messageOutcome = ChatMessage::generateOverviewMessage($outcome->getCallId(), $outcomeCollection, $chat);
					if ($messageOutcome)
					{
						$sendingConfig = (new SendingConfig())->enableSkipUrlIndex();
						NotifyService::getInstance()->sendMessageDeferred($chat, $messageOutcome, $sendingConfig);

						CallAIService::getInstance()->removeExpectation($call->getId());

						$callInstance = \Bitrix\Im\Call\Registry::getCallWithId($call->getId());
						(new FollowUpAnalytics($callInstance))->addFollowUpResultMessage();
					}
				}
			}
		}
	}

	/**
	 * @see CallAIService::fireCallAiFailedEvent
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
			&& $task->allowNotifyTaskFailed()
		)
		{
			$call = Registry::getCallWithId($task->getCallId());
			if ($call)
			{
				CallAIService::getInstance()->removeExpectation($call->getId());

				NotifyService::getInstance()->sendTaskFailedMessage($error, $call);
			}
		}
	}
}
