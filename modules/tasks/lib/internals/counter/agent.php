<?php

namespace Bitrix\Tasks\Internals\Counter;

use Bitrix\Main\Event;
use Bitrix\Tasks\Integration\Bizproc;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Effective;
use Bitrix\Tasks\Item\Task;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\User;

class Agent
{
	const EVENT_TASK_EXPIRED = 'OnTaskExpired';
	const EVENT_TASK_EXPIRED_SOON = 'OnTaskExpiredSoon';

	public static function add($taskId, DateTime $deadline, $forceExpired = false)
	{
		$adminId = User::getAdminId();
		$task = Task::getInstance($taskId, ($adminId ?: 1));

		if (!$task || in_array($task->status, array(\CTasks::STATE_COMPLETED, \CTasks::STATE_SUPPOSEDLY_COMPLETED)))
		{
			return false;
		}

		$expired = Counter::getExpiredTime();
		$expiredSoon = Counter::getExpiredSoonTime();
		$now = new Datetime();

		if ($now->checkLT($expired))
		{
			$soon = '';
			$agentStart = $now;
		}
		else if ($forceExpired || ($expired->checkLT($deadline) && $expiredSoon->checkGT($deadline)))
		{
			$soon = '';
			$agentStart = $deadline;
		}
		else
		{
			$soon = 'Soon';
			$agentStart = $expiredSoon->checkLT($deadline) ? $deadline : $expired;
		}

		$agentName = self::getClass()."::expired{$soon}({$taskId});";

		self::remove($taskId);
		if(!$agentStart)
		{
			$agentStart = $now;
		}

		\CAgent::AddAgent($agentName, 'tasks', 'Y', 0, '', 'Y', $agentStart);

		return true;
	}

	private static function recountSoon($taskId)
	{
		$adminId = User::getAdminId();
		$task = Task::getInstance($taskId, ($adminId ?: 1));

		if (!$task || in_array($task->status, array(\CTasks::STATE_COMPLETED, \CTasks::STATE_SUPPOSEDLY_COMPLETED)))
		{
			return false;
		}

		$responsible = Counter::getInstance($task->responsibleId, $task->groupId);
		$responsible->recount(Counter\Name::MY_EXPIRED);
		$responsible->recount(Counter\Name::MY_EXPIRED_SOON);

		if ($task->accomplices)
		{
			foreach ($task->accomplices as $userId)
			{
				$responsible = Counter::getInstance($userId, $task->groupId);
				$responsible->recount(Counter\Name::ACCOMPLICES_EXPIRED);
				$responsible->recount(Counter\Name::ACCOMPLICES_EXPIRED_SOON);
			}
		}

		return true;
	}

	public static function remove($taskId)
	{
		\CAgent::RemoveAgent(self::getClass()."::expired({$taskId});", 'tasks');
		\CAgent::RemoveAgent(self::getClass()."::expiredSoon({$taskId});", 'tasks');
	}

	public static function getClass()
	{
		return get_called_class();
	}

	public static function expired($taskId)
	{
		$adminId = User::getAdminId();
		$task = Task::getInstance($taskId, ($adminId ?: 1));

		if (!$task)
		{
			return '';
		}

		$status = (int)$task['STATUS'];
		$groupId = (int)$task['GROUP_ID'];
		$responsibleId = (int)$task['RESPONSIBLE_ID'];

		$statesCompleted = [\CTasks::STATE_DEFERRED, \CTasks::STATE_COMPLETED, \CTasks::STATE_SUPPOSEDLY_COMPLETED];

		if (!$responsibleId || in_array($status, $statesCompleted, true))
		{
			return false;
		}

		$responsible = Counter::getInstance($responsibleId, $groupId);
		$responsible->recount(Counter\Name::MY_EXPIRED_SOON);
		$responsible->recount(Counter\Name::MY_EXPIRED);

		$originator = Counter::getInstance($task['CREATED_BY'], $groupId);
		$originator->recount(Counter\Name::ORIGINATOR_EXPIRED);

		if (!Effective::checkActiveViolations($taskId, $responsibleId, $groupId))
		{
			Effective::modify($responsibleId, 'R', $task, $groupId, true);
		}

		foreach ($task['AUDITORS'] as $userId)
		{
			if ($userId)
			{
				$auditor = Counter::getInstance($userId, $groupId);
				$auditor->recount(Counter\Name::AUDITOR_EXPIRED);
			}
		}

		foreach ($task['ACCOMPLICES'] as $userId)
		{
			$userId = (int)$userId;
			if ($userId)
			{
				$accomplice = Counter::getInstance($userId, $groupId);
				$accomplice->recount(Counter\Name::ACCOMPLICES_EXPIRED);
				$accomplice->recount(Counter\Name::ACCOMPLICES_EXPIRED_SOON);

				if ($userId !== $responsibleId && !Effective::checkActiveViolations($taskId, $userId, $groupId))
				{
					Effective::modify($userId, 'A', $task, $groupId, true);
				}
			}
		}

		$event = new Event("tasks", self::EVENT_TASK_EXPIRED, ['TASK_ID' => $task->getId(), 'TASK' => $task->getData()]);
		$event->send();

		if ($taskData = $task->getData())
		{
			Bizproc\Listener::onTaskExpired($task->getId(), $taskData);
		}

		return '';
	}

	public static function expiredSoon($taskId)
	{
		if (!self::recountSoon($taskId))
		{
			return '';
		}

		$adminId = User::getAdminId();
		$task = Task::getInstance($taskId, ($adminId ?: 1));

		if (!$task || in_array($task->status, [\CTasks::STATE_COMPLETED, \CTasks::STATE_SUPPOSEDLY_COMPLETED]))
		{
			return false;
		}

		$taskId = $task->getId();
		$taskData = $task->getData();

		$event = new Event("tasks", self::EVENT_TASK_EXPIRED_SOON, ['TASK_ID' => $taskId, 'TASK' => $taskData]);
		$event->send();

		if ($taskData)
		{
			Bizproc\Listener::onTaskExpiredSoon($taskId, $taskData);
		}

		if ($task->deadline)
		{
			self::add($taskId, $task->deadline, true);
		}

		return '';
	}

	/**
	 * @deprecated not used
	 */
	public static function start()
	{
		return '';
	}

	public static function install()
	{
		$res = \CAgent::GetList(array(), array('MODULE_ID' => 'tasks', 'NAME' => '%Agent::expired%'));
		while ($t = $res->Fetch())
		{
			\CAgent::Delete($t['ID']);
		}

		\CTimeZone::Disable();
		\CAgent::AddAgent(
			'\Bitrix\Tasks\Internals\Counter\Agent::installNextStep(0);',
			'tasks',
			'N',
			30,
			'',
			'Y',
			ConvertTimeStamp(time()+10, "FULL")
		);
		\CTimeZone::Enable();

		return '';
	}

	public static function installNextStep($lastId = 0)
	{
		global $DB;
		$limit = 50;
		$lastId = (int)$lastId;
		$found = false;

		$res = $DB->Query(
			"
			SELECT ID, DEADLINE FROM b_tasks 
			WHERE 
			  ZOMBIE='N' AND 
			  STATUS < 4 AND
			  DEADLINE <> '' AND
              ID > {$lastId}
			LIMIT {$limit}
		");

		while ($t = $res->Fetch())
		{
			self::add($t['ID'], DateTime::createFrom($t['DEADLINE']));
			$lastId = $t['ID'];
			$found = true;
		}

		if($found)
		{
			return "\\Bitrix\\Tasks\\Internals\\Counter\\Agent::installNextStep({$lastId});";
		}
		else
		{
			return '';
		}
	}
}
