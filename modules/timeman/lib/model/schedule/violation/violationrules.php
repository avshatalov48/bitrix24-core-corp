<?php
namespace Bitrix\Timeman\Model\Schedule\Violation;

use Bitrix\Timeman\Form\Schedule\ViolationForm;
use Bitrix\Timeman\Helper\EntityCodesHelper;

class ViolationRules extends EO_ViolationRules
{
	/**
	 * @param int $scheduleId
	 * @param ViolationForm $violationForm
	 * @param null $entityCode
	 * @return ViolationRules
	 */
	public static function create($scheduleId, $violationForm = null, $entityCode = null)
	{
		if ($entityCode === null)
		{
			$entityCode = EntityCodesHelper::getAllUsersCode();
		}
		$violationRules = new static();
		$violationRules->setScheduleId($scheduleId);
		$violationRules->setEntityCode($entityCode);
		if ($violationForm)
		{
			$violationRules->setMinExactEnd($violationForm->minExactEnd);
			$violationRules->setMaxExactStart($violationForm->maxExactStart);
			$violationRules->setMinOffsetEnd($violationForm->minOffsetEnd);
			$violationRules->setMaxOffsetStart($violationForm->maxOffsetStart);
			$violationRules->setRelativeStartFrom($violationForm->relativeStartFrom);
			$violationRules->setRelativeStartTo($violationForm->relativeStartTo);
			$violationRules->setRelativeEndFrom($violationForm->relativeEndFrom);
			$violationRules->setRelativeEndTo($violationForm->relativeEndTo);
			$violationRules->setMinDayDuration($violationForm->minDayDuration);
			$violationRules->setMaxAllowedToEditWorkTime($violationForm->maxAllowedToEditWorkTime);
			$violationRules->setMaxWorkTimeLackForPeriod($violationForm->maxWorkTimeLackForPeriod);
			$violationRules->setMaxShiftStartDelay($violationForm->maxShiftStartDelay);
			$violationRules->setMissedShiftStart($violationForm->missedShiftStart);
			$violationRules->setUsersToNotifyByForm($violationForm, $violationRules);
		}

		return $violationRules;
	}

	public static function isViolationConfigured($value)
	{
		return static::isValueConfigured($value);
	}

	private static function isValueConfigured($value)
	{
		return $value !== null && $value !== -1;
	}

	public function edit(ViolationForm $violationForm)
	{
		$this->setMinExactEnd($violationForm->minExactEnd);
		$this->setMaxExactStart($violationForm->maxExactStart);
		$this->setMinOffsetEnd($violationForm->minOffsetEnd);
		$this->setMaxOffsetStart($violationForm->maxOffsetStart);
		$this->setRelativeStartFrom($violationForm->relativeStartFrom);
		$this->setRelativeStartTo($violationForm->relativeStartTo);
		$this->setRelativeEndFrom($violationForm->relativeEndFrom);
		$this->setRelativeEndTo($violationForm->relativeEndTo);
		$this->setMinDayDuration($violationForm->minDayDuration);
		$this->setMaxAllowedToEditWorkTime($violationForm->maxAllowedToEditWorkTime);
		$this->setMaxWorkTimeLackForPeriod($violationForm->maxWorkTimeLackForPeriod);
		$this->setMaxShiftStartDelay($violationForm->maxShiftStartDelay);
		$this->setMissedShiftStart($violationForm->missedShiftStart);

		$this->setUsersToNotifyByForm($violationForm, $this);
	}

	public function getNotifyUsersSymbolic($type)
	{
		return isset($this->getUsersToNotify()[$type]) ? $this->getUsersToNotify()[$type] : [];
	}

	public function getNotifyUserIds($groupName, $fromUserId = null)
	{
		$fromUserId = (int)$fromUserId;
		$users = $this->getNotifyUsersSymbolic($groupName);
		$userIds = [];
		foreach ($users as $userSymbol)
		{
			if (preg_match('#U[0-9]+#', $userSymbol) === 1)
			{
				$userIds[] = (int)substr($userSymbol, 1);
			}
		}
		if ($fromUserId && $this->needToNotifyManager($groupName))
		{
			return array_filter(
				array_unique(
					array_merge(
						$userIds,
						array_map('intval', \CTimeMan::getUserManagers($fromUserId, false))
					)
				),
				function ($value) use ($fromUserId) {
					return $value != $fromUserId;
				}
			);
		}
		return $userIds;
	}

	public function needToNotifyManager($groupName)
	{
		return in_array(ViolationRulesTable::USERS_TO_NOTIFY_USER_MANAGER, $this->getNotifyUsersSymbolic($groupName), true);
	}

	private function setUsersToNotifyByForm(ViolationForm $violationForm, ViolationRules $violationRules)
	{
		$newUsers = [];
		if (!empty($violationForm->startEndNotifyUsers))
		{
			$newUsers = array_merge($newUsers, [
				ViolationRulesTable::USERS_TO_NOTIFY_FIXED_START_END => $violationForm->startEndNotifyUsers,
			]);
		}
		if (!empty($violationForm->hoursPerDayNotifyUsers))
		{
			$newUsers = array_merge($newUsers, [
				ViolationRulesTable::USERS_TO_NOTIFY_FIXED_RECORD_TIME_PER_DAY => $violationForm->hoursPerDayNotifyUsers,
			]);
		}
		if (!empty($violationForm->editWorktimeNotifyUsers))
		{
			$newUsers = array_merge($newUsers, [
				ViolationRulesTable::USERS_TO_NOTIFY_FIXED_EDIT_WORKTIME => $violationForm->editWorktimeNotifyUsers,
			]);
		}
		if (!empty($violationForm->hoursPerPeriodNotifyUsers))
		{
			$newUsers = array_merge($newUsers, [
				ViolationRulesTable::USERS_TO_NOTIFY_FIXED_TIME_FOR_PERIOD => $violationForm->hoursPerPeriodNotifyUsers,
			]);
		}
		if (!empty($violationForm->shiftTimeNotifyUsers))
		{
			$newUsers = array_merge($newUsers, [
				ViolationRulesTable::USERS_TO_NOTIFY_SHIFT_DELAY => $violationForm->shiftTimeNotifyUsers,
			]);
		}
		if (!empty($violationForm->shiftCheckNotifyUsers))
		{
			$newUsers = array_merge($newUsers, [
				ViolationRulesTable::USERS_TO_NOTIFY_SHIFT_MISSED_START => $violationForm->shiftCheckNotifyUsers,
			]);
		}
		foreach ($newUsers as $key => $newUsersIds)
		{
			if (array_diff($newUsersIds, (array)$violationRules->getUsersToNotify()[$key])
				||
				array_diff((array)$violationRules->getUsersToNotify()[$key], $newUsersIds))
			{
				$this->setUsersToNotify($newUsers);
				break;
			}
		}
	}

	public function addToNotificationUserIds($ids)
	{
		if (!$this->getUsersToNotify())
		{
			$this->setUsersToNotify([]);
		}
		$this->setUsersToNotify(array_merge($this->getUsersToNotify(), $ids));
	}

	public function isPeriodWorkTimeLackControlEnabled()
	{
		return $this->getMaxWorkTimeLackForPeriod() >= 0;
	}

	public function isMissedShiftsControlEnabled()
	{
		return $this->getMissedShiftStart() === 1;
	}

	public function isForAllUsers()
	{
		return $this->getEntityCode() === ViolationRulesTable::ENTITY_CODE_ALL_SCHEDULE_USERS;
	}
}