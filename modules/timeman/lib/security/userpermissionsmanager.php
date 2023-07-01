<?php
namespace Bitrix\Timeman\Security;

use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Helper\EntityCodesHelper;
use CIntranetUtils;
use CTimeMan;

class UserPermissionsManager
{
	const OP_READ_SCHEDULES_ALL = 'tm_read_schedules_all';
	const OP_UPDATE_SCHEDULES_ALL = 'tm_update_schedules_all';

	const OP_READ_SHIFT_PLANS_ALL = 'tm_read_shift_plans_all';
	const OP_UPDATE_SHIFT_PLANS_ALL = 'tm_update_shift_plans_all';

	const OP_UPDATE_SETTINGS = 'tm_settings';

	const OP_MANAGE_WORKTIME = 'tm_manage';
	const OP_MANAGE_WORKTIME_ALL = 'tm_manage_all';

	const OP_UPDATE_WORKTIME_ALL = 'tm_write';
	const OP_UPDATE_WORKTIME_SUBORDINATE = 'tm_write_subordinate';

	const OP_READ_WORKTIME_ALL = 'tm_read';
	const OP_READ_WORKTIME_SUBORDINATE = 'tm_read_subordinate';

	const ENTITY_SCHEDULE = 'SCHEDULE';
	const ACTION_READ = 'READ';
	const ACTION_UPDATE = 'UPDATE';
	const ENTITY_SHIFT_PLAN = 'SHIFT_PLAN';
	const ENTITY_WORKTIME_RECORD = 'WORKTIME_RECORD';
	private static $managers = [];

	/** @var IUserOperationChecker */
	private $userOperationChecker;
	private $currentUserId;

	public function __construct(IUserOperationChecker $userOperationChecker, $userId)
	{
		$this->userOperationChecker = $userOperationChecker;
		$this->currentUserId = (int)$userId;
	}

	public static function getOperationsNames()
	{
		$reflection = new \ReflectionClass(__CLASS__);
		return array_filter($reflection->getConstants(), function ($element) {
			return strncmp('OP_', $element, 3) === 0;
		}, ARRAY_FILTER_USE_KEY);
	}

	/**
	 * @param \CUser $user
	 * @return mixed
	 */
	public static function getInstanceByUser($user): UserPermissionsManager
	{
		if (!$user || !is_object($user) || !($user instanceof \CUser))
		{
			return new static(new AccessDeniedOperationChecker(), null);
		}
		if (!isset(static::$managers[$user->getId()]))
		{
			static::$managers[$user->getId()] = new static(new UserOperationChecker($user), $user->getId());
		}
		return static::$managers[$user->getId()];
	}

	public function canReadSchedule($scheduleId)
	{
		return $this->userOperationChecker->canDoOperation(static::OP_READ_SCHEDULES_ALL);
	}

	public function canReadShiftPlan($scheduleId)
	{
		return $this->userOperationChecker->canDoOperation(static::OP_READ_SHIFT_PLANS_ALL);
	}

	public function canUpdateShiftPlan($scheduleId)
	{
		return $this->userOperationChecker->canDoOperation(static::OP_UPDATE_SHIFT_PLANS_ALL);
	}

	public function canUpdateShiftPlans()
	{
		return $this->userOperationChecker->canDoOperation(static::OP_UPDATE_SHIFT_PLANS_ALL);
	}

	public function canManageWorktime()
	{
		return $this->userOperationChecker->canDoOperation(static::OP_MANAGE_WORKTIME);
	}

	public function canManageWorktimeAll()
	{
		return $this->userOperationChecker->canDoOperation(static::OP_MANAGE_WORKTIME_ALL);
	}

	public function canUpdateWorktimeAll()
	{
		$userIds = $this->getUserIdsAccessibleToWrite();

		return (
			in_array('*', $userIds, true)
			|| $this->userOperationChecker->canDoOperation(static::OP_UPDATE_WORKTIME_ALL)
		);
	}

	public function canUpdateWorktime($recordOwnerUserId)
	{
		if ($this->canUpdateWorktimeAll())
		{
			return true;
		}

		$userIds = $this->getUserIdsAccessibleToWrite();

		return count($userIds) && in_array((int)$recordOwnerUserId, $userIds, true);
	}

	/**
	 * @param $recordOwnerUserId
	 * @return bool
	 */
	public function canApproveWorktime($recordOwnerUserId)
	{
		return $this->canUpdateWorktime($recordOwnerUserId);
	}

	public function canCreateSchedule()
	{
		return $this->userOperationChecker->canDoOperation(static::OP_UPDATE_SCHEDULES_ALL);
	}

	public function canUpdateSchedule($scheduleId)
	{
		return $this->userOperationChecker->canDoOperation(static::OP_UPDATE_SCHEDULES_ALL);
	}

	public function canUpdateSchedules()
	{
		return $this->userOperationChecker->canDoOperation(static::OP_UPDATE_SCHEDULES_ALL);
	}

	public function canDeleteSchedules()
	{
		return $this->userOperationChecker->canDoOperation(static::OP_UPDATE_SCHEDULES_ALL);
	}

	public function canDeleteSchedule($scheduleId)
	{
		return $this->userOperationChecker->canDoOperation(static::OP_UPDATE_SCHEDULES_ALL);
	}

	public function canReadSchedules()
	{
		return $this->userOperationChecker->canDoOperation(static::OP_READ_SCHEDULES_ALL);
	}

