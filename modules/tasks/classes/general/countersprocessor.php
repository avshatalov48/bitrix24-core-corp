<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Internals\TaskTable;

IncludeModuleLangFile(__FILE__);

/**
 * This is not a part of public API.
 * For internal use only.
 *
 * @access private
 */
class CTaskCountersProcessor
{
	const COUNTER_TASKS_TOTAL = 'tasks_total';

	// Subtotals counters for roles
	const COUNTER_TASKS_MY         = 'tasks_my';
	const COUNTER_TASKS_ACCOMPLICE = 'tasks_acc';
	const COUNTER_TASKS_AUDITOR    = 'tasks_au';
	const COUNTER_TASKS_ORIGINATOR = 'tasks_orig';

	// Not viewed tasks counters
	const COUNTER_TASKS_MY_NEW         = 'tasks_my_new';
	const COUNTER_TASKS_ACCOMPLICE_NEW = 'tasks_acc_new';

	// Expired tasks counters
	const COUNTER_TASKS_MY_EXPIRED         = 'tasks_my_expired';
	const COUNTER_TASKS_ACCOMPLICE_EXPIRED = 'tasks_acc_expired';
	const COUNTER_TASKS_AUDITOR_EXPIRED    = 'tasks_au_expired';
	const COUNTER_TASKS_ORIGINATOR_EXPIRED = 'tasks_orig_expired';

	// Tasks to be expired soon counters
	const COUNTER_TASKS_MY_EXPIRED_CANDIDATES         = 'tasks_my_expired_cand';
	const COUNTER_TASKS_ACCOMPLICE_EXPIRED_CANDIDATES = 'tasks_acc_expired_cand';
	//const COUNTER_TASKS_AUDITOR_EXPIRED_CANDIDATES    = 'tasks_au_expired_cand';
	//const COUNTER_TASKS_ORIGINATOR_EXPIRED_CANDIDATES = 'tasks_orig_expired_cand';

	// Tasks without DEADLINE counters
	const COUNTER_TASKS_MY_WO_DEADLINE         = 'tasks_my_wo_dl';
	const COUNTER_TASKS_ORIGINATOR_WO_DEADLINE = 'tasks_orig_wo_dl';

