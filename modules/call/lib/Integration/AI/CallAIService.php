<?php

namespace Bitrix\Call\Integration\AI;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Loader;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Type\DateTime;
use Bitrix\AI\Engine;
use Bitrix\AI\Context;
use Bitrix\AI\Payload\IPayload;
use Bitrix\Im\Call\Registry;
use Bitrix\Call\Track;
use Bitrix\Call\Logger\Logger;
use Bitrix\Call\NotifyService;
use Bitrix\Call\Integration\AI\Task\AITask;
use Bitrix\Call\Model\CallAITaskTable;
use Bitrix\Call\Model\CallOutcomeTable;
use Bitrix\Call\Model\CallTrackTable;
use Bitrix\Call\Integration\AI\Outcome\OutcomeCollection;
use Bitrix\Call\Analytics\FollowUpAnalytics;

final class CallAIService
{
	private const DELAY_WAIT_FOR_RESULT = 900; // 15 minutes
	private const FINISH_TASK_DEPTH_DAYS = 60;

	private static ?CallAIService $service = null;

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

	public function processTrack(Track $track): Result
	{
		$result = new Result;

		$logger = Logger::getInstance();

		if (!CallAISettings::isCallAIEnable())
		{
			$logger->error('Unable process track. Module AI is unavailable. TrackId:'.$track->getId());

			$error = new CallAIError(CallAIError::AI_UNAVAILABLE_ERROR);
			$error->allowRecover();

			return $result->addError($error);
		}

		$resultTask = $this->buildTaskByTrack($track);
		if (!$resultTask->isSuccess())
		{
			$logger->error('Unable process track. Error: '. implode('; ', $resultTask->getErrorMessages()));
			return $result->addErrors($resultTask->getErrors());
		}

		/** @var AITask $task */
		$task = $resultTask->getData()['task'] ?? null;
		if ($task)
		{
			$launchResult = $this->launchTask($task);
			if (!$launchResult->isSuccess())
			{
				return $result->addErrors($launchResult->getErrors());
			}
		}

		return $result;
	}

	/**
	 * @param Track $track
	 * @return Result<AITask|Task\TranscribeCallRecord>
	 */
	public function buildTaskByTrack(Track $track): Result
	{
		$result = new Result;

		if ($track->getType() != Track::TYPE_TRACK_PACK)
		{
			return $result->addError(new CallAIError(CallAIError::AI_UNSUPPORTED_TRACK_ERROR, 'Unsupported track format'));
		}

		$task = new Task\TranscribeCallRecord();
		$task
			->setPayload($track)
			->setLanguageId($this->getLanguageId())
			->save()
		;

		return $result->setData(['task' => $task]);
	}

	/**
	 * @param Outcome $outcome
	 * @return Result<AITask[]>
	 */
	public function buildTasksByOutcome(Outcome $outcome): Result
	{
		$result = new Result;

		$taskToLaunch = $this->getTaskToLaunchByOutcome($outcome);

		$tasks = [];
		foreach ($taskToLaunch as $taskSenseType)
		{
			$task = AITask::getTaskForCall($outcome->getCallId(), $taskSenseType);
			if ($task && $task->isFinished())
			{
				continue; // skip finished task
			}

			$taskClass = $taskSenseType->getTaskClass();

			$task = new $taskClass();
			$dbResult = $task
				->setPayload($outcome)
				->setLanguageId($this->getLanguageId())
				->save()
			;
			if ($dbResult->isSuccess())
			{
				$tasks[] = $task;
			}
			else
			{
				$result->addErrors($dbResult->getErrors());
			}
		}

		return $result->setData(['tasks' => $tasks]);
	}

	/**
	 * @return SenseType[]
	 */
	private function getTaskToLaunchByOutcome(Outcome $outcome): array
	{
		$taskToLaunch = [];
		if ($outcome->getType() == SenseType::TRANSCRIBE->value)
		{
			$taskToLaunch = [
				SenseType::OVERVIEW,
				SenseType::INSIGHTS,
				SenseType::SUMMARY,
			];
		}

		return $taskToLaunch;
	}

