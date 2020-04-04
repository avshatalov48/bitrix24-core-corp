<?php
namespace Bitrix\Timeman\Components\ReportEntry;

use \Bitrix\Main;
use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use \Bitrix\Timeman;
use Bitrix\Timeman\Form\Worktime\WorktimeEventForm;
use Bitrix\Timeman\Form\Worktime\WorktimeRecordForm;
use Bitrix\Timeman\Helper\DateTimeHelper;
use Bitrix\Timeman\Helper\EntityCodesHelper;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Helper\UserHelper;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventTable;
use Bitrix\Timeman\Repository\Worktime\WorktimeRepository;
use Bitrix\Timeman\Service\DependencyManager;
use Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolation;
use Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolationParams;
use CBXFeatures;
use CTimeManReportDaily;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__FILE__);

if (!Main\Loader::includeModule('timeman'))
{
	ShowError(Loc::getMessage('TIMEMAN_MODULE_NOT_INSTALLED'));
	return;
}

class WorktimeRecordReportComponent extends Timeman\Component\BaseComponent
{
	/** @var WorktimeRepository */
	private $worktimeRepository;
	/** @var Timeman\Security\UserPermissionsManager */
	private $userPermissionsManager;
	private $currentUserId;

	public function __construct($component = null)
	{
		parent::__construct($component);

		$this->worktimeRepository = new WorktimeRepository();
		global $USER;
		$this->currentUserId = $USER->GetID();
		$this->userPermissionsManager = DependencyManager::getInstance()->getUserPermissionsManager($USER);
	}

	public function onPrepareComponentParams($arParams)
	{
		$this->arResult['RECORD_ID'] = $this->getFromParamsOrRequest($arParams, 'RECORD_ID', 'int');
		return $arParams;
	}

	public function executeComponent()
	{
		$this->getApplication()->setTitle(htmlspecialcharsbx(Loc::getMessage('JS_CORE_TMR_TITLE')));
		$record = null;
		$this->arResult['URL_TEMPLATES_PROFILE_VIEW'] = UserHelper::getInstance()->getProfilePath('#USER_ID#');
		if ($this->arResult['RECORD_ID'])
		{
			$record = $this->worktimeRepository->findByIdWith($this->arResult['RECORD_ID'], ['USER', 'WORKTIME_EVENTS', 'SCHEDULE', 'SCHEDULE.SCHEDULE_VIOLATION_RULES', 'SCHEDULE.SHIFTS', 'REPORTS']);
			if (!$record)
			{
				return $this->showError(Loc::getMessage('TM_RECORD_NOT_FOUND'));
			}
		}
		if (!$this->userPermissionsManager->canReadWorktime($record->getUserId()))
		{
			return $this->showError(Loc::getMessage('TM_RECORD_READ_ACCESS_DENIED'));
		}
		$this->arResult['canUpdateWorktime'] = $this->userPermissionsManager->canUpdateWorktime($record->getUserId());

		$recordForm = new WorktimeRecordForm($record);
		$schedule = DependencyManager::getInstance()->getScheduleRepository()->findByIdWith($record->getScheduleId(), ['SHIFTS']);
		if ($schedule && $schedule->obtainShiftByPrimary($record->getShiftId()))
		{
			$this->arResult['VIOLATIONS'] = DependencyManager::getInstance()
				->getViolationManager()
				->buildViolations(
					(new WorktimeViolationParams())
						->setShift($schedule->obtainShiftByPrimary($record->getShiftId()))
						->setSchedule($schedule)
						->setViolationRules($this->getViolationRules($record))
						->setRecord($record)
				);
		}

		$this->fillTemplateParams($record, $recordForm);

		$this->arResult['record'] = $record;
		$this->arResult['recordForm'] = $recordForm;
		$this->arResult['user'] = $recordForm->getUser()->collectValues();

		$this->arResult['COMMENT_FORUM_ID'] = (int)\CTimeManNotify::GetForum();
		$this->arResult['COMMENT_ENTITY_TYPE'] = SONET_TIMEMAN_ENTRY_ENTITY;

		$this->makeRelatedRecordsLinks($record);

		$this->includeComponentTemplate();
	}

