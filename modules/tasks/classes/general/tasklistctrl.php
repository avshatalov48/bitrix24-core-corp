<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */

use \Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Task\MetaStatus;
use Bitrix\Tasks\Internals\Task\Status;

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
				$arFilter['REAL_STATUS'] = [
					Status::COMPLETED,
					Status::SUPPOSEDLY_COMPLETED,
					Status::DECLINED,
				];
				break;

			case CTaskListState::VIEW_TASK_CATEGORY_WAIT_CTRL:
				$arFilter['REAL_STATUS'] = Status::SUPPOSEDLY_COMPLETED;
			break;

			case CTaskListState::VIEW_TASK_CATEGORY_NEW:
				$arFilter['VIEWED'] = 0;
				$arFilter['VIEWED_BY'] = $userId;
			break;

			case CTaskListState::VIEW_TASK_CATEGORY_ALL:
			break;

			case CTaskListState::VIEW_TASK_CATEGORY_IN_PROGRESS:
				$arFilter['REAL_STATUS'] = array(
					Status::NEW,
					Status::PENDING,
					Status::IN_PROGRESS,
				);
			break;

			case CTaskListState::VIEW_TASK_CATEGORY_EXPIRED:
				$arFilter['STATUS'] = MetaStatus::EXPIRED;
			break;

			case CTaskListState::VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES:
				$arFilter['>=DEADLINE'] = Counter\Deadline::getExpiredTime();
				$arFilter['<DEADLINE'] = Counter\Deadline::getExpiredSoonTime();
				$arFilter['!REAL_STATUS'] = [
					Status::SUPPOSEDLY_COMPLETED,
					Status::COMPLETED,
					Status::DECLINED,
				];
				break;

			case CTaskListState::VIEW_TASK_CATEGORY_DEFERRED:
				$arFilter['REAL_STATUS'] = Status::DEFERRED;
			break;

			case CTaskListState::VIEW_TASK_CATEGORY_ATTENTION:
				switch ($userRoleId)
				{
					case CTaskListState::VIEW_ROLE_RESPONSIBLE:
					case CTaskListState::VIEW_ROLE_ACCOMPLICE:
						// selects not viewed tasks, expired and to be expired soon
						$arFilter['!REAL_STATUS'] = [
							Status::SUPPOSEDLY_COMPLETED,
							Status::COMPLETED,
							Status::DECLINED,
						];

						$arFilter['::SUBFILTER-' . (++$subfilterIndex)] = array(
							'::LOGIC'   => 'OR',
							'VIEWED'    => 0,
							'<DEADLINE' => Counter\Deadline::getExpiredSoonTime()
							// to be expired soon, it's includes already expired tasks too
						);
					break;

					case CTaskListState::VIEW_ROLE_AUDITOR:
					case CTaskListState::VIEW_ROLE_ORIGINATOR:
						// selects only expired tasks
						$arFilter['STATUS'] = MetaStatus::EXPIRED;
					break;
				}
			break;

			case CTaskListState::VIEW_TASK_CATEGORY_WO_DEADLINE:
				$arFilter['!REAL_STATUS'] = [
					Status::DECLINED,
					Status::SUPPOSEDLY_COMPLETED,
					Status::COMPLETED,
				];

				$arFilter['DEADLINE'] = '';

				// if($userRoleId == CTaskListState::VIEW_ROLE_RESPONSIBLE)
				// {
				// 	$arFilter['!CREATED_BY'] = $userId;
				// }
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

		$arFilter['CHECK_PERMISSIONS'] = 'Y';

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
		return null;
	}


	/**
	 * @deprecated since tasks 20.6.400
	 */
	public static function resolveCounterIdByRoleAndCategory($userRoleId, $taskCategoryId = 'TOTAL')
	{
		$counterId = null;

		return ($counterId);
	}

	/**
	 * @deprecated since tasks 20.6.400
	 */
	public function getCounter($userRoleId, $taskCategoryId)
	{
		return null;
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