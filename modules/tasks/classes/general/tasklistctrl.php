<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */

use \Bitrix\Tasks\Internals\Counter;

class CTaskListCtrl
{
	private static $instancesOfSelf = array();

	private $filterByGroupId = null;
	private $oListState = null;
	private $oFilter = null;
	private $loggedInUserId = null;
	private $userId = null;


	/**
	 * Get instance of multiton tasks' list controller
	 *
	 * @param integer $userId
	 */
	public static function getInstance($userId)
	{
		CTaskAssert::assertLaxIntegers($userId);
		CTaskAssert::assert($userId > 0);

		$key = (string) ((int) $userId);

		if ( ! array_key_exists($key, self::$instancesOfSelf) )
			self::$instancesOfSelf[$key] = new self($userId);

		return (self::$instancesOfSelf[$key]);
	}

	public function useState(CTaskListState $oState)
	{
		$this->oListState = $oState;
	}


	public function useAdvancedFilterObject(CTaskFilterCtrlInterface $oFilter)
	{
		$this->oFilter = $oFilter;
	}


	public function setFilterByGroupId($groupId)
	{
		if ( (int) $groupId >= 1)
			$this->filterByGroupId = (int) $groupId;
		else
			$this->filterByGroupId = null;
	}


	public function getCommonFilter()
	{
		$arCommonFilter = array();

		if ($this->oListState->isSubmode(CTaskListState::VIEW_SUBMODE_WITH_SUBTASKS))
		{
			$arCommonFilter['ONLY_ROOT_TASKS'] = 'Y';

			if ($this->oListState->isSubmode(CTaskListState::VIEW_SUBMODE_WITH_GROUPS))
				$arCommonFilter['SAME_GROUP_PARENT'] = 'Y';
		}

		if ($this->filterByGroupId !== null)
			$arCommonFilter['GROUP_ID'] = (int) $this->filterByGroupId;

		return ($arCommonFilter);
	}


	/**
	 * $userId can be integer or array of integers
	 */
	public static function getFilterFor($userId, $userRoleId, $taskCategoryId)
	{
		$subfilterIndex = 0;

		$arFilter = array(
			'::LOGIC' => 'AND'
		);

		switch ($userRoleId)
		{
			case CTaskListState::VIEW_ROLE_RESPONSIBLE:
				$arFilter['RESPONSIBLE_ID'] = $userId;
			break;

			case CTaskListState::VIEW_ROLE_ACCOMPLICE:
				$arFilter['ACCOMPLICE'] = $userId;
			break;

			case CTaskListState::VIEW_ROLE_AUDITOR:
				$arFilter['AUDITOR'] = $userId;
			break;

			case CTaskListState::VIEW_ROLE_ORIGINATOR:
				$arFilter['CREATED_BY']                = $userId;
				$arFilter['!REFERENCE:RESPONSIBLE_ID'] = 'CREATED_BY';
			break;
		}

		switch ($taskCategoryId)
		{
			case CTaskListState::VIEW_TASK_CATEGORY_COMPLETED:
				$arFilter['REAL_STATUS'] = array(
					CTasks::STATE_COMPLETED,
					CTasks::STATE_SUPPOSEDLY_COMPLETED,
					CTasks::STATE_DECLINED
				);
			break;

			case CTaskListState::VIEW_TASK_CATEGORY_WAIT_CTRL:
				$arFilter['REAL_STATUS'] = CTasks::STATE_SUPPOSEDLY_COMPLETED;
			break;

			case CTaskListState::VIEW_TASK_CATEGORY_NEW:
				$arFilter['VIEWED'] = 0;
				$arFilter['VIEWED_BY'] = $userId;
			break;

			case CTaskListState::VIEW_TASK_CATEGORY_ALL:
			break;

			case CTaskListState::VIEW_TASK_CATEGORY_IN_PROGRESS:
				$arFilter['REAL_STATUS'] = array(
					CTasks::STATE_NEW,
					CTasks::STATE_PENDING,
					CTasks::STATE_IN_PROGRESS
				);
			break;

			case CTaskListState::VIEW_TASK_CATEGORY_EXPIRED:
				$arFilter['STATUS'] = CTasks::METASTATE_EXPIRED;
			break;

			case CTaskListState::VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES:
				$arFilter['>=DEADLINE'] = Counter::getExpiredTime();
				$arFilter['<DEADLINE'] = Counter::getExpiredSoonTime();
				$arFilter['!REAL_STATUS'] = array(
					CTasks::STATE_SUPPOSEDLY_COMPLETED,
					CTasks::STATE_COMPLETED,
					CTasks::STATE_DECLINED
				);
			break;

			case CTaskListState::VIEW_TASK_CATEGORY_DEFERRED:
				$arFilter['REAL_STATUS'] = CTasks::STATE_DEFERRED;
			break;

			case CTaskListState::VIEW_TASK_CATEGORY_ATTENTION:
				switch ($userRoleId)
				{
					case CTaskListState::VIEW_ROLE_RESPONSIBLE:
					case CTaskListState::VIEW_ROLE_ACCOMPLICE:
						// selects not viewed tasks, expired and to be expired soon
						$arFilter['!REAL_STATUS'] = array(
							CTasks::STATE_SUPPOSEDLY_COMPLETED,
							CTasks::STATE_COMPLETED,
							CTasks::STATE_DECLINED
						);

						$arFilter['::SUBFILTER-' . (++$subfilterIndex)] = array(
							'::LOGIC'   => 'OR',
							'VIEWED'    => 0,
							'<DEADLINE' => Counter::getExpiredSoonTime()
							// to be expired soon, it's includes already expired tasks too
						);
					break;

					case CTaskListState::VIEW_ROLE_AUDITOR:
					case CTaskListState::VIEW_ROLE_ORIGINATOR:
						// selects only expired tasks
						$arFilter['STATUS'] = CTasks::METASTATE_EXPIRED;
					break;
				}
			break;

			case CTaskListState::VIEW_TASK_CATEGORY_WO_DEADLINE:
				$arFilter['!REAL_STATUS'] = array(
					CTasks::STATE_DECLINED,
					CTasks::STATE_SUPPOSEDLY_COMPLETED,
					CTasks::STATE_COMPLETED
				);

				$arFilter['DEADLINE'] = '';

				if($userRoleId == CTaskListState::VIEW_ROLE_RESPONSIBLE)
				{
					$arFilter['!CREATED_BY'] = $userId;
				}
			break;

			default:
				CTaskAssert::logError('[0x9a0abea0] Unknown $taskCategoryId = ' . $taskCategoryId);
			break;
		}

		return ($arFilter);
	}