	private function showError($errorMessage)
	{
		$this->addError($errorMessage);
		$this->includeComponentTemplate('error');
	}

	private function addError($errorMessage)
	{
		$this->arResult['errorMessages'][] = htmlspecialcharsbx($errorMessage);
	}

	private function findUserManagers($getManagerIds)
	{
		return Timeman\Service\DependencyManager::getInstance()
			->getScheduleRepository()
			->getUsersBaseQuery()
			->whereIn('ID', array_merge([-1], $getManagerIds))
			->exec()
			->fetchAll();
	}

	/**
	 * @param Timeman\Model\Worktime\Record\WorktimeRecord $record
	 * @param WorktimeRecordForm $recordForm
	 */
	private function fillTemplateParams($record, $recordForm)
	{
		$dateTimeHelper = new Timeman\Helper\DateTimeHelper();
		$userHelper = UserHelper::getInstance();
		$employee = $recordForm->getUser();
		$userManagers = $this->findUserManagers($userHelper->getManagerIds($employee->getId())) ?: [$employee];
		/** @var TimeHelper $timeHelper */
		$timeHelper = TimeHelper::getInstance();
		$this->arResult['RECORD_ID'] = $record->getId();
		$recordedStartDate = $timeHelper->createUserDateTimeFromFormat('U', $recordForm->recordedStartTimestamp, $this->currentUserId);
		$this->arResult['REPORT_FORMATTED_DATE'] = $dateTimeHelper->formatDate('d F Y', $recordedStartDate);

		$userUtcOffset = $timeHelper->getUserUtcOffset($this->currentUserId);
		$this->arResult['USER_PHOTO_PATH'] = $userHelper->getPhotoPath($employee->getPersonalPhoto());
		$this->arResult['MANAGER_PHOTO_PATH'] = $userHelper->getPhotoPath($userManagers[0]['PERSONAL_PHOTO']);
		$this->arResult['USER_FORMATTED_NAME'] = $userHelper->getFormattedName($recordForm->getUserFields());
		$this->arResult['USER_WORK_POSITION'] = $employee->getWorkPosition();
		$this->arResult['MANAGER_FORMATTED_NAME'] = $userHelper->getFormattedName($userManagers[0]);
		$this->arResult['MANAGER_WORK_POSITION'] = $userManagers[0]['WORK_POSITION'];
		$this->arResult['MANAGER_PROFILE_PATH'] = UserHelper::getInstance()->getProfilePath($userManagers[0]['ID']);
		$this->arResult['USER_PROFILE_PATH'] = UserHelper::getInstance()->getProfilePath($employee->getId());
		$this->makeRelatedRecordsLinks($record);
		$this->arResult['worktimeInfoHint'] = Loc::getMessage('TM_RECORD_REPORT_HINT_RECORD_INFO', [
			'#IP_OPEN#' => $record->getIpOpen() ?: 'N/A',
			'#IP_CLOSE#' => $record->getIpClose() ?: 'N/A',
			'#TIME_OFFSET#' => ($record->getStartOffset() > 0 ? '+' : '-') . $timeHelper->convertSecondsToHoursMinutes($record->getStartOffset()),
			'#TIME_OFFSET_SELF#' => ($userUtcOffset > 0 ? '+' : '-') . $timeHelper->convertSecondsToHoursMinutes($userUtcOffset),
		]);


		$this->arResult['IS_RECORD_APPROVED'] = $recordForm->getRecord()->isApproved();
		$this->arResult['startTimestamp'] = $recordForm->recordedStartTimestamp;

		$this->arResult['FIELD_CELLS']['START'] = [
			'TITLE' => Loc::getMessage('JS_CORE_TMR_START_TITLE'),
			'RECORDED_VALUE' => $recordForm->recordedStartTimestamp > 0 ? $timeHelper->convertUtcTimestampToHoursMinutesPostfix($recordForm->recordedStartTimestamp, $userUtcOffset) : '',
			'ACTUAL_VALUE' => $recordForm->actualStartTimestamp > 0 ? $timeHelper->convertUtcTimestampToHoursMinutesPostfix($recordForm->actualStartTimestamp, $userUtcOffset) : '',
			'ACTUAL_INFO' => [],
		];

		$this->arResult['FIELD_CELLS']['BREAK'] = [
			'HIDE' => $record->getRecordedBreakLength() < 60,
			'TITLE' => Loc::getMessage('JS_CORE_TMR_PAUSE'),
			'RECORDED_VALUE' => $timeHelper->convertSecondsToHoursMinutes($record->calculateCurrentBreakLength()),
			'ACTUAL_VALUE' => $timeHelper->convertSecondsToHoursMinutes($record->getActualBreakLength()),
			'ACTUAL_INFO' => [],
		];

		$recordedStop = $recordForm->recordedStopTimestamp > 0 ? $timeHelper->convertUtcTimestampToHoursMinutesPostfix($recordForm->recordedStopTimestamp, $userUtcOffset) : '';
		$actStop = $recordForm->actualStopTimestamp > 0 ? $timeHelper->convertUtcTimestampToHoursMinutesPostfix($recordForm->actualStopTimestamp, $userUtcOffset) : '';
		if ($recordForm->recordedStopTimestamp > 0)
		{
			$this->arResult['endTimestamp'] = $recordForm->recordedStopTimestamp;
		}
		else
		{
			$recordedStop = Loc::getMessage('JS_CORE_TMP_EXPIRE');
			if ($record->isExpired() && $record->getRecommendedStopTimestamp())
			{
				$recommendStop = $timeHelper->convertUtcTimestampToHoursMinutesPostfix($record->getRecommendedStopTimestamp(), $userUtcOffset);
				$this->arResult['endTimestamp'] = $record->getRecommendedStopTimestamp();
			}
		}
		if (!$recordForm->actualStopTimestamp)
		{
			$actStop = $recordedStop;
		}
		$this->arResult['FIELD_CELLS']['END'] = [
			'TITLE' => Loc::getMessage('JS_CORE_TMR_DEP'),
			'RECORDED_VALUE' => $recordedStop,
			'ACTUAL_VALUE' => $actStop,
			'ACTUAL_INFO' => [],
		];
		if (isset($recommendStop))
		{
			$this->arResult['FIELD_CELLS']['END']['TIME_PICKER_INIT_TIME'] = $recommendStop;
		}
		else
		{
			$this->arResult['FIELD_CELLS']['END']['TIME_PICKER_INIT_TIME'] = $timeHelper->convertUtcTimestampToHoursMinutesPostfix(
				$recordForm->recordedStopTimestamp ?: TimeHelper::getInstance()->getUtcNowTimestamp(),
				$userUtcOffset
			);
		}
		$dateHelper = new DateTimeHelper();

		$recordedStartDate = $timeHelper->createUserDateTimeFromFormat('U', $recordForm->recordedStartTimestamp, $this->currentUserId);
		$recordedEndDate = $timeHelper->createUserDateTimeFromFormat('U', $recordForm->recordedStopTimestamp, $this->currentUserId);

		if ($recordForm->recordedStopTimestamp > 0
			&& $recordedEndDate && $recordedStartDate
			&& $recordedStartDate->format('d') !== $recordedEndDate->format('d'))
		{
			$this->arResult['FIELD_CELLS']['START']['DATE'] = $dateHelper->formatDate('j F', $recordedStartDate);
			$this->arResult['FIELD_CELLS']['END']['DATE'] = $dateHelper->formatDate('j F', $recordedEndDate);
		}
		$actualStartDate = $timeHelper->createUserDateTimeFromFormat('U', $recordForm->actualStartTimestamp, $this->currentUserId);
		$actualEndDate = $timeHelper->createUserDateTimeFromFormat('U', $recordForm->actualStopTimestamp, $this->currentUserId);

		if ($recordForm->actualStopTimestamp > 0
			&& $actualEndDate && $actualStartDate
			&& $actualEndDate->format('d') !== $actualStartDate->format('d'))
		{
			$this->arResult['FIELD_CELLS']['START']['ACTUAL_VALUE'] .= ', ' . $dateHelper->formatDate('j F', $actualStartDate);
			$this->arResult['FIELD_CELLS']['END']['ACTUAL_VALUE'] .= ', ' . $dateHelper->formatDate('j F', $actualEndDate);
		}
		$durRecorded = $timeHelper->convertSecondsToHoursMinutesLocal($record->calculateCurrentDuration());
		$durActual = $durRecorded;
		$this->arResult['FIELD_CELLS']['DURATION'] = [
			'TITLE' => Loc::getMessage('JS_CORE_TMR_DURATION'),
			'RECORDED_VALUE' => $durRecorded,
			'ACTUAL_VALUE' => $durActual,
			'ACTUAL_INFO' => [],
		];
		if ($record->obtainSchedule())
		{
			$editedWarnings = Timeman\Service\DependencyManager::getInstance()
				->getViolationManager()
				->buildEditedWorktimeWarnings(
					(new WorktimeViolationParams())
						->setSchedule($record->obtainSchedule())
						->setViolationRules($this->getViolationRules($record))
						->setShift($record->obtainSchedule()->obtainShiftByPrimary($record->getShiftId()))
						->setRecord($record));
			$this->fillEditingExtraInfo($editedWarnings, $record, $employee, 'WARNINGS');
		}

		$this->fillEditingExtraInfo($this->arResult['VIOLATIONS'], $record, $employee, 'VIOLATIONS');

		$this->arResult['WORKTIME_RECORD_FORM_NAME'] = $recordForm->getFormName();
		$this->arResult['WORKTIME_EVENT_FORM_NAME'] = (new WorktimeEventForm())->getFormName();
		$this->arResult['WORKTIME_REPORT'] = Timeman\Service\DependencyManager::getInstance()
			->getWorktimeReportRepository()
			->findRecordReport($record->getId());
		if (!$this->arResult['WORKTIME_REPORT'])
		{
			$this->arResult['WORKTIME_REPORT']['REPORT'] = '';
		}
		$this->arResult['WORKTIME_REPORT']['TASKS'] = [];
		$this->arResult['WORKTIME_REPORT']['EVENTS'] = [];

		$dbRes = CTimeManReportDaily::GetList(['ID' => 'DESC'], ['ENTRY_ID' => $record->getId()]);
		if ($arRes = $dbRes->Fetch())
		{
			$this->arResult['WORKTIME_REPORT']['REPORT'] = $arRes['REPORT'];
			if ((CBXFeatures::IsFeatureEnabled('Tasks') && \Bitrix\Main\Loader::includeModule('tasks')))
			{
				$this->arResult['WORKTIME_REPORT']['TASKS'] = unserialize($arRes['TASKS']);
				if (!is_array($this->arResult['WORKTIME_REPORT']['TASKS']))
				{
					$this->arResult['WORKTIME_REPORT']['TASKS'] = [];
				}
				foreach ($this->arResult['WORKTIME_REPORT']['TASKS'] as $index => $task)
				{
					$this->arResult['WORKTIME_REPORT']['TASKS'][$index]['TIME_FORMATTED'] = '';
					if ($task['TIME'] >= 0)
					{
						$this->arResult['WORKTIME_REPORT']['TASKS'][$index]['TIME_FORMATTED'] = $timeHelper->convertSecondsToHoursMinutesLocal((int)$task['TIME']);
					}
				}
			}

			if (CBXFeatures::IsFeatureEnabled('Calendar'))
			{
				$this->arResult['WORKTIME_REPORT']['EVENTS'] = unserialize($arRes['EVENTS']);
				if (!is_array($this->arResult['WORKTIME_REPORT']['EVENTS']))
				{
					$this->arResult['WORKTIME_REPORT']['EVENTS'] = [];
				}
				if (\Bitrix\Main\Loader::includeModule('calendar'))
				{
					foreach ($this->arResult['WORKTIME_REPORT']['EVENTS'] as $eventIndex => $event)
					{
						$uri = new Uri(\CCalendar::GetPathForCalendarEx($event['OWNER_ID']));
						$uri->addParams(['EVENT_ID' => $event['ID']]);
						$this->arResult['WORKTIME_REPORT']['EVENTS'][$eventIndex]['URL'] = $uri->getLocator();
					}
				}
			}
		}
		$this->arResult['WORKTIME_REPORT']['REPORT'] = nl2br(htmlspecialcharsbx($this->arResult['WORKTIME_REPORT']['REPORT']));
	}