	/**
	 * @param AITask $task
	 * @return Result
	 */
	public function finishTask(AITask $task): Result
	{
		return $task->drop();
	}

	/**
	 * @param AITask $task
	 * @return Result
	 */
	public function launchTask(AITask $task): Result
	{
		$result = new Result;

		if ($log = CallAISettings::isLoggingEnable())
		{
			$logger = Logger::getInstance();
		}

		$payloadResult = $task->getAIPayload();
		if (!$payloadResult->isSuccess())
		{
			$log && $logger->error('Empty payload for AI');

			$error = new CallAIError(CallAIError::AI_EMPTY_PAYLOAD_ERROR);
			$this->fireCallAiFailedEvent($task, $error);

			return $result->addError($error);
		}

		/**
		 * @var \Bitrix\AI\Payload\IPayload $payload
		 */
		$payload = $payloadResult->getData()['payload'];
		$context = $task->getAIEngineContext();
		$engine = $task->getAIEngine($context);
		$call = Registry::getCallWithId($task->getCallId());

		if (
			$payload instanceof \Bitrix\AI\Payload\IPayload
			&& method_exists($payload, 'setCost')
		)
		{
			$payload->setCost($task->getCost());

			if (
				Loader::includeModule('bitrix24')
				&& CallAISettings::isAutoStartRecordingEnable()
			)
			{
				if ($call->autoStartRecording())
				{
					$payload->setCost(0);
				}
			}
		}

		$event = $this->fireCallAiTaskEvent($task, $payload, $context, $engine);
		if (
			($eventResult = $event->getResults()[0] ?? null)
			&& $eventResult instanceof EventResult
			&& $eventResult->getType() == EventResult::ERROR
		)
		{
			$log && $logger->error('AI processing was cancelled by event');

			return $result;
		}

		if (!($engine instanceof \Bitrix\AI\Engine))
		{
			$log && $logger->error('AI engine is unavailable');
			$result->addError((new CallAIError(CallAIError::AI_UNAVAILABLE_ERROR))->allowRecover());
		}
		else
		{
			$checkRestrictionResult = $this->checkRestriction($engine);

			if (!$checkRestrictionResult->isSuccess())
			{
				$log && $logger->error('AI engine error: '.$checkRestrictionResult->getError()->getMessage());
				$result->addError($checkRestrictionResult->getError());
			}
			else
			{
				$log && $logger->info(
					'Launch AI task: '.$task->getType()
					. ' Engine: '. $engine->getCode()
					. ' Payload: '. $task->decodePayload($payload->pack())
					. ($payload instanceof \Bitrix\AI\Payload\Prompt ? ' Prompt code: '. $payload->getPromptCode() : '')
				);

				$engine
					->setPayload($payload)
					->setHistoryState(false)
					->onSuccess(
						function (\Bitrix\AI\Result $result, ?string $queueHash = null)
						use (&$hash, &$task, &$logger)
						{
							$task
								->setHash($queueHash)
								->setStatus($task::STATUS_PENDING)
								->save();

						}
					)
					->onError(
						function (Error $processingError)
						use (&$result, &$task)
						{
							$error = CallAIError::constructTaskError(CallAIError::AI_TASK_START_FAIL, $processingError, $task);
							$error->allowRecover();

							$task
								->setStatus($task::STATUS_FAILED)
								->setErrorCode($error->getCode())
								->setErrorMessage($error->getDescription() ?: $error->getMessage())
								->save();

							$result->addError($error);
						}
					)
					->completionsInQueue();
			}
		}

		if (!$result->isSuccess())
		{
			$log && $logger->error('AI processing has failed. Task Id:'.$task->getId().' Error: '.$result->getError()?->getMessage());

			(new FollowUpAnalytics($call))->addAITaskFailed($task, $result->getError()?->getCode() ?? '');

			$this->fireCallAiFailedEvent($task, $result->getError());
		}
		else
		{
			$log && $logger->info('New AI task has been set. TaskId:'.$task->getId().' Hash: '.$task->getHash());

			(new FollowUpAnalytics($call))->addAITaskLunch($task);
		}

		return $result;
	}