	private function __getFilterFor($userRoleId, $taskCategoryId)
	{
		return (self::getFilterFor($this->userId, $userRoleId, $taskCategoryId));
	}


	public function getFilter()
	{
		$curSection = $this->oListState->getSection();

		if ($curSection === CTaskListState::VIEW_SECTION_ADVANCED_FILTER)
		{
			// we are in "advanced-mode" (section "all" and user-defined filters)

			$bGroupMode = false;
			if ($this->filterByGroupId !== null)
				$bGroupMode = true;

			if ($this->oFilter)
				$oFilter = $this->oFilter;
			else
				$oFilter = CTaskFilterCtrl::GetInstance($this->userId, $bGroupMode);

			$arFilter = $oFilter->GetSelectedFilterPresetCondition();
		}
		elseif ($curSection === CTaskListState::VIEW_SECTION_ROLES)
		{
			// we are in "role-mode" (four pre-defined filters at the top of the list)
			$arFilter = $this->__getFilterFor(
				$this->oListState->getUserRole(),
				$this->oListState->getTaskCategory()
			);
		}

		$counterId = self::resolveCounterIdByRoleAndCategory(
			$this->oListState->getUserRole(),
			$this->oListState->getTaskCategory()
		);

		$arFilter['CHECK_PERMISSIONS'] = 'Y';

		// Mark filter. So when it will be used by CTasks::GetList(),
		// CTaskCountersProcessorHomeostasis will check if counter is right
		// or not.

		// This will work out only when resolved counter is not null and we are in "role-mode"
//		$arFilter = CTaskCountersProcessorHomeostasis::injectMarker(
//			$arFilter,
//			$curSection,
//			$counterId,
//			$this->userId
//		);

		return ($arFilter);
	}


	public function getMainCounter()
	{
		return (
			$this->getUserRoleCounter(CTaskListState::VIEW_ROLE_RESPONSIBLE)
			+ $this->getUserRoleCounter(CTaskListState::VIEW_ROLE_ACCOMPLICE)
			+ $this->getUserRoleCounter(CTaskListState::VIEW_ROLE_ORIGINATOR)
			+ $this->getUserRoleCounter(CTaskListState::VIEW_ROLE_AUDITOR)
		);
	}


	public static function getMainCounterForUser($userId)
	{
		return (
			self::__getUserRoleCounter(CTaskListState::VIEW_ROLE_RESPONSIBLE, $userId)
			+ self::__getUserRoleCounter(CTaskListState::VIEW_ROLE_ACCOMPLICE, $userId)
			+ self::__getUserRoleCounter(CTaskListState::VIEW_ROLE_ORIGINATOR, $userId)
			+ self::__getUserRoleCounter(CTaskListState::VIEW_ROLE_AUDITOR, $userId)
		);
	}


	public static function getUserRoleCounterForUser($userId, $userRole)
	{
		return (self::__getUserRoleCounter($userRole, $userId));
	}


	public function getUserRoleCounter($userRole)
	{
		return (self::__getUserRoleCounter($userRole, $this->userId));
	}


