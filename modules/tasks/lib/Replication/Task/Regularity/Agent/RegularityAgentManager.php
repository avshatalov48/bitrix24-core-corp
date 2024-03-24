<?php

namespace Bitrix\Tasks\Replication\Task\Regularity\Agent;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Filter\Condition;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Internals\Log\Log;
use Bitrix\Tasks\Internals\Notification\Controller;
use Bitrix\Tasks\Internals\Task\RegularParametersTable;
use Bitrix\Tasks\Internals\TaskCollection;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Replication\Task\Regularity\Agent\Race\Mutex;
use Bitrix\Tasks\Update\AgentInterface;
use Bitrix\Tasks\Update\AgentTrait;
use CAgent;
use Exception;

class RegularityAgentManager implements AgentInterface
{
	use AgentTrait;

	private const LIMIT = 500;
	private const SECONDS_IN_MINUTE = 60;
	private const ROUNDER = 5;
	private const DELAY = 5;

	private static bool $processing = false;

	private Mutex $mutex;
	private Controller $controller;
	private TaskCollection $taskCollection;
	private Log $logger;

	public function __construct()
	{
		$this->init();
	}

	private function init(): void
	{
		$this->mutex = new Mutex();
		$this->controller = new Controller();
		$this->taskCollection = new TaskCollection();
		$this->logger = new Log();
	}

	public static function execute(): string
	{
		if (static::$processing)
		{
			return static::getAgentName();
		}

		static::$processing = true;

		$agent = new static();
		$result = $agent->run();

		static::$processing = false;

		return $result;
	}

	private function run(): string
	{
		if ($this->mutex->lock())
		{
			try
			{
				$this
					->fillSuitableTasks()
					->sendNotifications()
					->markAsSent()
					->updateAgentPeriod();
			}
			catch (Exception $exception)
			{
				$this->logger->collect("Regular notification not sent: {$exception->getMessage()}");
			}
			finally
			{
				$this->mutex->unlock();
			}
		}

		return static::getAgentName();
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function fillSuitableTasks(): static
	{
		$range = $this->getSuitableTaskIds();
		if (empty($range))
		{
			return $this;
		}

		$tasksQuery = TaskTable::query();
		$tasksQuery
			->setSelect(['ID', 'RESPONSIBLE_ID', 'REGULAR'])
			->whereIn('ID', $range)
			->setLimit(static::LIMIT);

		$this->taskCollection = $tasksQuery->exec()->fetchCollection();

		return $this;
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	private function getSuitableTaskIds(): array
	{
		$currentDateTime = $this->getCurrentExecutionTime();

		$dateQuery = RegularParametersTable::query();
		$sqlDateQuery =
			$dateQuery
				->setSelect(['ID'])
				->where('START_DAY', $currentDateTime)
				->where('NOTIFICATION_SENT', false)
				->getQuery();

		$dateTimeQuery = RegularParametersTable::query();
		$dateTimeQuery
			->setSelect(['ID', 'TASK_ID'])
			->whereIn('ID', new SqlExpression($sqlDateQuery))
			->where(new Condition('START_TIME', '<=', $this->roundDownToFive($currentDateTime)))
			->setLimit(static::LIMIT);

		return $dateTimeQuery->exec()->fetchCollection()->getTaskIdList();
	}

	/**
	 * Run agent every 5 minutes
	 */
	private function roundDownToFive(DateTime $dateTime): DateTime
	{
		$timestamp = $dateTime->getTimestamp();
		$round = static::SECONDS_IN_MINUTE * static::ROUNDER;
		$roundedTimestamp = floor($timestamp / $round) * $round;

		return DateTime::createFromTimestamp($roundedTimestamp);
	}

	private function roundUpToFive(DateTime $dateTime): DateTime
	{
		$timestamp = $dateTime->getTimestamp();
		$round = static::SECONDS_IN_MINUTE * static::ROUNDER;
		$roundedTimestamp = ceil($timestamp / $round) * $round;

		return DateTime::createFromTimestamp($roundedTimestamp);
	}

	/**
	 * @throws Exception
	 */
	private function sendNotifications(): static
	{
		foreach ($this->taskCollection as $task)
		{
			$this->controller->onRegularTaskStarted($task, ['SPAWNED_BY_AGENT' => false]);
			$this->controller->push();
		}

		return $this;
	}

	private function markAsSent(): static
	{
		foreach ($this->taskCollection as $task)
		{
			$task->fillRegular();
			$task->getRegular()->setNotificationSent(true)->save();
		}

		return $this;
	}

	private function getCurrentExecutionTime(bool $fetchAgent = false): DateTime
	{
		if ($fetchAgent)
		{
			$agent = CAgent::getList([], ['NAME' => static::getAgentName()])->fetch();
			if ($agent)
			{
				return DateTime::createFromText($agent['NEXT_EXEC']);
			}
		}


		return new DateTime();
	}

	private function updateAgentPeriod(): static
	{
		// we can not use CAgent::Update() here, kz the agent will be updated again just after this function ends ...
		// ... but we may set some global var called $pPERIOD
		// "why ' - time()'?" you may ask. see CAgent::ExecuteAgents(), in the last sql we got:
		// NEXT_EXEC=DATE_ADD(".($arAgent["IS_PERIOD"]=="Y"? "NEXT_EXEC" : "now()").", INTERVAL ".$pPERIOD." SECOND),
		$nextExecutionTime = $this->getCurrentExecutionTime();
		$nextExecutionTime = $this->roundUpToFive($nextExecutionTime);
		$nextExecutionTimeTS = $nextExecutionTime->getTimestamp();

		global $pPERIOD;
		$pPERIOD = $nextExecutionTimeTS - time() + static::DELAY;

		return $this;

	}
}