	/**
	 * @param $violations
	 * @param Timeman\Model\Worktime\Record\WorktimeRecord $record
	 * @param $employee
	 * @param $key
	 */
	private function fillEditingExtraInfo($violations, $record, $employee, $key)
	{
		if (empty($violations))
		{
			return;
		}
		foreach ($violations as $violation)
		{
			/** @var WorktimeViolation $violation */
			switch ($violation->type)
			{
				case WorktimeViolation::TYPE_EDITED_START:
					$this->arResult['FIELD_CELLS']['START']['CHANGED_TIME'] = true;
					$this->arResult['FIELD_CELLS']['DURATION'][$key][] = $violation;
					$this->arResult['FIELD_CELLS']['START'][$key][] = $violation;
					if ($editStartEvent = $record->obtainEventByType(WorktimeEventTable::EVENT_TYPE_EDIT_START))
					{
						$editStartTime = TimeHelper::getInstance()->createUserDateTimeFromFormat('U', $editStartEvent->getActualTimestamp(), $this->currentUserId);
						$this->arResult['FIELD_CELLS']['START']['ACTUAL_INFO'] = [
							'TITLE' => Loc::getMessage('JS_CORE_TMR_REPORT_START'),
							'EDITED_USER_TIME' => $editStartTime->format('d.m.Y H:i'),
							'EDITED_REASON' => $editStartEvent->getReason(),
						];
					}
					break;
				case WorktimeViolation::TYPE_EARLY_START:
				case WorktimeViolation::TYPE_LATE_START:
				case WorktimeViolation::TYPE_SHIFT_LATE_START:
					$this->arResult['FIELD_CELLS']['START'][$key][] = $violation;
					break;

				case WorktimeViolation::TYPE_EDITED_ENDING:
					$this->arResult['FIELD_CELLS']['END']['CHANGED_TIME'] = true;
					$this->arResult['FIELD_CELLS']['DURATION'][$key][] = $violation;
					$this->arResult['FIELD_CELLS']['END'][$key][] = $violation;
					if ($editStopEvent = $record->obtainEventByType(WorktimeEventTable::EVENT_TYPE_EDIT_STOP))
					{
						$editStopTime = TimeHelper::getInstance()->createUserDateTimeFromFormat('U', $editStopEvent->getActualTimestamp(), $this->currentUserId);
						$this->arResult['FIELD_CELLS']['END']['ACTUAL_INFO'] = [
							'TITLE' => Loc::getMessage('JS_CORE_TMR_REPORT_FINISH'),
							'EDITED_USER_TIME' => $editStopTime->format('d.m.Y H:i'),
							'EDITED_REASON' => $editStopEvent->getReason(),
						];
					}
					break;
				case WorktimeViolation::TYPE_EARLY_ENDING:
				case WorktimeViolation::TYPE_LATE_ENDING:
					$this->arResult['FIELD_CELLS']['END'][$key][] = $violation;
					break;


				case WorktimeViolation::TYPE_EDITED_BREAK_LENGTH:
					$this->arResult['FIELD_CELLS']['BREAK']['CHANGED_TIME'] = true;
					$this->arResult['FIELD_CELLS']['BREAK'][$key][] = $violation;
					if ($event = $record->obtainEventByType(WorktimeEventTable::EVENT_TYPE_EDIT_BREAK_LENGTH))
					{
						$editStopTime = TimeHelper::getInstance()->createUserDateTimeFromFormat('U', $event->getActualTimestamp(), $this->currentUserId);
						$this->arResult['FIELD_CELLS']['BREAK']['ACTUAL_INFO'] = [
							'TITLE' => Loc::getMessage('JS_CORE_TMR_REPORT_DURATION'),
							'EDITED_USER_TIME' => $editStopTime->format('d.m.Y H:i'),
							'EDITED_REASON' => $event->getReason(),
						];
					}
					break;

				case WorktimeViolation::TYPE_MIN_DAY_DURATION:
					$this->arResult['FIELD_CELLS']['DURATION'][$key][] = $violation;
					break;
				default:
					break;
			}
		}
	}

