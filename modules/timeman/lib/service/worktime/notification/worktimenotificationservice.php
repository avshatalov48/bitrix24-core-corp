<?php
namespace Bitrix\Timeman\Service\Worktime\Notification;

use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Helper\UserHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRulesTable;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;
use Bitrix\Timeman\Repository\Schedule\ScheduleRepository;
use Bitrix\Timeman\Service\Notification\NotificationParameters;
use Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolation;
use Bitrix\Timeman\TimemanUrlManager;

class WorktimeNotificationService
{
	private $scheduleRepository;
	private $userHelper;
	private $notifierFactory;
	private $users = [];
	/** @var TimemanUrlManager */
	private $urlManager;
	/** @var TimeHelper */
	private $timeHelper;

	public function __construct(
		ScheduleRepository $scheduleRepository,
		WorktimeNotifierFactory $notifierFactory,
		UserHelper $userHelper,
		TimeHelper $timeHelper,
		TimemanUrlManager $urlManager
	)
	{
		$this->timeHelper = $timeHelper;
		$this->scheduleRepository = $scheduleRepository;
		$this->userHelper = $userHelper;
		$this->notifierFactory = $notifierFactory;
		$this->urlManager = $urlManager;
	}

	/**
	 * @param WorktimeRecord $worktimeRecord
	 * @param Schedule $schedule
	 * @param WorktimeViolation[] $violations
	 */
	public function sendViolationsNotifications($schedule, $violations, $worktimeRecord = null, $paramsCallback = null)
	{
		foreach ($violations as $violation)
		{
			foreach ($this->getUserIdsToNotify($violation, $schedule) as $toUserId)
			{
				if ($paramsCallback)
				{
					$params = call_user_func($paramsCallback, $violation, $toUserId);
				}
				else
				{
					$params = $this->buildNotificationParams(
						$violation,
						$worktimeRecord,
						$toUserId
					);
				}
				$this->sendViolationNotification(
					$schedule,
					$params
				);
			}
		}
	}


	/**
	 * @param WorktimeViolation $violation
	 * @param WorktimeRecord $record
	 * @param $toUserId
	 * @return NotificationParameters
	 */
	protected function buildNotificationParams($violation, $record, $toUserId)
	{
		$notifyEvent = $this->buildNotificationTagByViolation($violation);
		$notifyMessage = $this->buildNotificationMessage($violation, $record);
		$notificationParams = $this->getDefaultNotificationParameters($record);
		$notificationParams->notifyEvent = $notifyEvent;
		$notificationParams->toUserId = $toUserId;
		$notificationParams->notifyMessage = $notifyMessage;
		return $notificationParams;
	}

	/**
	 * @param WorktimeRecord $record
	 * @return NotificationParameters
	 */
	protected function getDefaultNotificationParameters($record)
	{
		$notificationParams = new NotificationParameters();
		$notificationParams->messageType = "S";
		$notificationParams->fromUserId = $record->getUserId();
		$notificationParams->notifyType = 2;
		$notificationParams->notifyModule = 'timeman';
		return $notificationParams;
	}

	/**
	 * @param WorktimeViolation $violation
	 * @param $schedule
	 * @param NotificationParameters $notificationParams
	 */
	public function sendViolationNotification($schedule, $notificationParams)
	{
		$this->notifierFactory->getViolationNotifier($schedule)
			->sendMessage($notificationParams);
	}

	protected function getUserName($userId)
	{
		$user = $this->getUser($userId);
		if (!$user)
		{
			return '';
		}
		return $this->userHelper->getFormattedName($user);
	}

	private function getUser($userId)
	{
		if (!array_key_exists($userId, $this->users))
		{
			$user = $this->findUser($userId);
			$this->users[$userId] = $user === false ? [] : $user;
		}
		return $this->users[$userId];
	}

	private function findUser($userId)
	{
		return $this->scheduleRepository
			->getUsersBaseQuery()
			->addSelect('PERSONAL_GENDER')
			->where('ID', $userId)
			->exec()
			->fetch();
	}

	private function getUserIdsToNotify(WorktimeViolation $violation, Schedule $schedule, $fromUserId = null)
	{
		$userIds = [];
		if ($fromUserId === null)
		{
			$fromUserId = $violation->userId;
		}
		$violationRules = $violation->violationRules;
		switch ($violation->type)
		{
			case WorktimeViolation::TYPE_LATE_START:
			case WorktimeViolation::TYPE_EARLY_START:
			case WorktimeViolation::TYPE_EARLY_ENDING:
			case WorktimeViolation::TYPE_LATE_ENDING:
				$userIds = $violationRules->getNotifyUserIds(ViolationRulesTable::USERS_TO_NOTIFY_FIXED_START_END, $fromUserId);
				break;
			case WorktimeViolation::TYPE_MIN_DAY_DURATION:
				$userIds = $violationRules->getNotifyUserIds(ViolationRulesTable::USERS_TO_NOTIFY_FIXED_RECORD_TIME_PER_DAY, $fromUserId);
				break;
			case WorktimeViolation::TYPE_EDITED_BREAK_LENGTH:
			case WorktimeViolation::TYPE_EDITED_START:
			case WorktimeViolation::TYPE_EDITED_ENDING:
				$userIds = $violationRules->getNotifyUserIds(ViolationRulesTable::USERS_TO_NOTIFY_FIXED_EDIT_WORKTIME, $fromUserId);
				break;
			case WorktimeViolation::TYPE_TIME_LACK_FOR_PERIOD:
				$userIds = $violationRules->getNotifyUserIds(ViolationRulesTable::USERS_TO_NOTIFY_FIXED_TIME_FOR_PERIOD, $fromUserId);
				break;
			case WorktimeViolation::TYPE_SHIFT_LATE_START:
				$userIds = $violationRules->getNotifyUserIds(ViolationRulesTable::USERS_TO_NOTIFY_SHIFT_DELAY, $fromUserId);
				break;
			case WorktimeViolation::TYPE_MISSED_SHIFT:
				$userIds = $violationRules->getNotifyUserIds(ViolationRulesTable::USERS_TO_NOTIFY_SHIFT_MISSED_START, $fromUserId);
				break;
		}

		return $userIds;
	}

