<?php
namespace Bitrix\Timeman\Service\Agent;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Timeman\Helper\EntityCodesHelper;
use Bitrix\Timeman\Helper\TimeDictionary;
use Bitrix\Timeman\Helper\UserHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;
use Bitrix\Timeman\Model\Schedule\Shift\ShiftTable;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRulesTable;
use Bitrix\Timeman\Service\DependencyManager;
use Bitrix\Timeman\Service\Notification\NotificationParameters;
use Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolationParams;
use Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolationResult;

Loc::loadMessages(__FILE__);

class ViolationNotifierAgent
{
	public static function notifyIfPeriodTimeLack($scheduleId, $fromDateTimeFormatted, $toDateTimeFormatted, $entityCode = null)
	{
		if ($entityCode === null)
		{
			$entityCode = EntityCodesHelper::getAllUsersCode();
		}
		$violationRules = DependencyManager::getInstance()
			->getViolationRulesRepository()
			->findByScheduleIdEntityCode($scheduleId, $entityCode);
		if (!$violationRules)
		{
			return '';
		}
		$violationRules->setPeriodTimeLackAgentId(0);
		DependencyManager::getInstance()
			->getViolationRulesRepository()
			->save($violationRules);

		$schedule = DependencyManager::getInstance()
			->getScheduleRepository()
			->findByIdWith($scheduleId, ['SHIFTS', 'DEPARTMENT_ASSIGNMENTS', 'USER_ASSIGNMENTS']);
		if (!$schedule || !$schedule->isFixed())
		{
			return '';
		}
		if (empty($violationRules->getNotifyUsersSymbolic(ViolationRulesTable::USERS_TO_NOTIFY_FIXED_TIME_FOR_PERIOD)))
		{
			return '';
		}

		$fromDateTime = \DateTime::createFromFormat(TimeDictionary::DATE_TIME_FORMAT, $fromDateTimeFormatted);
		$expectedToDateTime = static::buildExpectedPeriodEndDate($fromDateTime, $schedule);
		if ($expectedToDateTime->format(TimeDictionary::DATE_TIME_FORMAT) !== $toDateTimeFormatted)
		{
			return '';
		}

		$violationResult = DependencyManager::getInstance()
			->getViolationManager()
			->buildPeriodTimeLackViolation(
				(new WorktimeViolationParams())
					->setSchedule($schedule)
					->setViolationRules($violationRules),
				$fromDateTime,
				$expectedToDateTime
			);
		if (!$violationResult->isSuccess())
		{
			return '';
		}

		$users = static::findUsers($violationResult);

		DependencyManager::getInstance()
			->getWorktimeNotificationService()
			->sendViolationsNotifications(
				$schedule,
				$violationResult->getViolations(),
				null,
				function ($violation, $toUserId) use ($users) {
					$notificationParams = new NotificationParameters();
					$notificationParams->messageType = "S";
					$notificationParams->fromUserId = $violation->userId;
					$notificationParams->toUserId = $toUserId;
					$notificationParams->notifyType = 2;
					$notificationParams->notifyModule = 'timeman';
					$notificationParams->notifyEvent = 'period_time_lack';
					$gender = '_MALE';
					$name = '';
					if ($users[$violation->userId])
					{
						if ($users[$violation->userId]['PERSONAL_GENDER'] === 'F')
						{
							$gender = '_FEMALE';
						}
						$name = UserHelper::getInstance()->getFormattedName($users[$violation->userId]);
					}
					$notificationParams->notifyMessage = Loc::getMessage(
						'TM_VIOLATION_NOTIFIER_AGENT_PERIOD_TIME_LACK' . $gender,
						['#USER_NAME#' => $name]
					);
					return $notificationParams;
				}
			);

		DependencyManager::getInstance()
			->getWorktimeAgentManager()
			->createTimeLackForPeriodChecking($schedule, $expectedToDateTime, $violationRules);
		return '';
	}