	public function canReadWorktimeAll()
	{
		$userIds = $this->getUserIdsAccessibleToRead();

		return (
			in_array('*', $userIds, true)
			|| $this->userOperationChecker->canDoOperation(static::OP_READ_WORKTIME_ALL)
		);
	}

	public function canReadWorktime($recordOwnerUserId)
	{
		if ($this->canReadWorktimeAll())
		{
			return true;
		}
		$userIds = $this->getUserIdsAccessibleToRead();
		return count($userIds) && in_array((int)$recordOwnerUserId, $userIds, true);
	}

	public function canReadWorktimeSubordinate()
	{
		return $this->userOperationChecker->canDoOperation(static::OP_READ_WORKTIME_SUBORDINATE);
	}

	public function canUpdateWorktimeSubordinate()
	{
		return $this->userOperationChecker->canDoOperation(static::OP_UPDATE_WORKTIME_SUBORDINATE);
	}

	public function canUpdateSettings()
	{
		return $this->userOperationChecker->canDoOperation(static::OP_UPDATE_SETTINGS);
	}

	public function canCreateViolationRules($entityCode)
	{
		if ($this->userOperationChecker->canDoAnyOperation())
		{
			return true;
		}
		if (EntityCodesHelper::isUser($entityCode))
		{
			$userId = EntityCodesHelper::getUserId($entityCode);
			return $this->currentUserId !== $userId && $this->canUpdateWorktime($userId);
		}
		elseif (EntityCodesHelper::isDepartment($entityCode))
		{
			if (!\Bitrix\Main\Loader::includeModule('intranet'))
			{
				return false;
			}
			$departmentId = EntityCodesHelper::getDepartmentId($entityCode);
			$subordinateDepartments = array_map('intval', CIntranetUtils::getSubordinateDepartments($this->currentUserId, true));
			return in_array($departmentId, $subordinateDepartments, true) || $this->canUpdateWorktimeAll();
		}
		return false;
	}

	public function canUpdateViolationRules($entityCode)
	{
		return $this->canCreateViolationRules($entityCode);
	}

	public function getEntityTitle($entity)
	{
		Loc::loadLanguageFile(__FILE__);
		$map = [
			static::ENTITY_SCHEDULE => Loc::getMessage('TIMEMAN_USERPERMISSIONS_ENTITY_SCHEDULE'),
			static::ENTITY_SHIFT_PLAN => Loc::getMessage('TIMEMAN_USERPERMISSIONS_ENTITY_SHIFT_PLAN'),
			static::ENTITY_WORKTIME_RECORD => Loc::getMessage('TIMEMAN_USERPERMISSIONS_ENTITY_WORKTIME_RECORD'),
		];
		return isset($map[$entity]) ? $map[$entity] : '';
	}

	public function getActionTitles()
	{
		Loc::loadLanguageFile(__FILE__);
		return [
			static::ACTION_UPDATE => Loc::getMessage('TIMEMAN_USERPERMISSIONS_ACTION_UPDATE'),
			static::ACTION_READ => Loc::getMessage('TIMEMAN_USERPERMISSIONS_ACTION_READ'),
		];
	}

	public function getActionTitle($action)
	{
		$map = $this->getActionTitles();
		return isset($map[$action]) ? $map[$action] : '';
	}

	public function getMap()
	{
		return [
			static::ENTITY_SCHEDULE => [
				static::ACTION_READ => [
					'',
					static::OP_READ_SCHEDULES_ALL,
				],
				static::ACTION_UPDATE => [
					'',
					static::OP_UPDATE_SCHEDULES_ALL,
				],
			],
			static::ENTITY_SHIFT_PLAN => [
				static::ACTION_READ => [
					'',
					static::OP_READ_SHIFT_PLANS_ALL,
				],
				static::ACTION_UPDATE => [
					'',
					static::OP_UPDATE_SHIFT_PLANS_ALL,
				],
			],
			static::ENTITY_WORKTIME_RECORD => [
				static::ACTION_READ => [
					'',
					static::OP_READ_WORKTIME_SUBORDINATE,
					static::OP_READ_WORKTIME_ALL,
				],
				static::ACTION_UPDATE => [
					'',
					static::OP_UPDATE_WORKTIME_SUBORDINATE,
					static::OP_UPDATE_WORKTIME_ALL,
				],
			],
		];
	}

	private function getAccessUserIds()
	{
		return CTimeMan::getAccess();
	}

	private function getUserIdsAccessibleToWrite()
	{
		$accessData = $this->getAccessUserIds();
		if (count($accessData['WRITE']) > 0)
		{
			return $this->filterUserIdsOnly($accessData['WRITE']);
		}
		return [];
	}

	public function getUserCodesAccessibleToRead()
	{
		$accessData = $this->getAccessUserIds();
		if (count($accessData['READ']) > 0)
		{
			$result = [];
			if (in_array('*', $accessData['READ'], true))
			{
				$result[] = EntityCodesHelper::getAllUsersCode();
			}
			$result = array_merge(
				$result,
				EntityCodesHelper::buildUserCodes($this->filterUserIdsOnly($accessData['READ']))
			);
			return array_filter($result);
		}
		return [];
	}

	public function getUserIdsAccessibleToRead()
	{
		$accessData = $this->getAccessUserIds();
		if (count($accessData['READ']) > 0)
		{
			return $this->filterUserIdsOnly($accessData['READ']);
		}
		return [];
	}

	private function filterUserIdsOnly($values)
	{
		return array_map('intval', array_filter($values, function ($elem) {
			return $elem !== '*';
		}));
	}
}