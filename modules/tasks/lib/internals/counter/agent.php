<?php

namespace Bitrix\Tasks\Internals\Counter;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Event;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Comments\Task\CommentPoster;
use Bitrix\Tasks\Integration\Bizproc;
use Bitrix\Tasks\Integration\CRM\TimeLineManager;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Item\Task;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\User;
use CAgent;
use CTasks;
use CTimeZone;
use Bitrix\Tasks\Integration\CRM\Timeline;

/**
 * Class Agent
 *
 * @package Bitrix\Tasks\Internals\Counter
 */
class Agent
{
	public const EVENT_TASK_EXPIRED = 'OnTaskExpired';
	public const EVENT_TASK_EXPIRED_SOON = 'OnTaskExpiredSoon';

	/**
	 * @param $taskId
	 * @param DateTime $deadline
	 * @param bool $forceExpired
	 * @return bool
	 */
	public static function add($taskId, DateTime $deadline, bool $forceExpired = false): bool
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

		$agentName = self::getClass()."::expired{$soon}({$taskId});";
		$agentStart = ($agentStart ?: new DateTime());

		self::remove($taskId);
		CAgent::AddAgent($agentName, 'tasks', 'Y', 0, '', 'Y', $agentStart);

		return true;
	}

	/**
	 * @param $taskId
	 */
	public static function remove($taskId): void
	{
		CAgent::RemoveAgent(self::getClass()."::expired({$taskId});", 'tasks');
		CAgent::RemoveAgent(self::getClass()."::expiredSoon({$taskId});", 'tasks');
	}

	/**
	 * @return string
	 */
	public static function getClass(): string
	{
		return static::class;
	}

	/**
	 * @param $taskId
	 * @return string
	 * @throws Main\SystemException
	 */
	public static function expired($taskId): string
	{
		$adminId = User::getAdminId();
		$task = Task::getInstance($taskId, $adminId);
		$statesCompleted = [CTasks::STATE_DEFERRED, CTasks::STATE_COMPLETED, CTasks::STATE_SUPPOSEDLY_COMPLETED];

		if (
			!$task
			|| !$task['RESPONSIBLE_ID']
			|| in_array((int)$task['STATUS'], $statesCompleted, true)
			|| !($taskData = $task->getData())
			|| !array_key_exists('CREATED_BY', $taskData)
			|| !$taskData['CREATED_BY']
		)
		{
			return '';
		}

		Counter\CounterService::addEvent(Counter\Event\EventDictionary::EVENT_TASK_EXPIRED, $taskData);

		$commentPoster = CommentPoster::getInstance($taskId, (int)$taskData['CREATED_BY']);
		$commentPoster && $commentPoster->postCommentsOnTaskExpired($taskData);

		\CTaskNotifications::sendExpiredMessage($taskData);

		$event = new Event('tasks', self::EVENT_TASK_EXPIRED, [
			'TASK_ID' => $taskId,
			'TASK' => $taskData,
		]);
		$event->send();

		Bizproc\Listener::onTaskExpired($taskId, $taskData);
		/** @var Task\Collection\Member $members */
		$members = $taskData['SE_MEMBER'];
		$responsibleId = 0;
		foreach ($members as $member)
		{
			$memberData = $member->getData();
			if ($memberData['TYPE'] === RoleDictionary::ROLE_RESPONSIBLE)
			{
				$responsibleId = (int)$memberData['USER_ID'];
			}
		}
		(new TimeLineManager($taskId, $responsibleId))->onTaskExpired()->save();

		return '';
	}

	/**
	 * @param $taskId
	 * @return string
	 * @throws Main\SystemException
	 */
	public static function expiredSoon($taskId): string
	{
		$task = Task::getInstance($taskId, User::getAdminId());
		$statesCompleted = [CTasks::STATE_DEFERRED, CTasks::STATE_COMPLETED, CTasks::STATE_SUPPOSEDLY_COMPLETED];

		if (!$task || !$task['RESPONSIBLE_ID'] || !($taskData = $task->getData()))
		{
			return '';
		}

		if (is_null($taskData['DEADLINE']))
		{
			self::remove($taskData['ID']);
			return '';
		}

		self::add($taskId, $taskData['DEADLINE'], true);

		if (in_array((int)$taskData['STATUS'], $statesCompleted, true))
		{
			return '';
		}

		Counter\CounterService::addEvent(Counter\Event\EventDictionary::EVENT_TASK_EXPIRED_SOON, $taskData);

		$commentPoster = CommentPoster::getInstance($taskId, (int)$taskData['CREATED_BY']);
		$commentPoster && $commentPoster->postCommentsOnTaskExpiredSoon($taskData);

		\CTaskNotifications::sendExpiredSoonMessage($taskData);

		$event = new Event('tasks', self::EVENT_TASK_EXPIRED_SOON, [
			'TASK_ID' => $taskId,
			'TASK' => $taskData,
		]);
		$event->send();

		Bizproc\Listener::onTaskExpiredSoon($taskId, $taskData);

		return '';
	}

	/**
	 * @deprecated
	 */
	public static function start(): string
	{
		return '';
	}

	/**
	 * @param int $delay
	 * @return string
	 *
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
	 * @param int $lastId
	 * @return string
	 * @throws Main\Db\SqlQueryException
	 *
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