	/**
	 * @param int $callId
	 * @return Result
	 */
	public function restartCallAiTask(int $callId): Result
	{
		$result = new Result();

		$outcomeCollection = OutcomeCollection::getOutcomesByCallId($callId);

		// Check transcribe
		$transcribe = $outcomeCollection?->getOutcomeByType(SenseType::TRANSCRIBE->value);
		if (!$transcribe)
		{
			// Check transcribe Task
			$transcribeTask = AITask::getTaskForCall($callId, SenseType::TRANSCRIBE);
			if ($transcribeTask)
			{
				if ($transcribeTask->isPending())
				{
					return $result;// wait more
				}
			}

			// Check track_pack
			$trackPack = Track::getTrackForCall($callId, Track::TYPE_TRACK_PACK);
			if (!$trackPack)
			{
				return $result->addError(new CallAIError(CallAIError::AI_TRACKPACK_NOT_RECEIVED));
			}

			return $this->processTrack($trackPack);
		}

		$taskToLaunch = $this->getTaskToLaunchByOutcome($transcribe);
		foreach ($taskToLaunch as $taskSenseType)
		{
			$outcome = $outcomeCollection?->getOutcomeByType($taskSenseType->value);
			if (!$outcome)
			{
				// Check Task
				$task = AITask::getTaskForCall($callId, $taskSenseType);
				if ($task)
				{
					if ($task->isPending() || $task->isFinished())
					{
						continue;// wait more
					}
				}

				$taskClass = $taskSenseType->getTaskClass();

				$task = new $taskClass();
				$dbResult = $task
					->setPayload($transcribe)
					->setLanguageId($this->getLanguageId())
					->save()
				;
				if ($dbResult->isSuccess())
				{
					$launchResult = $this->launchTask($task);
					if (!$launchResult->isSuccess())
					{
						$result->addErrors($launchResult->getErrors());
						break;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Success AI callback handler.
	 * @see \Bitrix\AI\QueueJob::execute
	 * @event ai:onQueueJobExecute
	 * @param Event $event
	 * @return void
	 */
	public static function onQueueTaskExecute(Event $event): void
	{
		/** @var string $hash */
		$hash = $event->getParameter('queue');

		/** @var \Bitrix\AI\Engine\IEngine $engine */
		$engine = $event->getParameter('engine');
		$context = $engine->getContext();

		$moduleId = $context->getModuleId();
		$contextId = $context->getContextId();
		$parameters = $context->getParameters();

		if (
			empty($moduleId)
			|| $moduleId != 'call'
			|| empty($contextId)
			|| empty($parameters)
			|| empty($parameters['taskId'])
			|| !($task = AITask::loadById($parameters['taskId']))
			|| $contextId != $task->getContextId()
			|| $hash != $task->getHash()
		)
		{
			return;
		}

		if ($log = CallAISettings::isLoggingEnable())
		{
			$logger = Logger::getInstance();
			$logger->info('AI task has successfully completed. TaskId:' . $task->getId() . ' Hash:' . $hash);
		}

		// check for duplicate event
		if ($task->getStatus() == AITask::STATUS_FINISHED)
		{
			$res = CallOutcomeTable:: query()
				->setSelect(['ID'])
				->where('CALL_ID', $task->getCallId())
				->where('TYPE', $task->getAISenseType())
				->setLimit(1)
				->exec()
			;
			if ($res->fetch())
			{
				if ($log)
				{
					$logger->info('Got duplicate AI event. TaskId:' . $task->getId() . ' Hash:' . $hash);
				}
				return;
			}
		}

		$task
			->setStatus(AITask::STATUS_FINISHED)
			->setDateFinished(new DateTime)
			->save()
		;

		$aiResult = $event->getParameter('result');
		if (!($aiResult instanceof \Bitrix\AI\Result))
		{
			return;
		}

		$outcome = $task->buildOutcome($aiResult);
		if (!$outcome)
		{
			return;
		}

		$outcome->save();
		$outcome->saveProps();

		if ($log)
		{
			$logger->info('AI task outcome. TaskId:' . $task->getId() . ' OutcomeId: ' . $outcome->getId());
			$propsLog = '';
			foreach ($outcome->getProps() as $prop)
			{
				$propsLog .= "\nProperty: {$prop->getCode()}, Content: " . $prop->getContent();
			}
			$logger->info(
				"AI outcome. Type: {$outcome->getType()}"
				. ($outcome->hasContent() ? "\nContent: " . $outcome->getContent() : '')
				. ($propsLog ?: '')
			);
		}

		$service = self::getInstance();
		$event = $service->fireCallOutcomeEvent($outcome);
		if (
			($eventResult = $event->getResults()[0] ?? null)
			&& $eventResult instanceof EventResult
			&& $eventResult->getType() == EventResult::ERROR
		)
		{
			$log && $logger->info('Processing AI result has been canceled by event');
			return;
		}

		$nextTaskResult = $service->buildTasksByOutcome($outcome);
		if (!$nextTaskResult->isSuccess())
		{
			if ($log && !empty($nextTaskResult->getErrors()))
			{
				$logger->error('Unable process AI outcome. OutcomeId: '.$outcome->getId().' Error: '. implode('; ', $nextTaskResult->getErrorMessages()));
			}
		}

		/** @var AITask[] $tasks */
		$tasks = $nextTaskResult->getData()['tasks'] ?? [];
		foreach ($tasks as $nextTask)
		{
			$service->launchTask($nextTask);
			usleep(100);
		}
	}


	/**
	 * Callback handler AI job has been failed.
	 * @see \Bitrix\AI\QueueJob::clearOldAgent
	 * @see \Bitrix\AI\QueueJob::fail
	 * @event ai:onQueueJobFail
	 * @return void
	 */
	public static function onQueueTaskFail(Event $event): void
	{
		/** @var string $hash */
		$hash = $event->getParameter('queue');

		/** @var \Bitrix\AI\Engine\IEngine $engine */
		$engine = $event->getParameter('engine');
		$context = $engine->getContext();

		$moduleId = $context->getModuleId();
		$contextId = $context->getContextId();
		$parameters = $context->getParameters();

		if (
			empty($moduleId)
			|| $moduleId != 'call'
			|| empty($contextId)
			|| empty($parameters)
			|| empty($parameters['taskId'])
			|| !($task = AITask::loadById($parameters['taskId']))
			|| $contextId != $task->getContextId()
			|| $hash != $task->getHash()
		)
		{
			return;
		}

		$processingError = $event->getParameter('error');
		$error = CallAIError::constructTaskError(CallAIError::AI_TASK_FAILED, $processingError, $task);
		$error->allowRecover();

		$task
			->setStatus(AITask::STATUS_FAILED)
			->setDateFinished(new DateTime)
			->setErrorMessage($error->getDescription() ?: $error->getMessage())
			->setErrorCode($error->getCode())
			->save()
		;

		if (CallAISettings::isLoggingEnable())
		{
			$logger = Logger::getInstance();
			$logger->info(
				'AI task has failed.'
				. ' TaskId:' . $task->getId()
				. ' Hash: ' . $hash
				. ' Code: ' . $error->getCode()
				. ' Error: ' . ($error->getDescription() ?: $error->getMessage())
			);
		}

		$call = Registry::getCallWithId($task->getCallId());
		(new FollowUpAnalytics($call))->addAITaskFailed($task, $error->getCode() ?? '');

		$service = self::getInstance();
		$service->fireCallAiFailedEvent($task, $error);
	}

	/**
	 * Check service AI unavailability and restrictions.
	 * @return Result
	 */
	public function checkRestriction(Engine $engine): Result
	{
		$checkResult = new Result;
		if (!$engine->isAvailableByTariff())
		{
			$error = new CallAIError(CallAIError::AI_UNAVAILABLE_ERROR);// AI service unavailable by tariff
			$error->allowRecover();

			$checkResult->addError($error);
		}
		elseif (!$engine->isAvailableByAgreement())
		{
			$error = new CallAIError(CallAIError::AI_AGREEMENT_ERROR);// AI service agreement must be accepted
			$error->allowRecover();

			$checkResult->addError($error);
		}

		return $checkResult;
	}

	/**
	 * Detects user language.
	 * @return string|null
	 */
	protected function getLanguageId(): ?string
	{
		return Loader::includeModule('ai') ? \Bitrix\AI\Facade\User::getUserLanguage() : null;
	}

	/**
	 * @event call:onCallAiOutcome
	 * @param Outcome $outcome
	 * @return Event
	 */
	public function fireCallOutcomeEvent(Outcome $outcome): Event
	{
		$event = new Event('call', 'onCallAiOutcome', ['outcome' => $outcome]);
		$event->send();

		return $event;
	}

	/**
	 * @event call:onCallAiFailed
	 * @param AITask $task
	 * @param Error|null $processingError
	 * @return Event
	 */
	public function fireCallAiFailedEvent(AITask $task, ?Error $processingError): Event
	{
		$event = new Event('call', 'onCallAiFailed', ['task' => $task, 'error' => $processingError]);
		$event->send();

		return $event;
	}

	/**
	 * @event call:onCallAiTask
	 * @param AITask $task
	 * @param IPayload $payload
	 * @param Context $context
	 * @param Engine|null $engine
	 * @return Event
	 */
	public function fireCallAiTaskEvent(AITask $task, IPayload $payload, Context $context, ?Engine $engine): Event
	{
		$event = new Event(
			'call',
			'onCallAiTask',
			[
				'task' => $task,
				'payload' => $payload,
				'context' => $context,
				'engine' => $engine,
			]
		);
		$event->send();

		return $event;
	}

	public static function finishTasks(): string
	{
		$service = self::getInstance();

		$depthDays = self::FINISH_TASK_DEPTH_DAYS;
		$taskList = CallAITaskTable::getList([
			'filter' => [
				'<DATE_CREATE' => (new DateTime())->add("-{$depthDays} days")
			]
		]);
		while ($row = $taskList->fetchObject())
		{
			$task = AITask::buildBySource($row);
			$service->finishTask($task);
		}

		return __METHOD__. "();";
	}

	//region Expectation

	/**
	 * Adds agent to checkup ai tasks.
	 * @param int $callId
	 * @return void
	 */
	public function setupExpectation(int $callId): void
	{
		/** @see self::expectCallAiTask */
		\CAgent::AddAgent(
			"Bitrix\\Call\\Integration\\AI\\CallAIService::expectCallAiTask({$callId});",
			'call',
			'N',
			self::DELAY_WAIT_FOR_RESULT,
			'',
			'Y',
			\ConvertTimeStamp(time() + \CTimeZone::GetOffset() + self::DELAY_WAIT_FOR_RESULT, 'FULL')
		);
	}
	/**
	 * Removes agent expecting for ai tasks.
	 * @param int $callId
	 * @return void
	 */
	public function removeExpectation(int $callId): void
	{
		/** @see self::expectCallAiTask */
		\CAgent::RemoveAgent(
			"Bitrix\\Call\\Integration\\AI\\CallAIService::expectCallAiTask({$callId});",
			'call'
		);
	}

	/**
	 * Agent to run ai tasks check.
	 * @param int $callId
	 * @param int $repeat
	 * @return string
	 */
	public static function expectCallAiTask(int $callId, int $repeat = 1): string
	{
		Loader::includeModule('im');

		$call = Registry::getCallWithId($callId);
		if (!$call)
		{
			return '';
		}

		$service = self::getInstance();
		$notifyService = NotifyService::getInstance();

		$result = $service->checkCallAiTask($callId, $repeat);
		if (!$result->isSuccess())
		{
			$notifyService->sendTaskFailedMessage($result->getError(), $call);
		}
		elseif ($result->getData()['wait_more'] === true)
		{
			$notifyService->sendTaskWaitMessage($call);
		}

		if ($result->getData()['repeat'] === true)
		{
			$repeat ++;
			return __METHOD__. "({$callId}, {$repeat});"; // wait more
		}

		return '';
	}

	/**
	 * @param int $callId
	 * @param int $repeat
	 * @return Result
	 */
	public function checkCallAiTask(int $callId, int $repeat = 1): Result
	{
		$result = new Result();
		$result->setData(['repeat' => false]);

		$log = function (string $mess)
		{
			if (CallAISettings::isLoggingEnable())
			{
				Logger::getInstance()->error($mess);
			}
		};

		// Check all followup tasks
		$taskCompleted = 0;
		$waitForTasks = [SenseType::OVERVIEW, SenseType::INSIGHTS, SenseType::SUMMARY, SenseType::TRANSCRIBE];
		/** @var SenseType $senseType */
		foreach ($waitForTasks as $senseType)
		{
			$taskOutcome = Outcome::getOutcomeForCall($callId, $senseType);
			if ($taskOutcome)
			{
				$taskCompleted ++;
				continue;// ok
			}

			$task = AITask::getTaskForCall($callId, $senseType);
			if ($task)
			{
				if ($task->isPending())
				{
					$log("Check ai task: Call #{$callId}, {$senseType->value} is still pending.");

					return $result->setData(['repeat' => true]);// wait more
				}
				if (!$task->isFinished())
				{
					$log("Check ai task: Call #{$callId}, {$senseType->value} task has failed.");

					$error = new CallAIError(CallAIError::AI_OVERVIEW_TASK_ERROR);
					$error->allowRecover();

					return $result->addError($error);
				}
			}
		}
		if ($taskCompleted == count($waitForTasks))
		{
			return $result;//ок
		}

		// Check track_pack
		$trackPack = Track::getTrackForCall($callId, Track::TYPE_TRACK_PACK);
		if ($trackPack)
		{
			$log("Check ai task: Call #{$callId}, Transcription task has failed.");

			$error = new CallAIError(CallAIError::AI_TRANSCRIBE_TASK_ERROR);
			$error->allowRecover();

			return $result->addError($error);
		}

		$log("Check ai task: Call #{$callId}, Trackpack not received.");

		if ($repeat <= 1)
		{
			return $result->setData([
				'repeat' => true,
				'wait_more' => true,
			]);
		}

		return $result->addError(new CallAIError(CallAIError::AI_TRACKPACK_NOT_RECEIVED));
	}
	//endregion

	/**
	 * @param int $callId
	 * @return Result
	 */
	public function dropCallAiFollowUp(int $callId): Result
	{
		$result = new Result();

		$taskList = CallAITaskTable::query()
			->where('CALL_ID', $callId)
			->exec()
		;
		while ($row = $taskList->fetchObject())
		{
			$task = AITask::buildBySource($row);
			$task->drop();
		}

		$outcomeCollection = OutcomeCollection::getOutcomesByCallId($callId);
		foreach ($outcomeCollection as $outcome)
		{
			$outcome->drop();
		}

		$trackList = CallTrackTable::query()
			->setSelect(['FILE_ID', 'DISK_FILE_ID', 'EXTERNAL_TRACK_ID'])
			->where('CALL_ID', $callId)
			->exec()
		;
		while ($track = $trackList->fetchObject())
		{
			$track->drop();
		}

		return $result;
	}
}
