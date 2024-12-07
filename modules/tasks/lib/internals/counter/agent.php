<?php

namespace Bitrix\Tasks\Internals\Counter;

use Bitrix\Main\Application;
use Bitrix\Main\Event;
use Bitrix\Tasks\Comments\Task\CommentPoster;
use Bitrix\Tasks\Integration\Bizproc\Listener;
use Bitrix\Tasks\Integration\CRM\TimeLineManager;
use Bitrix\Tasks\Internals\Counter\Event\EventDictionary;
use Bitrix\Tasks\Internals\Log\LogFacade;
use Bitrix\Tasks\Internals\Notification\Controller;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Util\Type\DateTime;
use CAgent;
use CTimeZone;

/**
 * Class Agent
 *
 * @package Bitrix\Tasks\Internals\Counter
 */
class Agent
{
	public const EVENT_TASK_EXPIRED = EventDictionary::EVENT_TASK_EXPIRED;
	public const EVENT_TASK_EXPIRED_SOON = EventDictionary::EVENT_TASK_EXPIRED_SOON;

	private TaskObject $task;
	private Controller $notificationController;
	private TimeLineManager $timeLineManager;
	private ?CommentPoster $commentPoster;

	private string $eventType;
	private array $taskData;

	private function __construct(string $eventType, TaskObject $task)
	{
		$this->task = $task;
		$this->eventType = $eventType;
		$this->init();
	}

	/**
	 * @uses Agent::expired,
	 * @uses Agent::expiredSoon,
	 */
	public static function add(int $taskId, DateTime $deadline, bool $forceExpired = false): bool
	{
		if (Deadline::isDeadlineExpired($deadline))
		{
			$soon = '';
			$agentStart = new DateTime();
		}
		else if ($forceExpired || Deadline::isDeadlineExpiredSoon($deadline))
		{
			$soon = '';
			$agentStart = $deadline;
		}
		else
		{
			$soon = 'Soon';
			$agentStart = clone $deadline;
			$agentStart->addSecond(-Deadline::getDeadlineTimeLimit());
		}

		$agentName = static::class."::expired{$soon}({$taskId});";

		self::remove($taskId);

		CTimeZone::Disable();
		CAgent::AddAgent($agentName, 'tasks', 'Y', 0, '', 'Y', $agentStart);
		CTimeZone::Enable();

		return true;
	}

	/**
	 * @uses Agent::expired,
	 * @uses Agent::expiredSoon,
	 */
	public static function remove(int $taskId): void
	{
		CAgent::RemoveAgent(static::class . "::expired({$taskId});", 'tasks');
		CAgent::RemoveAgent(static::class . "::expiredSoon({$taskId});", 'tasks');
	}

	public static function expired(int $taskId): string
	{
		$task = TaskRegistry::getInstance()->getObject($taskId)?->fillAdditionalMembers();
		$statesCompleted = [Status::DEFERRED, Status::COMPLETED, Status::SUPPOSEDLY_COMPLETED];

		if (
			$task === null
			|| !$task->getResponsibleId()
			|| !$task->getCreatedBy()
			|| in_array((int)$task->getStatus(), $statesCompleted, true)
		)
		{
			return '';
		}

		return static::run(static::EVENT_TASK_EXPIRED, $task);
	}

	public static function expiredSoon(int $taskId): string
	{
		$task = TaskRegistry::getInstance()->getObject($taskId)?->fillAdditionalMembers();
		$statusesCompleted = [Status::DEFERRED, Status::COMPLETED, Status::SUPPOSEDLY_COMPLETED];

		if ($task === null || !$task->getResponsibleId())
		{
			return '';
		}

		if (!$task->hasDeadlineValue())
		{
			self::remove($taskId);
			return '';
		}

		self::add($taskId, DateTime::createFromObjectOrString($task->getDeadline()), true);

		if (in_array((int)$task->getStatus(), $statusesCompleted, true))
		{
			return '';
		}

		return static::run(static::EVENT_TASK_EXPIRED_SOON, $task);
	}

	private static function run(string $eventType, TaskObject $task): string
	{
		return (new static($eventType, $task))
			->addCounterEvent()
			->postComment()
			->sendNotification()
			->sendEvent()
			->triggerAutomation()
			->runCrmEvent()
			->finish();
	}

