<?php
namespace Bitrix\Timeman\Service\Agent;

use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Helper\EntityCodesHelper;
use Bitrix\Timeman\Helper\TimeDictionary;
use Bitrix\Timeman\Helper\UserHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;
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
		$schedule = DependencyManager::getInstance()
			->getScheduleRepository()
			->findByIdWith($scheduleId, ['SHIFTS', 'DEPARTMENT_ASSIGNMENTS',]);
		if (!$schedule || !$schedule->isFixed())
		{
			return '';
		}
		$violationRules = DependencyManager::getInstance()
			->getViolationRulesRepository()
			->findByScheduleIdEntityCode($scheduleId, $entityCode);
		if (!$violationRules)
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

	public static function notifyIfShiftMissed($shiftId, $userId, $formattedDate, $shiftEndSeconds = null)
	{
		$violationResult = DependencyManager::getInstance()
			->getViolationManager()
			->buildMissedShiftViolation($shiftId, $userId, $formattedDate);
		if (!$violationResult->isSuccess())
		{
			return '';
		}
		if ((int)$violationResult->getShift()->getWorkTimeEnd() !== (int)$shiftEndSeconds)
		{
			return '';
		}
		$plan = $violationResult->getShiftPlan();
		$toUserIds = $violationResult->getFirstViolation()
			->getNotifyUserIds(ViolationRulesTable::USERS_TO_NOTIFY_SHIFT_MISSED_START, $userId);
		if (empty($toUserIds))
		{
			return '';
		}
		$fromUser = DependencyManager::getInstance()
			->getScheduleRepository()
			->getUsersBaseQuery()
			->addSelect('PERSONAL_GENDER')
			->where('ID', $plan->getUserId())
			->exec()
			->fetch();
		if (!$fromUser)
		{
			return '';
		}
		$name = UserHelper::getInstance()->getFormattedName($fromUser);
		$gender = $fromUser['PERSONAL_GENDER'] === 'F' ? '_FEMALE' : '_MALE';
		$notifyText = Loc::getMessage(
			'TM_VIOLATION_NOTIFIER_AGENT_MISSED_SHIFT_NOTIFICATION' . $gender,
			['#USER_NAME#' => $name]
		);
		foreach ($toUserIds as $toUserId)
		{
			$notificationParams = new NotificationParameters();
			$notificationParams->messageType = "S";
			$notificationParams->fromUserId = $fromUser['ID'];
			$notificationParams->toUserId = $toUserId;
			$notificationParams->notifyType = 2;
			$notificationParams->notifyModule = 'timeman';
			$notificationParams->notifyEvent = 'shift_missed';
			$notificationParams->notifyMessage = $notifyText;
			DependencyManager::getInstance()
				->getNotifier($violationResult->getSchedule())
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
			->exec()
			->fetchAll();
		return array_combine(
			array_column($res, 'ID'),
			$res
		);
	}

}