	public static function notifyIfShiftMissed($shiftPlanId)
	{
		$shiftPlan = DependencyManager::getInstance()->getShiftPlanRepository()->findActiveById(
			$shiftPlanId,
			['*', 'SHIFT'],
			Query::filter()->where('SHIFT.DELETED', ShiftTable::DELETED_NO)
		);
		if (!$shiftPlan)
		{
			return '';
		}
		$shiftPlan->setMissedShiftAgentId(0);
		DependencyManager::getInstance()->getShiftPlanRepository()->save($shiftPlan);

		$schedule = DependencyManager::getInstance()->getScheduleRepository()
			->findActiveByShiftId($shiftPlan->getShiftId(), ['*']);
		if (!$schedule || !$schedule->isShifted())
		{
			return '';
		}

		$utcStart = $shiftPlan->obtainShift()->buildUtcStartByShiftplan($shiftPlan);
		$minStart = clone $utcStart;
		if ($schedule->getAllowedMaxShiftStartOffset() > 0)
		{
			$minStart->sub(new \DateInterval('PT' . $schedule->getAllowedMaxShiftStartOffset() . 'S'));
		}
		$maxStart = clone $utcStart;
		$maxStart->add(new \DateInterval('PT' . $shiftPlan->obtainShift()->getDuration() . 'S'));

		$record = DependencyManager::getInstance()->getWorktimeRepository()->findByUserShiftSchedule(
			$shiftPlan->getUserId(),
			$shiftPlan->obtainShift()->getId(),
			$schedule->getId(),
			['ID', 'RECORDED_START_TIMESTAMP'],
			Query::filter()
				->where('RECORDED_START_TIMESTAMP', '>=', $minStart->getTimestamp())
				->where('RECORDED_START_TIMESTAMP', '<=', $maxStart->getTimestamp())
		);
		if ($record)
		{
			return '';
		}

		$userCodes = EntityCodesHelper::buildDepartmentCodes(
			DependencyManager::getInstance()->getDepartmentRepository()->getAllUserDepartmentIds($shiftPlan->getUserId())
		);
		$userCodes[] = EntityCodesHelper::getAllUsersCode();
		$userCodes[] = EntityCodesHelper::buildUserCode($shiftPlan->getUserId());

		$violationRulesList = DependencyManager::getInstance()->getViolationRulesRepository()
			->findAllByScheduleId(
				$schedule->getId(),
				[
					'ID',
					'ENTITY_CODE',
					'MISSED_SHIFT_START',
					'USERS_TO_NOTIFY',
				],
				Query::filter()
					->where('MISSED_SHIFT_START', ViolationRulesTable::MISSED_SHIFT_IS_TRACKED)
					->whereIn('ENTITY_CODE', $userCodes)
			);
		if ($violationRulesList->count() === 0)
		{
			return '';
		}
		$params = (new WorktimeViolationParams())
			->setShift($shiftPlan->obtainShift())
			->setSchedule($schedule)
			->setRecord($record)
			->setShiftPlan($shiftPlan);
		$toUserIds = [];
		$manager = DependencyManager::getInstance()
			->getViolationManager();
		foreach ($violationRulesList as $violationRules)
		{
			$params->setViolationRules($violationRules);
			$violationResult = $manager->buildMissedShiftViolation($params);
			if (empty($violationResult->getViolations()))
			{
				continue;
			}
			$toUserIds = array_merge(
				$toUserIds,
				$violationRules->getNotifyUserIds(ViolationRulesTable::USERS_TO_NOTIFY_SHIFT_MISSED_START, $shiftPlan->getUserId())
			);
		}

		$toUserIds = array_unique($toUserIds);
		if (empty($toUserIds))
		{
			return '';
		}
		$fromUser = DependencyManager::getInstance()
			->getScheduleRepository()
			->getUsersBaseQuery()
			->addSelect('PERSONAL_GENDER')
			->where('ID', $shiftPlan->getUserId())
			->where('ACTIVE', 'Y')
			->exec()
			->fetch()
		;
		if (!$fromUser)
		{
			return '';
		}
		$name = UserHelper::getInstance()->getFormattedName($fromUser);
		$gender = $fromUser['PERSONAL_GENDER'] === 'F' ? '_FEMALE' : '_MALE';
		$notifyText = fn (?string $languageId = null) => Loc::getMessage(
			'TM_VIOLATION_NOTIFIER_AGENT_MISSED_SHIFT_NOTIFICATION' . $gender,
			['#USER_NAME#' => $name],
			$languageId
		);
		foreach ($toUserIds as $toUserId)
		{
			$notificationParams = new NotificationParameters();
			$notificationParams->messageType = 'S';
			$notificationParams->fromUserId = $fromUser['ID'];
			$notificationParams->toUserId = $toUserId;
			$notificationParams->notifyType = 2;
			$notificationParams->notifyModule = 'timeman';
			$notificationParams->notifyEvent = 'shift_missed';
			$notificationParams->notifyMessage = $notifyText;
			DependencyManager::getInstance()
				->getNotifier($schedule)
				->sendMessage($notificationParams);
		}

		return '';
	}

	/**
	 * @param \DateTime $fromDateTime
	 * @param Schedule $schedule
	 * @return mixed
	 * @throws \Exception
	 */
	private static function buildExpectedPeriodEndDate($fromDateTime, $schedule)
	{
		$expectedToDateTime = clone $fromDateTime;
		switch ($schedule->getReportPeriod())
		{
			case ScheduleTable::REPORT_PERIOD_WEEK:
				$expectedToDateTime->add(new \DateInterval('P6D'));
				break;
			case ScheduleTable::REPORT_PERIOD_TWO_WEEKS:
				$expectedToDateTime->add(new \DateInterval('P13D'));
				break;
			case ScheduleTable::REPORT_PERIOD_MONTH:
				$expectedToDateTime->modify('last day of');
				break;
			case ScheduleTable::REPORT_PERIOD_QUARTER:
				$expectedToDateTime->add(new \DateInterval('P3M'));
				$expectedToDateTime->sub(new \DateInterval('P1D'));
				break;
			default:
				break;
		}
		$expectedToDateTime->setTime(23, 59, 59);
		return $expectedToDateTime;
	}

	/**
	 * @param WorktimeViolationResult $violationResult
	 */
	private static function findUsers($violationResult)
	{
		$userIds = [];
		foreach ($violationResult->getViolations() as $violation)
		{
			$userIds[] = $violation->userId;
		}
		if (empty($userIds))
		{
			return [];
		}
		$res = DependencyManager::getInstance()
			->getScheduleRepository()
			->getUsersBaseQuery()
			->addSelect('PERSONAL_GENDER')
			->whereIn('ID', $userIds)
			->where('ACTIVE', 'Y')
			->exec()
			->fetchAll();
		return array_combine(
			array_column($res, 'ID'),
			$res
		);
	}

}