	private static function __getUserRoleCounter($userRole, $userId)
	{
		$cnt = null;

		switch ($userRole)
		{
			case CTaskListState::VIEW_ROLE_RESPONSIBLE:
				$cnt = self::getCounterForUser($userRole, 'TOTAL', $userId);
			break;

			case CTaskListState::VIEW_ROLE_ACCOMPLICE:
				$cnt = self::getCounterForUser($userRole, 'TOTAL', $userId);
			break;

			case CTaskListState::VIEW_ROLE_ORIGINATOR:
				$cnt = self::getCounterForUser($userRole, 'TOTAL', $userId);
			break;

			case CTaskListState::VIEW_ROLE_AUDITOR:
				$cnt = self::getCounterForUser($userRole, 'TOTAL', $userId);
			break;
		}

		return ($cnt);
	}


	/**
	 * @param $userRoleId
	 * @param string $taskCategoryId
	 * @return null|string - null if not resolved, or (int) counter id if resolved
	 */
	public static function resolveCounterIdByRoleAndCategory($userRoleId, $taskCategoryId = 'TOTAL')
	{
		$counterId = null;

		if ($userRoleId == CTaskListState::VIEW_ROLE_RESPONSIBLE)
		{
			if ($taskCategoryId == CTaskListState::VIEW_TASK_CATEGORY_NEW)
				$counterId = CTaskCountersProcessor::COUNTER_TASKS_MY_NEW;
			elseif ($taskCategoryId == CTaskListState::VIEW_TASK_CATEGORY_WO_DEADLINE)
				$counterId = CTaskCountersProcessor::COUNTER_TASKS_MY_WO_DEADLINE;
			elseif ($taskCategoryId == CTaskListState::VIEW_TASK_CATEGORY_EXPIRED)
				$counterId = CTaskCountersProcessor::COUNTER_TASKS_MY_EXPIRED;
			elseif ($taskCategoryId == CTaskListState::VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES)
				$counterId = CTaskCountersProcessor::COUNTER_TASKS_MY_EXPIRED_CANDIDATES;
			elseif ($taskCategoryId === 'TOTAL')
				$counterId = CTaskCountersProcessor::COUNTER_TASKS_MY;
		}
		elseif ($userRoleId == CTaskListState::VIEW_ROLE_ACCOMPLICE)
		{
			if ($taskCategoryId == CTaskListState::VIEW_TASK_CATEGORY_NEW)
				$counterId = CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_NEW;
			elseif ($taskCategoryId == CTaskListState::VIEW_TASK_CATEGORY_EXPIRED)
				$counterId = CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_EXPIRED;
			elseif ($taskCategoryId == CTaskListState::VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES)
				$counterId = CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_EXPIRED_CANDIDATES;
			elseif ($taskCategoryId === 'TOTAL')
				$counterId = CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE;
		}
		elseif ($userRoleId == CTaskListState::VIEW_ROLE_AUDITOR)
		{
			if ($taskCategoryId == CTaskListState::VIEW_TASK_CATEGORY_EXPIRED)
				$counterId = CTaskCountersProcessor::COUNTER_TASKS_AUDITOR_EXPIRED;
			elseif ($taskCategoryId === 'TOTAL')
				$counterId = CTaskCountersProcessor::COUNTER_TASKS_AUDITOR;
		}
		elseif ($userRoleId == CTaskListState::VIEW_ROLE_ORIGINATOR)
		{
			if ($taskCategoryId == CTaskListState::VIEW_TASK_CATEGORY_EXPIRED)
				$counterId = CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_EXPIRED;
			elseif ($taskCategoryId == CTaskListState::VIEW_TASK_CATEGORY_WO_DEADLINE)
				$counterId = CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_WO_DEADLINE;
			elseif ($taskCategoryId == CTaskListState::VIEW_TASK_CATEGORY_WAIT_CTRL)
				$counterId = CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_WAIT_CTRL;
			elseif ($taskCategoryId === 'TOTAL')
				$counterId = CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR;
		}

		return ($counterId);
	}


	public function getCounter($userRoleId, $taskCategoryId)
	{
		return (self::getCounterForUser($userRoleId, $taskCategoryId, $this->userId));
	}


	private static function getCounterForUser($userRoleId, $taskCategoryId, $userId)
	{return;
		$counterId = self::resolveCounterIdByRoleAndCategory($userRoleId, $taskCategoryId);

		if ($counterId !== null)
			$rc = \CTaskCountersProcessor::getCounter($userId, $counterId);
		else
		{
			CTaskAssert::logError('[0x0de6c535] unknown counter for $userRole: ' . $userRoleId . '; $taskCategoryId: ' . $taskCategoryId);
			$rc = false;
		}

		return ($rc);
	}



	/**
	 * prevent creating through "new"
	 *
	 * @param $userId
	 */
	private function __construct($userId)
	{
		CTaskAssert::assertLaxIntegers($userId);
		CTaskAssert::assert($userId > 0);

		$this->userId = $userId;

		$cUserId = \Bitrix\Tasks\Util\User::getId();

		if (
			$cUserId
			&&
			\Bitrix\Tasks\Util\User::isAuthorized()
		)
		{
			$this->loggedInUserId = (int) $cUserId;
		}

		$this->oListState = CTaskListState::getInstance($userId);
	}
}