	private function buildNotificationTagByViolation(WorktimeViolation $violation)
	{
		switch ($violation->type)
		{
			case WorktimeViolation::TYPE_LATE_START:
			case WorktimeViolation::TYPE_EARLY_START:
			case WorktimeViolation::TYPE_EARLY_ENDING:
			case WorktimeViolation::TYPE_LATE_ENDING:
				return 'fixed_start_end_violation';
			case WorktimeViolation::TYPE_MIN_DAY_DURATION:
				return 'fixed_record_time_violation';
			case WorktimeViolation::TYPE_EDITED_BREAK_LENGTH:
			case WorktimeViolation::TYPE_EDITED_START:
			case WorktimeViolation::TYPE_EDITED_ENDING:
				return 'edit_record_time_violation';
			case WorktimeViolation::TYPE_SHIFT_LATE_START:
				return 'shift_late_start_violation';
		}
		return '';
	}

	/**
	 * @param $violation
	 * @param WorktimeRecord $worktimeRecord
	 * @return string
	 */
	private function buildNotificationMessage($violation, $worktimeRecord)
	{
		$notifyMessage = '';
		$gender = '_MALE';
		if (
			$this->getUser($violation->userId)
			&& $this->getUser($violation->userId)['PERSONAL_GENDER'] === 'F'
		)
		{
			$gender = '_FEMALE';
		}
		switch ($violation->type)
		{
			case WorktimeViolation::TYPE_LATE_START:
				$notifyMessage = fn (?string $languageId = null) => Loc::getMessage(
					'TM_VIOLATION_WORKTIME_MANAGER_LATE_START' . $gender,
					['#USER_NAME#' => $this->getUserName($violation->userId)],
					$languageId
				);
				break;
			case WorktimeViolation::TYPE_EARLY_START:
				$notifyMessage = fn (?string $languageId = null) => Loc::getMessage(
					'TM_VIOLATION_WORKTIME_MANAGER_EARLY_START' . $gender,
					['#USER_NAME#' => $this->getUserName($violation->userId)],
					$languageId
				);
				break;
			case WorktimeViolation::TYPE_EARLY_ENDING:
				$notifyMessage = fn (?string $languageId = null) => Loc::getMessage(
					'TM_VIOLATION_WORKTIME_MANAGER_EARLY_END' . $gender,
					['#USER_NAME#' => $this->getUserName($violation->userId)],
					$languageId
				);
				break;
			case WorktimeViolation::TYPE_LATE_ENDING:
				$notifyMessage = fn (?string $languageId = null) => Loc::getMessage(
					'TM_VIOLATION_WORKTIME_MANAGER_LATE_END' . $gender,
					['#USER_NAME#' => $this->getUserName($violation->userId)],
					$languageId
				);
				break;
			case WorktimeViolation::TYPE_MIN_DAY_DURATION:
				$notifyMessage = fn (?string $languageId = null) => Loc::getMessage(
					'TM_VIOLATION_WORKTIME_MANAGER_MIN_DAY_DURATION' . $gender,
					['#USER_NAME#' => $this->getUserName($violation->userId)],
					$languageId
				);
				break;
			case WorktimeViolation::TYPE_EDITED_ENDING:
			case WorktimeViolation::TYPE_EDITED_START:
			case WorktimeViolation::TYPE_EDITED_BREAK_LENGTH:
				$culture = \Bitrix\Main\Application::getInstance()->getContext()->getCulture();
				$dayMonthFormat = $culture->getDayMonthFormat();
				$href = $this->urlManager->getUriTo('recordReport', ['RECORD_ID' => $worktimeRecord->getId()]);
				$notifyMessage = fn (?string $languageId = null) => Loc::getMessage(
					'TM_VIOLATION_WORKTIME_MANAGER_EDIT_WITH_URL' . $gender,
					[
						'#USER_NAME#' => $this->getUserName($violation->userId),
						'#URL#' => $href,
						'#DATE#' => $this->timeHelper->formatDateTime($worktimeRecord->getRecordedStartTimestamp(), $dayMonthFormat, $languageId),
					],
					$languageId
				);
				break;
			case WorktimeViolation::TYPE_SHIFT_LATE_START:
				$notifyMessage = fn (?string $languageId = null) => Loc::getMessage(
					'TM_VIOLATION_WORKTIME_MANAGER_LATE_SHIFT_START' . $gender,
					['#USER_NAME#' => $this->getUserName($violation->userId)],
					$languageId
				);
				break;
		}

		return $notifyMessage;
	}
}