	private function addCounterEvent(): static
	{
		CounterService::addEvent(
			$this->eventType,
			$this->taskData
		);

		return $this;
	}

	private function postComment(): static
	{
		match ($this->eventType)
		{
			static::EVENT_TASK_EXPIRED => $this->commentPoster?->postCommentsOnTaskExpired($this->taskData),
			static::EVENT_TASK_EXPIRED_SOON => $this->commentPoster?->postCommentsOnTaskExpiredSoon($this->taskData),
			default => LogFacade::log("Unexpected event type {$this->eventType}"),
		};

		return $this;
	}

	private function sendNotification(): static
	{
		match ($this->eventType)
		{
			static::EVENT_TASK_EXPIRED => $this->notificationController->onTaskExpired($this->task),
			static::EVENT_TASK_EXPIRED_SOON => $this->notificationController->onTaskExpiresSoon($this->task),
			default => LogFacade::log("Unexpected event type {$this->eventType}"),
		};

		$this->notificationController->push();

		return $this;
	}

	private function sendEvent(): static
	{
		(new Event('tasks', $this->eventType, [
			'TASK_ID' => $this->task->getId(),
			'TASK' => $this->taskData,
		]))->send();

		return $this;
	}

	private function triggerAutomation(): static
	{
		match ($this->eventType)
		{
			static::EVENT_TASK_EXPIRED => Listener::onTaskExpired($this->task->getId(), $this->taskData),
			static::EVENT_TASK_EXPIRED_SOON => Listener::onTaskExpiredSoon($this->task->getId(), $this->taskData),
			default => LogFacade::log("Unexpected event type {$this->eventType}"),
		};

		return $this;
	}

	private function runCrmEvent(): static
	{
		if ($this->eventType == static::EVENT_TASK_EXPIRED)
		{
			$this->timeLineManager->onTaskExpired()->save();
		}

		$this->timeLineManager->save();

		return $this;
	}

	private function finish(): string
	{
		return '';
	}

	private function init(): void
	{
		$this->notificationController = new Controller();
		$this->timeLineManager = new TimeLineManager($this->task->getId(), $this->task->getResponsibleId());
		$this->commentPoster = CommentPoster::getInstance($this->task->getId(), $this->task->getCreatedBy());
		$this->taskData = $this->task->toArray(true);
	}

	/**
	 * @deprecated
	 */
	public static function start(): string
	{
		return '';
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Tasks\Update\ExpiredAgentCreator
	 */
	public static function install($delay = 10): string
	{
		$res = CAgent::GetList([], ['MODULE_ID' => 'tasks', 'NAME' => '%Agent::expired%']);
		while ($t = $res->Fetch())
		{
			CAgent::Delete($t['ID']);
		}

		$res = CAgent::GetList([], ['MODULE_ID' => 'tasks', 'NAME' => '%Counter\Agent::installNextStep%']);
		while ($t = $res->Fetch())
		{
			CAgent::Delete($t['ID']);
		}

		$agentName = '\Bitrix\Tasks\Internals\Counter\Agent::installNextStep(0);';
		$agentTime = ConvertTimeStamp(time() + $delay, "FULL");

		CTimeZone::Disable();
		CAgent::AddAgent($agentName, 'tasks', 'N', 30, '', 'Y', $agentTime);
		CTimeZone::Enable();

		return '';
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Tasks\Update\ExpiredAgentCreator
	 */
	public static function installNextStep($lastId = 0): string
	{
		$lastId = (int)$lastId;
		$found = false;

		$res = Application::getConnection()->query("
			SELECT ID, DEADLINE
			FROM b_tasks 
			WHERE 
		  		STATUS < 4
		  		AND DEADLINE IS NOT NULL
		  		AND DEADLINE > NOW()
		  		AND ID > {$lastId}
			LIMIT 100
		");
		while ($task = $res->fetch())
		{
			$taskId = $task['ID'];
			$deadline = DateTime::createFromInstance($task['DEADLINE']);

			if ($taskId && $deadline)
			{
				self::add($taskId, $deadline);

				$lastId = $taskId;
				$found = true;
			}
		}

		return ($found ? "\\Bitrix\\Tasks\\Internals\\Counter\\Agent::installNextStep({$lastId});" : "");
	}
}