	/**
	 * @param Timeman\Model\Worktime\Record\WorktimeRecord $record
	 */
	private function makeRelatedRecordsLinks($record)
	{
		$prevRecord = Timeman\Model\Worktime\Record\WorktimeRecordTable::query()
			->addSelect('ID')
			->where('USER_ID', $record->getUserId())
			->where('RECORDED_START_TIMESTAMP', '<', $record->getRecordedStartTimestamp())
			->addOrder('RECORDED_START_TIMESTAMP', 'desc')
			->setLimit(1)
			->exec()
			->fetchObject();
		if ($prevRecord)
		{
			$uri = new Uri(DependencyManager::getInstance()->getUrlManager()->getUriTo('recordReport', ['RECORD_ID' => $prevRecord->getId()]));
			$uri->addParams(['IFRAME' => 'Y']);
			$this->arResult['RECORD_PREV_HREF'] = $uri->getLocator();
		}
		$nextRecord = Timeman\Model\Worktime\Record\WorktimeRecordTable::query()
			->addSelect('ID')
			->where('USER_ID', $record->getUserId())
			->where('RECORDED_START_TIMESTAMP', '>', $record->getRecordedStartTimestamp())
			->addOrder('RECORDED_START_TIMESTAMP', 'asc')
			->setLimit(1)
			->exec()
			->fetchObject();
		if ($nextRecord)
		{
			$uri = new Uri(DependencyManager::getInstance()->getUrlManager()->getUriTo('recordReport', ['RECORD_ID' => $nextRecord->getId()]));
			$uri->addParams(['IFRAME' => 'Y']);
			$this->arResult['RECORD_NEXT_HREF'] = $uri->getLocator();
		}
	}

	private function findIndividualViolationRules(Timeman\Model\Worktime\Record\WorktimeRecord $record)
	{
		return DependencyManager::getInstance()->getViolationRulesRepository()
			->findFirstByScheduleIdAndEntityCode($record->getScheduleId(), EntityCodesHelper::buildUserCode($record->getUserId()));
	}

	/**
	 * @param Timeman\Model\Worktime\Record\WorktimeRecord $record
	 */
	private function getViolationRules($record)
	{
		if (!$record->obtainSchedule())
		{
			return null;
		}
		$isIndividual = $this->getRequest()->get('useIndividualViolationRules') === 'Y';
		$rules = $record->obtainSchedule()->obtainScheduleViolationRules();
		if ($isIndividual)
		{
			$rules = $this->findIndividualViolationRules($record);
		}
		return $rules;
	}
}