	// Counters of tasks in status CTasks::STATE_SUPPOSEDLY_COMPLETED
	const COUNTER_TASKS_ORIGINATOR_WAIT_CTRL = 'tasks_orig_wctrl';

//
//	// private consts:
//	const DEADLINE_NOT_COUNTED                  = 0;
//	const DEADLINE_COUNTED_AS_EXPIRED           = 1;
//	const DEADLINE_COUNTED_AS_EXPIRED_CANDIDATE = 2;
//
//	const DEADLINE_TIME = 86400; // for expired 1 day
//	const AGENT_HIT_LIMIT = 100; // 1 hit recount counters for 100 users
//
//	private static $instanceOfSelf = null;
//
//	private static $debug = false;
//
//	/**
//	 * @return array of known counters IDs
//	 */
//	public static function enumCountersIds()
//	{
//		return array(
//			self::COUNTER_TASKS_TOTAL,
//			self::COUNTER_TASKS_MY,
//			self::COUNTER_TASKS_ACCOMPLICE,
//			self::COUNTER_TASKS_AUDITOR,
//			self::COUNTER_TASKS_ORIGINATOR,
//			self::COUNTER_TASKS_MY_NEW,
//			self::COUNTER_TASKS_ACCOMPLICE_NEW,
//			self::COUNTER_TASKS_MY_EXPIRED,
//			self::COUNTER_TASKS_ACCOMPLICE_EXPIRED,
//			self::COUNTER_TASKS_AUDITOR_EXPIRED,
//			self::COUNTER_TASKS_ORIGINATOR_EXPIRED,
//			self::COUNTER_TASKS_MY_EXPIRED_CANDIDATES,
//			self::COUNTER_TASKS_ACCOMPLICE_EXPIRED_CANDIDATES,
//			self::COUNTER_TASKS_ORIGINATOR_WAIT_CTRL,
//			self::COUNTER_TASKS_MY_WO_DEADLINE,
//			self::COUNTER_TASKS_ORIGINATOR_WO_DEADLINE
//		);
//	}
//
//	/**
//	 * @return array of problem counters IDs
//	 */
//	public static function enumProblemCountersIds()
//	{
//		return array(
//			self::COUNTER_TASKS_MY_NEW,
//			self::COUNTER_TASKS_ACCOMPLICE_NEW,
//			self::COUNTER_TASKS_MY_EXPIRED,
//			self::COUNTER_TASKS_ACCOMPLICE_EXPIRED,
//			self::COUNTER_TASKS_AUDITOR_EXPIRED,
//			self::COUNTER_TASKS_ORIGINATOR_EXPIRED,
//			self::COUNTER_TASKS_MY_EXPIRED_CANDIDATES,
//			self::COUNTER_TASKS_ACCOMPLICE_EXPIRED_CANDIDATES,
//			self::COUNTER_TASKS_ORIGINATOR_WAIT_CTRL,
//			self::COUNTER_TASKS_MY_WO_DEADLINE,
//			self::COUNTER_TASKS_ORIGINATOR_WO_DEADLINE
//		);
//	}
//
//	/**
//	 * Get instance of multiton tasks' list controller
//	 */
//	public static function getInstance()
//	{
//		if (self::$instanceOfSelf === null)
//			self::$instanceOfSelf = new self();
//
//		return (self::$instanceOfSelf);
//	}
//
//
//	/**
//	 * prevent creating through "new"
//	 */
//	private function __construct()
//	{
//	}
//
//	/**
//	 * @return string
//	 */
	public static function agent($offset = 0)
	{
//		$nextOffset = self::countExpiredAndExpiredSoonTasks($offset);
//
//		$agent = CAgent::GetList(array(), array(
//			'MODULE_ID' => 'tasks',
//			'ACTIVE'=>'Y',
//			'NAME' => 'CTaskCountersProcessor::agent('.$offset.');'
//		))->fetch();
//
//		if($agent['ID'])
//		{
//			if($nextOffset > self::AGENT_HIT_LIMIT)
//			{
//				CAgent::Update($agent['ID'], array(
//					'AGENT_INTERVAL'=>60
//				));
//			}else{
//				CAgent::Update($agent['ID'], array(
//					'AGENT_INTERVAL'=>300
//				));
//			}
//		}
//
//		return "CTaskCountersProcessor::agent({$nextOffset});";
	}
//
//	public static function ensureAgentExists()
//	{
//		$agent = CAgent::GetList(array(), array(
//			'MODULE_ID' => 'tasks',
//			'NAME' => 'CTaskCountersProcessor::agent%'
//		))->fetch();
//
//		if(!is_array($agent) || !isset($agent['ID']))
//		{
//			CAgent::AddAgent(
//				'CTaskCountersProcessor::agent(0);',
//				'tasks',
//				'N',
//				300
//			);	// every 10 minutes
//		}
//	}
//
//	/**
//	 * @param int $offset
//	 *
//	 * @return int
//	 */
//	public static function countExpiredAndExpiredSoonTasks($offset = 0)
//	{
//		$query = new Entity\Query(MemberTable::getEntity());
//		$query->addFilter('=TASK.ZOMBIE', 'N');
//		$query->addFilter('!=TASK.DEADLINE', NULL);
//
//		$queryCnt = clone $query;
//
//		$queryCnt->setSelect(array(
//			new Entity\ExpressionField('CNT', 'COUNT(*)')
//		));
//
//		$totalCountRes = $queryCnt->exec()->fetch();
//		$totalCount = $totalCountRes['CNT'];
//
//		$query->addGroup('USER_ID');
//		$query->addGroup('TYPE');
//
//		$query->setSelect(array(
//			'USER_ID',
//			'TYPE'
//		));
//
//		$query->setLimit(self::AGENT_HIT_LIMIT);
//		$query->setOffset($offset);
//
//		$_query = $query->getQuery();
//
//		$res = $query->exec();
//		$users = $usersTotal = array();
//		while ($row = $res->fetch())
//		{
//			$users[$row['TYPE']][] = $row['USER_ID'];
//			$usersTotal[] = $row['USER_ID'];
//		}
//
//		if(count($users) < 1)
//		{
//			return 0;
//		}
//
//		if(isset($users['A']))//ACCOMPLISHED
//		{
//			self::processRecalculateAccomplicesExpired($users['A']);
//			self::processRecalculateAccomplicesExpiredSoon($users['A']);
//			self::processRecalculateAccomplices($users['A']);
//		}
//
//		if(isset($users['U']))//AUDITORS
//		{
//			self::processRecalculateAuditorsExpired($users['U']);
//			self::processRecalculateAuditors($users['U']);
//		}
//
//		if(isset($users['R']))// RESPONSIBLE
//		{
//			self::processRecalculateMyTasksExpired($users['R']);
//			self::processRecalculateMyTasksExpiredSoon($users['R']);
//			self::processRecalculateMyTasks($users['R']);
//		}
//
//		if(isset($users['O']))// ORIGINATOR
//		{
//			self::processRecalculateOriginatorsExpired($users['O']);
//			self::processRecalculateOriginators($users['O']);
//		}
//
//		self::processRecalculateTotal($usersTotal);
//
//		if($totalCount > self::AGENT_HIT_LIMIT)
//			return self::AGENT_HIT_LIMIT + $offset;
//		return 0;
//	}
//
//	/**
//	 * @deprecated
//	 */
//	public function onBeforeTaskAdd(&$arFields, $effectiveUserId)
//	{
//	}
//
//
//	/**
//	 * @param array $arFields
//	 * @internal param int $effectiveUserId
//	 */
//	public static function onAfterTaskAdd($arFields)
//	{
//		$originatorId  = (int) $arFields['CREATED_BY'];
//		$responsibleId = (int) $arFields['RESPONSIBLE_ID'];
//
//		// only for responsible, because originator already viewed task
//		self::processRecalculateMyTasksNew($responsibleId);
//		self::processRecalculateMyTasksWithoutDeadline(array($originatorId, $responsibleId));
//
//		self::processRecalculateMyTasksExpired(array($originatorId, $responsibleId));
//		self::processRecalculateMyTasksExpiredSoon(array($originatorId, $responsibleId));
//
//		self::processRecalculateOriginatorsWaitCtrl(array($originatorId));
//		self::processRecalculateOriginatorsExpired(array($originatorId));
//		self::processRecalculateOriginatorsWithoutDeadline(array($originatorId));
//		self::processRecalculateOriginators(array($originatorId));
//
//
//		if (count($arFields['ACCOMPLICES'])>0)
//		{
//			foreach($arFields['ACCOMPLICES'] as $accId)
//			{
//				self::processRecalculateAccomplicesNew($accId);
//			}
//
//			self::processRecalculateAccomplicesExpired($arFields['ACCOMPLICES']);
//			self::processRecalculateAccomplicesExpiredSoon($arFields['ACCOMPLICES']);
//			self::processRecalculateAccomplices($arFields['ACCOMPLICES']);
//		}
//
//		if (count($arFields['AUDITORS'])>0)
//		{
//			self::processRecalculateAuditorsExpired($arFields['AUDITORS']);
//			self::processRecalculateAuditors($arFields['AUDITORS']);
//		}
//
//		// need exec last because it counter depends from other
//		self::processRecalculateMyTasks(array($originatorId, $responsibleId));
//	}
//
//
//	/**
//	 * @param $taskId
//	 * @param $arFields
//	 *
//	 * @deprecated
//	 */
//	public static function onBeforeTaskDelete($taskId, $arFields)
//	{}
//
//	/**
//	 * @param $arFields
//	 */
//	public static function onAfterTaskDelete($arFields)
//	{
//		$originatorId  = (int) $arFields['CREATED_BY'];
//		$responsibleId = (int) $arFields['RESPONSIBLE_ID'];
//
//		self::processRecalculateMyTasksNew($responsibleId);
//		self::processRecalculateMyTasksWithoutDeadline(array($originatorId, $responsibleId));
//
//		self::processRecalculateMyTasksExpired(array($originatorId, $responsibleId));
//		self::processRecalculateMyTasksExpiredSoon(array($originatorId, $responsibleId));
//
//		self::processRecalculateOriginatorsWaitCtrl(array($originatorId));
//		self::processRecalculateOriginatorsExpired(array($originatorId));
//		self::processRecalculateOriginatorsWithoutDeadline(array($originatorId));
//
//		self::processRecalculateOriginators(array($originatorId));
//
//
//		if (count($arFields['ACCOMPLICES'])>0)
//		{
//			foreach($arFields['ACCOMPLICES'] as $accId)
//			{
//				self::processRecalculateAccomplicesNew($accId);
//			}
//
//			self::processRecalculateAccomplicesExpired($arFields['ACCOMPLICES']);
//			self::processRecalculateAccomplicesExpiredSoon($arFields['ACCOMPLICES']);
//			self::processRecalculateAccomplices($arFields['ACCOMPLICES']);
//		}
//
//		if (count($arFields['AUDITORS'])>0)
//		{
//			self::processRecalculateAuditorsExpired($arFields['AUDITORS']);
//			self::processRecalculateAuditors($arFields['AUDITORS']);
//		}
//
//		// need exec last because it counter depends from other
//		self::processRecalculateMyTasks(array($originatorId, $responsibleId));
//
//	}
//
//	/**
//	 * @param $taskId
//	 * @param $userId
//	 * @param $onTaskAdd
//	 *
//	 * @deprecated
//	 */
//	public static function onBeforeTaskViewedFirstTime($taskId, $userId, $onTaskAdd)
//	{}
//
//	/**
//	 * @param $taskId
//	 * @param $userId
//	 * @param $onTaskAdd
//	 */
//	public static function onAfterTaskViewedFirstTime($taskId, $userId, $onTaskAdd)
//	{
//		if($onTaskAdd)
//		{
//			return;
//		}
//
//		self::processRecalculateMyTasksNew($userId);
//		self::processRecalculateAccomplicesNew($userId);
//
//		//after, because depends
//		self::processRecalculateMyTasks(array($userId));
//		self::processRecalculateAccomplices(array($userId));
//	}
//
//
//	/**
//	 * @param $taskId
//	 * @param $arTask
//	 * @param $arFields
//	 *
//	 * @deprecated
//	 */
//	public function onBeforeTaskUpdate($taskId, $arTask, &$arFields)
//	{
//	}
//
//	/**
//	 * @param $arPrevFields
//	 * @param $arNewFields
//	 */
//	public static function onAfterTaskUpdate($arPrevFields, $arNewFields)
//	{
//		$responsibles = array($arPrevFields['RESPONSIBLE_ID'], $arNewFields['RESPONSIBLE_ID']);
//
//		self::processRecalculateMyTasksNew($arPrevFields['RESPONSIBLE_ID']);
//		if($arNewFields['RESPONSIBLE_ID'] && $arNewFields['RESPONSIBLE_ID'] != $arPrevFields['RESPONSIBLE_ID'])
//		{
//			self::processRecalculateMyTasksNew($arNewFields['RESPONSIBLE_ID']);
//		}
//
//		$originators = array($arPrevFields['CREATED_BY'], $arNewFields['CREATED_BY']);
//		self::processRecalculateOriginatorsWaitCtrl($originators);
//		self::processRecalculateOriginatorsExpired($originators);
//		self::processRecalculateOriginatorsWithoutDeadline($originators);
//		self::processRecalculateOriginators($originators);
//
//
//		$users_my = array_unique(array(
//			$arPrevFields['CREATED_BY'],
//			$arPrevFields['RESPONSIBLE_ID'],
//			$arNewFields['CREATED_BY'],
//			$arNewFields['RESPONSIBLE_ID']
//		));
//		self::processRecalculateMyTasksWithoutDeadline($users_my);
//
//		self::processRecalculateMyTasksExpired($users_my);
//		self::processRecalculateMyTasksExpiredSoon($users_my);
//
//		if($arPrevFields['ACCOMPLICES'] || $arNewFields['ACCOMPLICES'])
//		{
//			$acc = array_unique(array_merge((array)$arPrevFields['ACCOMPLICES'], (array)$arNewFields['ACCOMPLICES']));
//
//			foreach($acc as $accId)
//			{
//				self::processRecalculateAccomplicesNew($accId);
//			}
//
//			self::processRecalculateAccomplicesExpiredSoon($acc);
//			self::processRecalculateAccomplicesExpired($acc);
//
//			self::processRecalculateAccomplices($acc);
//		}
//
//		if($arPrevFields['AUDITORS'] || $arNewFields['AUDITORS'])
//		{
//			$au = array_merge((array)$arPrevFields['AUDITORS'], (array)$arNewFields['AUDITORS']);
//
//			self::processRecalculateAuditorsExpired($au);
//			self::processRecalculateAuditors($au);
//		}
//
//		// need exec last because it counter depends from other
//		self::processRecalculateMyTasks($responsibles);
//	}
//
//	// --------------------- Private functions are below -----------------------
//	/**
//	 * @param $status
//	 * @return bool
//	 */
//	private static function isCompletedStatus($status)
//	{
//		return(in_array(
//			(int) $status,
//			array(
//				CTasks::STATE_DECLINED,
//				CTasks::STATE_SUPPOSEDLY_COMPLETED,
//				CTasks::STATE_COMPLETED
//			),
//			true
//		));
//	}
//
//
//	/**
//	 * @param $status
//	 * @return bool
//	 */
//	private static function isNewStatus($status)
//	{
//		return(in_array(
//			(int) $status,
//			array(
//				CTasks::STATE_NEW,
//				CTasks::STATE_PENDING
//			),
//			true
//		));
//	}
//
//	/**
//	 * @param $deadline
//	 * @return bool
//	 */
//	private static function isDeadlineExpired($deadline)
//	{
//		$time = self::getEdgeDateTime(); // expiredEdgeDateTime expiredSoonEdgeDateTime
//
//		return self::isDeadlinePresent($deadline)
//			&& MakeTimeStamp($deadline) < MakeTimeStamp($time['expiredEdgeDateTime']);
//	}
//
//	/**
//	 * @param $deadline
//	 * @return bool
//	 */
//	protected static function isDeadlinePresent($deadline)
//	{
//		return trim($deadline) != '';
//	}
//
//	/**
//	 * @param $deadline
//	 * @return bool
//	 */
//	private static function isDeadlineExpiredSoon($deadline)
//	{
//		$time = self::getEdgeDateTime(); // expiredEdgeDateTime expiredSoonEdgeDateTime
//
//		return self::isDeadlinePresent($deadline)
//			&& MakeTimeStamp($deadline) >= MakeTimeStamp($time['expiredEdgeDateTime'])
//			&& MakeTimeStamp($deadline) < MakeTimeStamp($time['expiredSoonEdgeDateTime']);
//	}
//
//	/**
//	 * @return bool|int|null
//	 */
//	private function getAdminId() //TODO NEED DELETE
//	{
//		static $adminId;
//
//		if($adminId === null)
//		{
//			$adminId = CTasksTools::GetCommanderInChief();
//			if(!intval($adminId))
//			{
//				CAdminNotify::Add(
//					array(
//						"MESSAGE" => GetMessage('TASKS_COUNTERS_PROCESSOR_ADMIN_IS_NOT_AN_ADMIN'),
//						"TAG" => "TASKS_SYSTEM_NO_ADMIN",
//						"MODULE_ID" => "TASKS",
//						"ENABLE_CLOSE" => "Y"
//					)
//				);
//			}
//		}
//
//		return $adminId;
//	}
//
//	/**
//	 * @param array $userIds
//	 *
//	 * @return Entity\Query
//	 */
//	protected static function recountQuery(array $userIds)
//	{
//		$query = new Entity\Query(TaskTable::getEntity());
//		$tableAlias = $query->getInitAlias();
//		$query->setSelect(array(
//			new Entity\ExpressionField('USER_ID', 'RESPONSIBLE_ID'),
//			new Entity\ExpressionField('COUNT', 'COUNT(' . $tableAlias . '.ID)')
//		));
//
//		$query->addFilter('=ZOMBIE', 'N');
//		$query->addFilter(null, array(
//			'LOGIC' => 'OR',
//			array(
//				'=STATUS' => \CTasks::STATE_NEW,
//			),
//			array(
//				'=STATUS' => \CTasks::STATE_PENDING,
//			),
//			array(
//				'=STATUS' => \CTasks::STATE_IN_PROGRESS,
//			),
//			array(
//				'=STATUS' => \CTasks::STATE_SUPPOSEDLY_COMPLETED,
//			)
//		));
//
//		$query->addFilter('>COUNT', 0);
//		$query->addGroup('RESPONSIBLE_ID');
//		$query->addFilter('=RESPONSIBLE_ID', count($userIds) > 1 ? $userIds : (int)$userIds);
//
//		return $query;
//	}
//
//	/**
//	 * @return Entity\Query
//	 */
//	protected static function recountExpiredQuery($op)
//	{
//		$query = new Entity\Query(TaskTable::getEntity());
//		$tableAlias = $query->getInitAlias();
//		$query->setSelect(array(
//			new Entity\ExpressionField('USER_ID', 'RESPONSIBLE_ID'),
//			new Entity\ExpressionField('COUNT', 'COUNT(' . $tableAlias . '.ID)')
//		));
//		$query->addFilter('>COUNT', 0);
//		$query->addFilter('=ZOMBIE', 'N');
//		$query->addFilter(null, array( // IN() not used indexes!
//			'LOGIC'=>$op,
////			array(
////				'!=STATUS' => \CTasks::STATE_SUPPOSEDLY_COMPLETED,
////			),
//			array(
//				'!=STATUS' => \CTasks::STATE_COMPLETED,
//			),
//			array(
//				'!=STATUS' => \CTasks::STATE_DECLINED,
//			)
//		));
//
//		return $query;
//	}
//
//	/**
//	 * @return Entity\Query
//	 */
//	protected static function recountMembersQuery()
//	{
//		$query = new Entity\Query(TaskTable::getEntity());
//		$tableAlias = $query->getInitAlias();
//
//		$query->setSelect(array(
//			'USER_ID' => 'MEMBERS.USER_ID',
//			new Entity\ExpressionField('COUNT', 'COUNT(' . $tableAlias . '.ID)')
//		));
//
//		$query->addFilter('=ZOMBIE', 'N');
//		$query->addFilter('!=CREATOR.ID', NULL);
//		$query->addFilter(null, array(
//			'LOGIC' => 'OR',
//			array(
//				'=STATUS' => \CTasks::STATE_NEW,
//			),
//			array(
//				'=STATUS' => \CTasks::STATE_PENDING,
//			),
//			array(
//				'=STATUS' => \CTasks::STATE_IN_PROGRESS,
//			)
//		));
//
//		$query->addFilter('>COUNT', 0);
//		$query->addGroup('MEMBERS.USER_ID');
//
//		return $query;
//	}
//
//	/**
//	 * @param $type
//	 * @param $data
//	 * @param $userIDs
//	 */
//	protected static function setCounter($type, array $data, array $userIDs)
//	{
//		$users = array();
//
//		foreach ($data as $item)
//		{
//			if ($item['USER_ID'] > 0)
//			{
//				if(self::getCounter($item['USER_ID'], $type) != $item['COUNT'])
//				{
//					\CUserCounter::Set($item['USER_ID'], $type, $item['COUNT'], '**', '', false);
//				}
//				$users[] = $item['USER_ID'];
//			}
//		}
//
//		// clean counters for others users
//		foreach ($userIDs as $userId)
//		{
//			if ($userId > 0 && !in_array($userId, $users))
//			{
//				if(self::getCounter($userId, $type) != 0)
//				{
//					\CUserCounter::Clear($userId, $type, '**', false);
//				}
//			}
//		}
//	}
//
//	/**
//	 * @param int $userId
//	 * @param int $type
//	 *
//	 * @return int
//	 */
//	public static function getCounter($userId, $type)
//	{
//		return (int)CUserCounter::GetValue($userId, $type);
//	}
//
//	/**
//	 * TODO PRIVATE USE
//	 * @return array
//	 */
//	public static function reset()
//	{
//		$data = array();
//		self::countExpiredAndExpiredSoonTasks();
//		$data[self::COUNTER_TASKS_MY] = self::processRecalculateMyTasks(array(1));
//		$data[self::COUNTER_TASKS_MY_EXPIRED] = self::processRecalculateMyTasksExpired(array(1));
//		$data[self::COUNTER_TASKS_MY_EXPIRED_CANDIDATES] = self::processRecalculateMyTasksExpiredSoon(array(1));
//		$data[self::COUNTER_TASKS_MY_NEW] = self::processRecalculateMyTasksNew(1);
//		$data[self::COUNTER_TASKS_MY_WO_DEADLINE] = self::processRecalculateMyTasksWithoutDeadline(array(1));
//
//		$data[self::COUNTER_TASKS_ACCOMPLICE] = self::processRecalculateAccomplices(array(1));
//		$data[self::COUNTER_TASKS_ACCOMPLICE_NEW] = self::processRecalculateAccomplicesNew(array(1));
//		$data[self::COUNTER_TASKS_ACCOMPLICE_EXPIRED] = self::processRecalculateAccomplicesExpired(array(1));
//		$data[self::COUNTER_TASKS_ACCOMPLICE_EXPIRED_CANDIDATES] = self::processRecalculateAccomplicesExpiredSoon(array(1));
//
//		$data[self::COUNTER_TASKS_AUDITOR] = self::processRecalculateAuditors(array(1));
//		$data[self::COUNTER_TASKS_AUDITOR_EXPIRED] = self::processRecalculateAuditorsExpired(array(1));
//
//		$data[self::COUNTER_TASKS_ORIGINATOR] = self::processRecalculateOriginators(array(1));
//		$data[self::COUNTER_TASKS_ORIGINATOR_EXPIRED] = self::processRecalculateOriginatorsExpired(array(1));
//
//		return $data;
//	}
//
//	/**
//	 * @param array $userIds
//	 *
//	 * @return array
//	 */
//	protected static function processRecalculateMyTasks(array $userIds)
//	{
//		$data = array();
//
//		foreach($userIds as $userId)
//		{
//			$expired = self::getCounter($userId, self::COUNTER_TASKS_MY_EXPIRED);
//			$expiredSoon = self::getCounter($userId, self::COUNTER_TASKS_MY_EXPIRED_CANDIDATES);
//			$woDeadLine = self::getCounter($userId, self::COUNTER_TASKS_MY_WO_DEADLINE);
//			$new = self::getCounter($userId, self::COUNTER_TASKS_MY_NEW);
//
//			$data[] = array(
//				'USER_ID' => $userId,
//				'COUNT' => $expired + $expiredSoon + $woDeadLine + $new
//			);
//		}
//
//		self::setCounter(self::COUNTER_TASKS_MY, $data, $userIds);
//
//		self::processRecalculateTotal($userIds);
//		return $data;
//	}
//
//	/**
//	 * @return array
//	 */
//	protected static function getEdgeDateTime()
//	{
//		$expired = new \Bitrix\Main\Type\DateTime();
//		$expiredSoon = new \Bitrix\Main\Type\DateTime();
//		$expiredSoon->add('T'.self::DEADLINE_TIME.'S');
//
//		return array(
//			'expiredEdgeDateTime' => $expired,
//			'expiredSoonEdgeDateTime' => $expiredSoon,
//		);
//	}
//
//	/**
//	 * Returns datetime string, before which tasks is counted as "expired"
//	 *
//	 * @return string
//	 */
//	public function getNowDateTime()
//	{
//		$time = self::getEdgeDateTime();
//		return $time['expiredEdgeDateTime'];
//	}
//
//
//	/**
//	 * Returns datetime string, before which tasks is counted as "expired soon"
//	 *
//	 * @return string
//	 */
//	public function getExpiredSoonEdgeDateTime()
//	{
//		$time = self::getEdgeDateTime();
//		return $time['expiredSoonEdgeDateTime'];
//	}
//
//	/**
//	 * @param array $userIds
//	 * @return array
//	 * @noinspection PhpDeprecationInspection
//	 *
//	 */
//	protected static function processRecalculateMyTasksExpired(array $userIds)
//	{
//		$userIds = array_diff($userIds, array(null));
//
//		$deadline = self::getEdgeDateTime();
//
//		$query = new Entity\Query(TaskTable::getEntity());
//		$tableAlias = $query->getInitAlias();
//		$query->setSelect(array(
//			new Entity\ExpressionField('USER_ID', 'RESPONSIBLE_ID'),
//			new Entity\ExpressionField('COUNT', 'COUNT(' . $tableAlias . '.ID)')
//		));
//		$query->addFilter('>COUNT', 0);
//		$query->addFilter('!=CREATOR.ID', NULL);
//		$query->addFilter('!=CREATOR.ID', 'RESPONSIBLE_ID');
//		$query->addFilter('=ZOMBIE', 'N');
//		$query->addFilter(null, array(
//			'LOGIC'=>'AND',
//			array(
//				'!=STATUS' => \CTasks::STATE_SUPPOSEDLY_COMPLETED,
//			),
//			array(
//				'!=STATUS' => \CTasks::STATE_COMPLETED,
//			),
//			array(
//				'!=STATUS' => \CTasks::STATE_DECLINED,
//			)
//		));
//
//
//		$query->addFilter(
//			'<DEADLINE', $deadline['expiredEdgeDateTime']
//		);
//		$query->addFilter('=RESPONSIBLE_ID', $userIds);
//		$query->setGroup('RESPONSIBLE_ID');
//
//		$data = $query->exec()->fetchAll();
//		$_query = $query->getQuery();
//
//		self::setCounter(self::COUNTER_TASKS_MY_EXPIRED, $data, $userIds);
//
//		if(self::$debug)
//		{
//			foreach($userIds as $userId)
//			{
//				self::debug($userId, array(
//					'STATUS' => -1,
//					'RESPONSIBLE_ID' => $userId
//				), self::COUNTER_TASKS_MY_EXPIRED, $query);
//			}
//		}
//		return $data;
//	}
//
//	/**
//	 * @param array $userIds
//	 * @return array
//	 * @noinspection PhpDeprecationInspection
//	 */
//	protected static function processRecalculateMyTasksExpiredSoon(array $userIds)
//	{
//		$userIds = array_diff($userIds, array(null));
//
//		$deadline = self::getEdgeDateTime();
//
//		$query = new Entity\Query(TaskTable::getEntity());
//		$tableAlias = $query->getInitAlias();
//		$query->setSelect(array(
//			new Entity\ExpressionField('USER_ID', 'RESPONSIBLE_ID'),
//			new Entity\ExpressionField('COUNT', 'COUNT(' . $tableAlias . '.ID)')
//		));
//		$query->addFilter('>COUNT', 0);
//		$query->addFilter('=ZOMBIE', 'N');
//		$query->addFilter('!=CREATOR.ID', NULL);
//		$query->addFilter('!=CREATOR.ID', 'RESPONSIBLE_ID');
//		$query->addFilter(null, array( // IN() not used indexes!
//			'LOGIC'=>'AND',
//			array(
//				'!=STATUS' => \CTasks::STATE_SUPPOSEDLY_COMPLETED,
//			),
//			array(
//				'!=STATUS' => \CTasks::STATE_COMPLETED,
//			),
//			array(
//				'!=STATUS' => \CTasks::STATE_DECLINED,
//			)
//		));
//
//		$query->addFilter('<DEADLINE', $deadline['expiredSoonEdgeDateTime']);
//		$query->addFilter('>=DEADLINE', $deadline['expiredEdgeDateTime']);
//		$query->addFilter('=RESPONSIBLE_ID', $userIds);
//
//		$query->setGroup('RESPONSIBLE_ID');
//
//		$data = $query->exec()->fetchAll();
//		$_query = $query->getQuery();
//
//		self::setCounter(self::COUNTER_TASKS_MY_EXPIRED_CANDIDATES, $data, $userIds);
//
//		if(self::$debug)
//		{
//			foreach($userIds as $userId)
//			{
//				self::debug($userId, array(
//					'>=DEADLINE' => $deadline['expiredEdgeDateTime'],
//					'<DEADLINE' => $deadline['expiredSoonEdgeDateTime'],
//					'RESPONSIBLE_ID' => $userId,
//					'!REAL_STATUS'=>array(4,5,7)
//				), self::COUNTER_TASKS_MY_EXPIRED_CANDIDATES, $query);
//			}
//		}
//
//		return $data;
//	}
//
//	/**
//	 * @param int $userId
//	 *
//	 * @return array
//	 */
//	protected static function processRecalculateMyTasksNew($userId)
//	{
//		$query = new Entity\Query(TaskTable::getEntity());
//
//		$tableAlias = $query->getInitAlias();
//		$query->setSelect(array(
//			'USER_ID' => 'RESPONSIBLE_ID',
//			new Entity\ExpressionField('COUNT', 'COUNT('.$tableAlias.'.ID)')
//		));
//		$query->addFilter('!=CREATOR.ID', NULL);
//		$query->addFilter('!=CREATOR.ID', 'RESPONSIBLE_ID');
//		$query->addFilter('=ZOMBIE', 'N');
//		$query->addFilter(null, array(
//			'LOGIC' => 'OR',
//			array(
//				'=STATUS' => \CTasks::STATE_NEW,
//			),
//			array(
//				'=STATUS' => \CTasks::STATE_PENDING,
//			)
//		));
//
//		$query->addFilter('>COUNT', 0);
//		$query->addGroup('RESPONSIBLE_ID');
//
//		$query->addFilter('=RESPONSIBLE_ID', $userId);
//
//
//		$query->registerRuntimeField(null, new Entity\ReferenceField(
//			'TV', \Bitrix\Tasks\Internals\Task\ViewedTable::getEntity(),
//			array(
//				'=this.ID'=>'ref.TASK_ID',
//				'=ref.USER_ID' => array('?', $userId)
//			)
//		));
//		$query->addFilter('=TV.TASK_ID', NULL);
//
//		$data = $query->exec()->fetchAll();
//
//		self::setCounter(self::COUNTER_TASKS_MY_NEW, $data, array($userId));
//
//		if(self::$debug)
//		{
//			self::debug($userId, array(
//				'VIEWED' => 0,
//				'VIEWED_BY' => $userId,
//				'RESPONSIBLE_ID' => $userId
//			), self::COUNTER_TASKS_MY_NEW, $query);
//
//		}
//
//		return $data;
//	}
//
//	private static function debug($userId, $filter, $category, $query, $userIds = array())
//	{
//		$cnt = CTasks::GetCountInt($filter);
//
//		if($cnt != self::getCounter($userId, $category))
//		{
//			\Bitrix\Tasks\Util::log(
//				print_r(array(
//					'userId'=>$userId,
//					'category'=>$category,
//					'ctasks::getcount' => $cnt,
//					'ccounterprocessor' => self::getCounter($userId, $category),
//					'query' => $query->getQuery(),
//					'data' => $query->exec()->fetchAll(),
//					'userIds'=>$userIds
//				), true)
//			);
//		}
//	}
//
//	/**
//	 * @param array $userIds
//	 *
//	 * @return array
//	 */
//	protected static function processRecalculateMyTasksWithoutDeadline(array $userIds)
//	{
//		$userIds = array_diff($userIds, array(null));
//
//		$query = new Entity\Query(TaskTable::getEntity());
//		$tableAlias = $query->getInitAlias();
//		$query->setSelect(array(
//			new Entity\ExpressionField('USER_ID', 'RESPONSIBLE_ID'),
//			new Entity\ExpressionField('COUNT', 'COUNT(' . $tableAlias . '.ID)')
//		));
//
//		$query->addFilter('=ZOMBIE', 'N');
//		$query->addFilter('!=CREATOR.ID', NULL);
//		$query->addFilter('!=CREATOR.ID', 'RESPONSIBLE_ID');
//		$query->addFilter('!=STATUS', array(
//			\CTasks::STATE_DECLINED,
//			\CTasks::STATE_SUPPOSEDLY_COMPLETED,
//			\CTasks::STATE_COMPLETED,
//			\CTasks::STATE_DEFERRED,
//		));
//
//		$query->addFilter('>COUNT', 0);
//		$query->addGroup('RESPONSIBLE_ID');
//
//
//		$tableAlias = $query->getInitAlias();
//		$query->addFilter('=DEADLINE', NULL);
////		$query->addFilter('!=RESPONSIBLE_ID', new SqlExpression($tableAlias . '.CREATED_BY'));
//
//		// select * from b_tasks where RESPONSIBLE_ID in (193, 281) AND ZOMBIE='N' AND STATUS NOT IN (7,4,5) AND DEADLINE IS NULL
//
//		$data = $query->exec()->fetchAll();
//		$_query = $query->getQuery();
//
//		self::setCounter(self::COUNTER_TASKS_MY_WO_DEADLINE, $data, $userIds);
//
//		if(self::$debug)
//		{
//			foreach($userIds as $userId)
//			{
//				self::debug($userId, array(
//					'DEADLINE' => '',
//					'RESPONSIBLE_ID' => $userId,
//					'!CREATED_BY' => $userId,
//					'!REAL_STATUS'=>array(4,5,7)
//				), self::COUNTER_TASKS_MY_WO_DEADLINE, $query);
//			}
//		}
//		return $data;
//	}
//
//	/**
//	 * @param array $userIds
//	 *
//	 * @return array
//	 */
//	protected static function processRecalculateAccomplices(array $userIds)
//	{
//		$userIds = array_diff($userIds, array(null));
//		$data = array();
//		foreach($userIds as $userId)
//		{
//			$expired = self::getCounter($userId, self::COUNTER_TASKS_ACCOMPLICE_EXPIRED);
//			$expiredSoon = self::getCounter($userId, self::COUNTER_TASKS_ACCOMPLICE_EXPIRED_CANDIDATES);
//			$new = self::getCounter($userId, self::COUNTER_TASKS_ACCOMPLICE_NEW);
//
//			$data[] = array(
//				'USER_ID' => $userId,
//				'COUNT' => $expired + $expiredSoon + $new
//			);
//		}
//
//		self::setCounter(self::COUNTER_TASKS_ACCOMPLICE, $data, $userIds);
//
//		self::processRecalculateTotal($userIds);
//		return $data;
//	}
//
//	/**
//	 * @param array $userIds
//	 *
//	 * @return array
//	 */
//	protected static function processRecalculateAccomplicesExpired(array $userIds)
//	{
//		$userIds = array_diff($userIds, array(null));
//
//		$deadline = self::getEdgeDateTime();
//		$query = self::recountMembersQuery();
//		$query->addFilter('=MEMBERS.TYPE', 'A');
//		$query->addFilter('=MEMBERS.USER_ID', $userIds);
//		$query->addFilter('<DEADLINE', $deadline['expiredEdgeDateTime']);
//		$data = $query->exec()->fetchAll();
//
//		self::setCounter(self::COUNTER_TASKS_ACCOMPLICE_EXPIRED, $data, $userIds);
//
//		if(self::$debug)
//		{
//			foreach($userIds as $userId)
//			{
//				self::debug($userId, array(
//					'ACCOMPLICE' => $userId,
//					'STATUS' => -1
//				), self::COUNTER_TASKS_ACCOMPLICE_EXPIRED, $query);
//			}
//		}
//
//		return $data;
//	}
//
//	/**
//	 * @param array $userIds
//	 *
//	 * @return array
//	 */
//	protected static function processRecalculateAccomplicesExpiredSoon(array $userIds)
//	{
//		$userIds = array_diff($userIds, array(null));
//
//		$deadline = self::getEdgeDateTime();
//		$query = self::recountMembersQuery();
//		$query->addFilter('=MEMBERS.TYPE', 'A');
//		$query->addFilter('=MEMBERS.USER_ID', $userIds);
//		$query->addFilter('<DEADLINE', $deadline['expiredSoonEdgeDateTime']);
//		$query->addFilter('>=DEADLINE', $deadline['expiredEdgeDateTime']);
//		$data = $query->exec()->fetchAll();
//
//		self::setCounter(self::COUNTER_TASKS_ACCOMPLICE_EXPIRED_CANDIDATES, $data, $userIds);
//
//
//		if(self::$debug)
//		{
//			foreach($userIds as $userId)
//			{
//				self::debug($userId, array(
//					'ACCOMPLICE' => $userId,
//					'>=DEADLINE' => $deadline['expiredEdgeDateTime'],
//					'<DEADLINE' => $deadline['expiredSoonEdgeDateTime'],
//					'!REAL_STATUS'=>array(4,5,7)
//				), self::COUNTER_TASKS_ACCOMPLICE_EXPIRED_CANDIDATES, $query);
//			}
//		}
//
//		return $data;
//	}
//
//	/**
//	 * @param int $userId
//	 *
//	 * @return array
//	 */
//	protected static function processRecalculateAccomplicesNew($userId)
//	{
//		$query = new Entity\Query(TaskTable::getEntity());
//		$tableAlias = $query->getInitAlias();
//		$query->addFilter('=ZOMBIE', 'N');
//		$query->addFilter('!=CREATOR.ID', NULL);
//		$query->addFilter(null, array(
//			'LOGIC' => 'OR',
//			array(
//				'=STATUS' => \CTasks::STATE_NEW,
//			),
//			array(
//				'=STATUS' => \CTasks::STATE_PENDING,
//			),
////			array(
////				'=STATUS' => \CTasks::STATE_IN_PROGRESS,
////			)
//		));
//
//		$query->addFilter('=MEMBERS.TYPE', 'A');
//		$query->addFilter('=MEMBERS.USER_ID', $userId);
//
//		$query->registerRuntimeField(null, new Entity\ReferenceField(
//			'TV', \Bitrix\Tasks\Internals\Task\ViewedTable::getEntity(),
//			array(
//				'=this.MEMBERS.TASK_ID'=>'ref.TASK_ID',
//				'=this.MEMBERS.USER_ID' =>'ref.USER_ID'
//			)
//		));
//		$query->addFilter('=TV.TASK_ID', NULL);
//
//		$query->setSelect(array(
//			'USER_ID' => 'MEMBERS.USER_ID',
//			new Entity\ExpressionField('COUNT', 'COUNT('.$tableAlias.'.ID)')
//		));
//
//		$data = $query->exec()->fetchAll();
//		$_query = $query->getQuery();
//
//		self::setCounter(self::COUNTER_TASKS_ACCOMPLICE_NEW, $data, array($userId));
//
//		if(self::$debug)
//		{
//			self::debug($userId, array(
//				'ACCOMPLICE' => $userId,
//				'VIEWED' => 0,
//				'VIEWED_BY'=>$userId
//			), self::COUNTER_TASKS_ACCOMPLICE_NEW, $query);
//
//		}
//		return $data;
//	}
//
//
//	/**
//	 * @param array $userIds
//	 *
//	 * @return array
//	 */
//	protected static function processRecalculateOriginators(array $userIds)
//	{
//		$userIds = array_diff($userIds, array(null));
//		$data = array();
//
//		foreach($userIds as $userId)
//		{
//			$expired = self::getCounter($userId, self::COUNTER_TASKS_ORIGINATOR_EXPIRED);
//			$withoutDeadline = self::getCounter($userId, self::COUNTER_TASKS_ORIGINATOR_WO_DEADLINE);
//			$wait = self::getCounter($userId, self::COUNTER_TASKS_ORIGINATOR_WAIT_CTRL);
//
//			$data[] = array(
//				'USER_ID' => $userId,
//				'COUNT' => $expired + $withoutDeadline + $wait
//			);
//		}
//
//		self::setCounter(self::COUNTER_TASKS_ORIGINATOR, $data, $userIds);
//
//		self::processRecalculateTotal($userIds);
//		return $data;
//	}
//
//	/**
//	 * @param array $userIds
//	 * @noinspection PhpDeprecationInspection
//	 * @return array
//	 */
//	protected static function processRecalculateOriginatorsExpired(array $userIds)
//	{
//		$userIds = array_diff($userIds, array(null));
//
//		$deadline = self::getEdgeDateTime();
//		$query = new Entity\Query(TaskTable::getEntity());
//		$tableAlias = $query->getInitAlias();
//
//		$query->addFilter('!=RESPONSIBLE.ID', NULL);
//		$query->addFilter('=ZOMBIE', 'N');
//		$query->addFilter(null, array(
//			'!=STATUS'=>array(
//				\CTasks::STATE_SUPPOSEDLY_COMPLETED,
//				\CTasks::STATE_COMPLETED
//			),
//			array(
//				'LOGIC'=>'OR',
//				array(
//					'!=STATUS'=>\CTasks::STATE_DECLINED,
//					'!=RESPONSIBLE_ID'=> new SqlExpression($tableAlias . '.CREATED_BY')
//				)
//			)
//		));
//
//		$query->addFilter('>COUNT', 0);
//
//		$query->setSelect(array(
//			new Entity\ExpressionField('USER_ID', 'CREATED_BY'),
//			new Entity\ExpressionField('COUNT', 'COUNT(' . $tableAlias . '.ID)')
//		));
//
//		$query->setGroup(array('CREATED_BY'));
//		$query->addFilter('=CREATED_BY', $userIds);
//
//		$query->addFilter('<DEADLINE', $deadline['expiredEdgeDateTime']);
//
//		$data = $query->exec()->fetchAll();
//
//		self::setCounter(self::COUNTER_TASKS_ORIGINATOR_EXPIRED, $data, $userIds);
//
//		if(self::$debug)
//		{
//			foreach($userIds as $userId)
//			{
//				self::debug($userId, array(
//					'CREATED_BY' => $userId,
//					'!REFERENCE:RESPONSIBLE_ID' => 'CREATED_BY',
//					'STATUS'=>-1
//				), self::COUNTER_TASKS_ORIGINATOR_EXPIRED, $query);
//			}
//		}
//
//		return $data;
//	}
//
//	/**
//	 * @param array $userIds
//	 *
//	 * @return array
//	 */
//	protected static function processRecalculateOriginatorsWithoutDeadline(array $userIds)
//	{
//		$userIds = array_diff($userIds, array(null));
//
//		$query = new Entity\Query(TaskTable::getEntity());
//		$tableAlias = $query->getInitAlias();
//
//		$query->addFilter('=ZOMBIE', 'N');
//		$query->addFilter('!=STATUS', array(
//				\CTasks::STATE_DECLINED,
//				\CTasks::STATE_COMPLETED,
//				\CTasks::STATE_SUPPOSEDLY_COMPLETED,
//				\CTasks::STATE_DEFERRED,
//			)
//		);
//
//		$query->addFilter('>COUNT', 0);
//
//		$query->setSelect(array(
//			new Entity\ExpressionField('USER_ID', 'CREATED_BY'),
//			new Entity\ExpressionField('COUNT', 'COUNT(' . $tableAlias . '.ID)')
//		));
//
//		$query->setGroup(array('CREATED_BY'));
//		$query->addFilter('!=RESPONSIBLE.ID', NULL);
//		$query->addFilter('=DEADLINE', NULL);
//		$query->addFilter('=CREATED_BY', $userIds);
//		$query->addFilter('!=RESPONSIBLE_ID', new SqlExpression($tableAlias . '.CREATED_BY'));
//		$_query = $query->getQuery();
//		$data = $query->exec()->fetchAll();
//
//		self::setCounter(self::COUNTER_TASKS_ORIGINATOR_WO_DEADLINE, $data, $userIds);
//
//		if(self::$debug)
//		{
//			foreach($userIds as $userId)
//			{
//				self::debug($userId, array(
//					'CREATED_BY' => $userId,
//					'!REFERENCE:RESPONSIBLE_ID' => 'CREATED_BY',
//					'!REAL_STATUS'=>array(5,4,7),
//					'DEADLINE'=>''
//				), self::COUNTER_TASKS_ORIGINATOR_WO_DEADLINE, $query,$userIds);
//			}
//		}
//
//		return $data;
//	}
//
//
//	/**
//	 * Recalculateulate tasks where current user (if userIds empty) or all users in userIds array supports
//	 *
//	 * @param array $userIds
//	 *
//	 * @return array
//	 */
//	protected static function processRecalculateAuditors(array $userIds)
//	{
//		$userIds = array_diff($userIds, array(null));
//		$data = array();
//
//		foreach($userIds as $userId)
//		{
//			$expired = self::getCounter($userId, self::COUNTER_TASKS_AUDITOR_EXPIRED);
//
//			$data[] = array(
//				'USER_ID' => $userId,
//				'COUNT' => $expired
//			);
//		}
//
//		self::setCounter(self::COUNTER_TASKS_AUDITOR, $data, $userIds);
//
//		self::processRecalculateTotal($userIds);
//
//
//		return $data;
//	}
//
//	/**
//	 * @param array $userIds
//	 *
//	 * @return array
//	 */
//	protected static function processRecalculateAuditorsExpired(array $userIds)
//	{
//		$userIds = array_diff($userIds, array(null));
//
//		$deadline = self::getEdgeDateTime();
//		$query = self::recountMembersQuery();
//		$query->addFilter('=MEMBERS.TYPE', 'U');
//		$query->addFilter('=MEMBERS.USER_ID', $userIds);
//		$query->addFilter('<DEADLINE', $deadline['expiredEdgeDateTime']);
//		$data = $query->exec()->fetchAll();
//
//		self::setCounter(self::COUNTER_TASKS_AUDITOR_EXPIRED, $data, $userIds);
//
//		if(self::$debug)
//		{
//			foreach($userIds as $userId)
//			{
//				self::debug($userId, array(
//					'AUDITOR' => $userId,
//					'REAL_STATUS'=>array(1,2,3,4)
//				), self::COUNTER_TASKS_AUDITOR_EXPIRED, $query);
//			}
//		}
//
//		return $data;
//	}
//
//	/**
//	 * @param array $userIds
//	 *
//	 * @return array
//	 */
//	protected static function processRecalculateOriginatorsWaitCtrl(array $userIds)
//	{
//		$userIds = array_diff($userIds, array(null));
//
//		$query = new Entity\Query(TaskTable::getEntity());
//		$tableAlias = $query->getInitAlias();
//
//		$query->addGroup('CREATED_BY');
//
//		$query->setFilter(array(
//			'=STATUS' => CTasks::STATE_SUPPOSEDLY_COMPLETED,
//			'>COUNT'=>0,
//			'!=RESPONSIBLE_ID'=> new SqlExpression($tableAlias . '.CREATED_BY'),
//			'=CREATED_BY'=>$userIds,
//			'=ZOMBIE'=>'N'
//		));
//
//		$query->setSelect(array(
//			new Entity\ExpressionField('USER_ID', 'CREATED_BY'),
//			new Entity\ExpressionField('COUNT', 'COUNT(' . $tableAlias . '.ID)')
//		));
//
//		$data = $query->exec()->fetchAll();
//
//		self::setCounter(self::COUNTER_TASKS_ORIGINATOR_WAIT_CTRL, $data, $userIds);
//
//		if(self::$debug)
//		{
//			foreach($userIds as $userId)
//			{
//				self::debug($userId, array(
//					'CREATED_BY' => $userId,
//					'REAL_STATUS'=>4,
//					'!REFERENCE:RESPONSIBLE_ID' => 'CREATED_BY',
//				), self::COUNTER_TASKS_ORIGINATOR_WAIT_CTRL, $query);
//			}
//		}
//		return $data;
//	}
//
//	/**
//	 * For left menu
//	 * @param array $userIds
//	 */
//	protected static function processRecalculateTotal(array $userIds)
//	{
//		$data = array();
//		foreach($userIds as $userId)
//		{
//			$data[] = array(
//				'USER_ID'=> $userId,
//				'COUNT'=>
//					self::getCounter($userId, self::COUNTER_TASKS_MY)
//					+ self::getCounter($userId, self::COUNTER_TASKS_ORIGINATOR)
//					+ self::getCounter($userId, self::COUNTER_TASKS_ACCOMPLICE)
//					+ self::getCounter($userId, self::COUNTER_TASKS_AUDITOR)
//			);
//		}
//		self::setCounter(self::COUNTER_TASKS_TOTAL, $data, $userIds);
//	}
}