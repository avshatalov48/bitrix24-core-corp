<?php
namespace Bitrix\Timeman\Components\ReportEntry;

use \Bitrix\Main;
use Bitrix\Main\Application;
use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use \Bitrix\Timeman;
use Bitrix\Timeman\Form\Worktime\WorktimeEventForm;
use Bitrix\Timeman\Form\Worktime\WorktimeRecordForm;
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
	private $showingOffset;
	/** @var TimeHelper */
	private $timeHelper;
	/** @var Timeman\Helper\Form\Worktime\RecordFormHelper */
	private $recordFormHelper;
	private $shortTimeFormat;
	private $dayMonthFormat;
	/** @var Timeman\Model\User\User */
	private $mainUser;
	/** @var Timeman\Model\User\User */
	private $oppositeUser;
	private $dateAndTimeFormat;

	public function __construct($component = null)
	{
		parent::__construct($component);

		$this->worktimeRepository = new WorktimeRepository();
		$this->timeHelper = TimeHelper::getInstance();
		$this->dayMonthFormat = Main\Context::getCurrent()->getCulture()->getDayMonthFormat();
		$this->recordFormHelper = new Timeman\Helper\Form\Worktime\RecordFormHelper();
		$this->shortTimeFormat = Main\Context::getCurrent()->getCulture()->getShortTimeFormat();
		$this->dateAndTimeFormat = Main\Context::getCurrent()->getCulture()->getShortDateFormat() . ', ' . $this->shortTimeFormat; /*-*/// todo add Loc
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
		$this->arResult['isShiftplan'] = $this->getExtraInfo()['isShiftplan'] ?? null;
		$record = null;
		$this->arResult['URL_TEMPLATES_PROFILE_VIEW'] = UserHelper::getInstance()->getProfilePath('#USER_ID#');
		if ($this->arResult['RECORD_ID'])
		{
			$record = $this->worktimeRepository->findByIdWith(
				$this->arResult['RECORD_ID'],
				['USER', 'WORKTIME_EVENTS', 'SCHEDULE', 'SHIFT', 'SCHEDULE.SCHEDULE_VIOLATION_RULES', 'REPORTS']
			);
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
		$this->arResult['useEmployeesTimezone'] = $this->useEmployeesTimezone();

		$recordManager = DependencyManager::getInstance()->buildWorktimeRecordManager(
			$record,
			$record->obtainSchedule(),
			$record->obtainShift()
		);
		$this->arResult['canChangeWorktime'] = (
			!$this->userPermissionsManager->canUpdateWorktime($record->getUserId())
			&& ($recordManager->isRecordExpired() && $this->currentUserId == $record->getUserId())
		);

		$recordForm = new WorktimeRecordForm($record);
		if ($record->obtainSchedule())
		{
			$violations = [];
			if ($record->isApproved())
			{
				$violations = $this->buildViolations($record, $this->getViolationRules($record));
			}
			else
			{
				$violationsAll = $this->buildViolations($record, $record->obtainSchedule()->obtainScheduleViolationRules());
				$violationsAll = array_merge(
					$violationsAll,
					$this->buildViolations($record, $this->findIndividualViolationRules($record))
				);
				foreach ($violationsAll as $violation)
				{
					$violations[$violation->type] = $violation;
				}
			}
			$this->arResult['VIOLATIONS'] = $violations;
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

	private function findUserManagers(array $getManagerIds): array
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
		$userHelper = UserHelper::getInstance();
		$employee = $record->obtainUser();
		if ($employee && $record->getStartOffset() !== $employee->obtainUtcOffset())
		{
			$employee->defineUtcOffset($record->getStartOffset());
			$employee->defineTimezoneName('');
		}
		$currentUser = DependencyManager::getInstance()->getScheduleRepository()
			->getUsersBaseQuery(true)
			->addSelect('PERSONAL_GENDER')
			->addSelect('TIME_ZONE')
			->where('ID', $this->currentUserId)
			->exec()
			->fetchObject();
		$this->mainUser = $this->useEmployeesTimezone() ? $employee : $currentUser;
		$this->oppositeUser = $this->useEmployeesTimezone() ? $currentUser : $employee;
		$recordManager = DependencyManager::getInstance()->buildWorktimeRecordManager($record, $record->obtainSchedule(), $record->obtainShift());
		$userManagers = $this->findUserManagers($userHelper->getManagerIds($employee->getId()) ?: [$employee->getId()]);
		$this->showingOffset = $this->mainUser->obtainUtcOffset();

		$recordedStartDate = $this->timeHelper->createUserDateTimeFromFormat(
			'U',
			$recordForm->recordedStartTimestamp,
			$this->mainUser->getId()
		);
		$this->arResult['REPORT_FORMATTED_DATE'] = $this->timeHelper->formatDateTime($recordedStartDate, 'd F Y');

		$this->arResult['USER_PHOTO_PATH'] = $userHelper->getPhotoPath($employee->getPersonalPhoto());
		$this->arResult['MANAGER_PHOTO_PATH'] = $userHelper->getPhotoPath($userManagers[0]['PERSONAL_PHOTO']);
		$this->arResult['USER_FORMATTED_NAME'] = $userHelper->getFormattedName($recordForm->getUserFields());
		$this->arResult['USER_WORK_POSITION'] = $employee->getWorkPosition();
		$this->arResult['MANAGER_FORMATTED_NAME'] = $userHelper->getFormattedName($userManagers[0]);
		$this->arResult['MANAGER_WORK_POSITION'] = $userManagers[0]['WORK_POSITION'];
		$this->arResult['MANAGER_PROFILE_PATH'] = UserHelper::getInstance()->getProfilePath($userManagers[0]['ID']);
		$this->arResult['USER_PROFILE_PATH'] = UserHelper::getInstance()->getProfilePath($employee->getId());
		$this->makeRelatedRecordsLinks($record);

		$this->arResult['IS_RECORD_APPROVED'] = $recordForm->getRecord()->isApproved();
		$this->arResult['startTimestamp'] = $recordForm->recordedStartTimestamp;

		$this->arResult['FIELD_CELLS']['START'] = [
			'TITLE' => Loc::getMessage('JS_CORE_TMR_START_TITLE'),
			'RECORDED_VALUE' => $this->timeHelper->convertUtcTimestampToHoursMinutesAmPm($recordForm->recordedStartTimestamp, $this->showingOffset),
			'TIME_PICKER_INIT_DATE' => $this->timeHelper->createDateTimeFromFormat(
				'U', $recordForm->recordedStartTimestamp, $this->showingOffset
			)->format('m/d/Y'),
			'ACTUAL_VALUE' => $recordForm->actualStartTimestamp > 0 ? $this->timeHelper->convertUtcTimestampToHoursMinutesAmPm($recordForm->actualStartTimestamp, $this->showingOffset) : '',
			'ACTUAL_INFO' => [],
		];
		$this->arResult['FIELD_CELLS']['BREAK'] = [
			'HIDE' => $record->getRecordedBreakLength() < 60,
			'TITLE' => Loc::getMessage('JS_CORE_TMR_PAUSE'),
			'RECORDED_VALUE' => $this->timeHelper->convertSecondsToHoursMinutes($record->calculateCurrentBreakLength()),
			'ACTUAL_VALUE' => $this->timeHelper->convertSecondsToHoursMinutes($record->getActualBreakLength()),
			'ACTUAL_INFO' => [],
		];

		$recordedStop = $recordForm->recordedStopTimestamp > 0 ? $this->timeHelper->convertUtcTimestampToHoursMinutesAmPm($recordForm->recordedStopTimestamp, $this->showingOffset) : '';
		$expectedStop = null;
		$actStop = $recordForm->actualStopTimestamp > 0 ? $this->timeHelper->convertUtcTimestampToHoursMinutesAmPm($recordForm->actualStopTimestamp, $this->showingOffset) : '';
		if ($recordForm->recordedStopTimestamp <= 0)
		{
			$recordedStop = Loc::getMessage('JS_CORE_TMP_EXPIRE');
			if ($recordManager->isRecordExpired())
			{
				$expectedStop = $recordManager->getRecommendedStopTimestamp();
				if ($expectedStop)
				{
					$recommendStop = $this->timeHelper->convertUtcTimestampToHoursMinutesAmPm($expectedStop, $this->showingOffset);
				}
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
		if (isset($recommendStop) && $expectedStop !== null)
		{
			$this->arResult['FIELD_CELLS']['END']['TIME_PICKER_INIT_TIME'] = $recommendStop;
			$this->arResult['FIELD_CELLS']['END']['TIME_PICKER_INIT_DATE'] = $this->timeHelper->createDateTimeFromFormat(
				'U', $expectedStop, $this->showingOffset
			)->format('m/d/Y');
		}
		else
		{
			$this->arResult['FIELD_CELLS']['END']['TIME_PICKER_INIT_TIME'] = $this->timeHelper->convertUtcTimestampToHoursMinutesAmPm(
				$recordForm->recordedStopTimestamp ?: $this->timeHelper->getUtcNowTimestamp(),
				$this->showingOffset
			);
			$this->arResult['FIELD_CELLS']['END']['TIME_PICKER_INIT_DATE'] = $this->timeHelper->createDateTimeFromFormat(
				'U', $recordForm->recordedStopTimestamp ?: $this->timeHelper->getUtcNowTimestamp(), $this->showingOffset
			)->format('m/d/Y');
		}
		$recordedStartDate = $this->timeHelper->createUserDateTimeFromFormat('U', $recordForm->recordedStartTimestamp, $this->mainUser->getId());
		$recordedEndDate = $this->timeHelper->createUserDateTimeFromFormat('U', $recordForm->recordedStopTimestamp, $this->mainUser->getId());

		if ($recordForm->recordedStopTimestamp > 0
			&& $recordedEndDate && $recordedStartDate
			&& $recordedStartDate->format('d') !== $recordedEndDate->format('d'))
		{
			$this->arResult['FIELD_CELLS']['START']['DATE'] = $this->timeHelper->formatDateTime($recordedStartDate, $this->dayMonthFormat);
			$this->arResult['FIELD_CELLS']['END']['DATE'] = $this->timeHelper->formatDateTime($recordedEndDate, $this->dayMonthFormat);
		}
		$actualStartDate = $this->timeHelper->createUserDateTimeFromFormat('U', $recordForm->actualStartTimestamp, $this->mainUser->getId());
		$actualEndDate = $this->timeHelper->createUserDateTimeFromFormat('U', $recordForm->actualStopTimestamp, $this->mainUser->getId());

		if ($recordForm->actualStopTimestamp > 0
			&& $actualEndDate && $actualStartDate
			&& $actualEndDate->format('d') !== $actualStartDate->format('d'))
		{
			$this->arResult['FIELD_CELLS']['START']['ACTUAL_VALUE'] .= ', ' . $this->timeHelper->formatDateTime($actualStartDate, $this->dayMonthFormat);
			$this->arResult['FIELD_CELLS']['END']['ACTUAL_VALUE'] .= ', ' . $this->timeHelper->formatDateTime($actualEndDate, $this->dayMonthFormat);
		}
		$durRecorded = $this->timeHelper->convertSecondsToHoursMinutesLocal($record->calculateCurrentDuration());
		$durActual = $durRecorded;
		$this->arResult['FIELD_CELLS']['DURATION'] = [
			'TITLE' => Loc::getMessage('JS_CORE_TMR_DURATION'),
			'RECORDED_VALUE' => $durRecorded,
			'ACTUAL_VALUE' => $durActual,
			'ACTUAL_INFO' => [],
		];
		if ($record->obtainSchedule())
		{
			$editedWarnings = [];
			if (!empty($this->arResult['VIOLATIONS']))
			{
				$types = [
					WorktimeViolation::TYPE_EDITED_ENDING,
					WorktimeViolation::TYPE_EDITED_START,
					WorktimeViolation::TYPE_EDITED_BREAK_LENGTH,
				];
				foreach ($this->arResult['VIOLATIONS'] as $violation)
				{
					/** @var WorktimeViolation $violation */
					if (in_array($violation->type, $types, true))
					{
						$editedWarnings[] = $violation;
					}
				}
			}
			$this->fillEditingExtraInfo($editedWarnings, $record, 'WARNINGS');
		}

		$this->fillEditingExtraInfo($this->arResult['VIOLATIONS'], $record, 'VIOLATIONS');

		$this->arResult['WORKTIME_RECORD_FORM_NAME'] = $recordForm->getFormName();
		$this->arResult['WORKTIME_EVENT_FORM_NAME'] = (new WorktimeEventForm())->getFormName();

		$this->initReportsTasks($record);
		$this->initHints($record);
	}

	/**
	 * @param $violations
	 * @param Timeman\Model\Worktime\Record\WorktimeRecord $record
	 * @param $key
	 */
	private function fillEditingExtraInfo($violations, $record, $key)
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
					$this->arResult['FIELD_CELLS']['START']['EDITED_VIOLATIONS'][$violation->type] = $violation;
					$this->arResult['FIELD_CELLS']['START']['CHANGED_TIME'] = true;
					$this->arResult['FIELD_CELLS']['DURATION'][$key][] = $violation;
					$this->arResult['FIELD_CELLS']['START'][$key][] = $violation;
					if ($editStartEvent = $record->obtainEventByType(WorktimeEventTable::EVENT_TYPE_EDIT_START))
					{
						$editStartTime = $this->timeHelper->createUserDateTimeFromFormat(
							'U', $editStartEvent->getActualTimestamp(), $this->mainUser->getId()
						);
						$this->arResult['FIELD_CELLS']['START']['ACTUAL_INFO'] = [
							'TITLE' => Loc::getMessage('JS_CORE_TMR_REPORT_START'),
							'EDITED_USER_TIME' => $editStartTime->format('d.m.Y H:i'),
							'EDITED_USER_DATE_TIME' => $editStartTime,
							'EDITED_REASON' => $editStartEvent->getReason(),
						];
					}
					break;
				case WorktimeViolation::TYPE_EARLY_START:
				case WorktimeViolation::TYPE_LATE_START:
				case WorktimeViolation::TYPE_SHIFT_LATE_START:
					$this->arResult['FIELD_CELLS']['START']['OTHER_VIOLATIONS'][$violation->type] = $violation;
					$this->arResult['FIELD_CELLS']['START'][$key][] = $violation;
					break;

				case WorktimeViolation::TYPE_EDITED_ENDING:
					$this->arResult['FIELD_CELLS']['END']['EDITED_VIOLATIONS'][$violation->type] = $violation;
					$this->arResult['FIELD_CELLS']['END']['CHANGED_TIME'] = true;
					$this->arResult['FIELD_CELLS']['DURATION'][$key][] = $violation;
					$this->arResult['FIELD_CELLS']['END'][$key][] = $violation;
					if ($editStopEvent = $record->obtainEventByType(WorktimeEventTable::EVENT_TYPE_EDIT_STOP))
					{
						$editStopTime = $this->timeHelper->createUserDateTimeFromFormat(
							'U', $editStopEvent->getActualTimestamp(), $this->mainUser->getId()
						);
						$this->arResult['FIELD_CELLS']['END']['ACTUAL_INFO'] = [
							'TITLE' => Loc::getMessage('JS_CORE_TMR_REPORT_FINISH'),
							'EDITED_USER_TIME' => $editStopTime->format('d.m.Y H:i'),
							'EDITED_USER_DATE_TIME' => $editStopTime,
							'EDITED_REASON' => $editStopEvent->getReason(),
						];
					}
					break;
				case WorktimeViolation::TYPE_EARLY_ENDING:
				case WorktimeViolation::TYPE_LATE_ENDING:
					$this->arResult['FIELD_CELLS']['END']['OTHER_VIOLATIONS'][$violation->type] = $violation;
					$this->arResult['FIELD_CELLS']['END'][$key][] = $violation;
					break;


				case WorktimeViolation::TYPE_EDITED_BREAK_LENGTH:
					$this->arResult['FIELD_CELLS']['BREAK']['EDITED_VIOLATIONS'][$violation->type] = $violation;
					$this->arResult['FIELD_CELLS']['BREAK']['CHANGED_TIME'] = true;
					$this->arResult['FIELD_CELLS']['BREAK'][$key][] = $violation;
					$event = $record->obtainEventByType(WorktimeEventTable::EVENT_TYPE_EDIT_BREAK_LENGTH);
					if ($event || $event = $record->obtainEventByType(WorktimeEventTable::EVENT_TYPE_APPROVE))
					{
						$editStopTime = $this->timeHelper->createUserDateTimeFromFormat(
							'U', $event->getActualTimestamp(), $this->mainUser->getId()
						);
						$this->arResult['FIELD_CELLS']['BREAK']['ACTUAL_INFO'] = [
							'TITLE' => Loc::getMessage('JS_CORE_TMR_REPORT_DURATION'),
							'EDITED_USER_TIME' => $editStopTime->format('d.m.Y H:i'),
							'EDITED_USER_DATE_TIME' => $editStopTime,
							'EDITED_REASON' => $event->getReason(),
						];
					}
					break;

				case WorktimeViolation::TYPE_MIN_DAY_DURATION:
					$this->arResult['FIELD_CELLS']['DURATION']['OTHER_VIOLATIONS'][$violation->type] = $violation;
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

	private function getCookie($name)
	{
		$raw = Application::getInstance()->getContext()->getRequest()->getCookieRaw($name);
		return $raw === 'Y';
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
		$isIndividual = $this->getCookie('useIndividualViolationRules');
		$rules = $record->obtainSchedule()->obtainScheduleViolationRules();
		if ($isIndividual)
		{
			$rules = $this->findIndividualViolationRules($record);
		}
		return $rules;
	}

	private function makeOffsetSign($offset)
	{
		return ($offset === 0 ? '' : ($offset > 0 ? '+' : '-'));
	}

	private function getExtraInfo()
	{
		if ($this->getRequest()->get('extraInfo') !== null)
		{
			try
			{
				return (array)json_decode($this->getRequest()->get('extraInfo'), true);
			}
			catch (\Exception $exc)
			{
			}
		}

		return [];
	}

	private function useEmployeesTimezone()
	{
		if (array_key_exists('useEmployeesTimezone', $this->getExtraInfo()))
		{
			return $this->getExtraInfo()['useEmployeesTimezone'];
		}
		return $this->getCookie('useEmployeesTimezone');
	}

	/**
	 * @param Timeman\Model\Worktime\Record\WorktimeRecord $record
	 * @param Timeman\Model\Schedule\Schedule $schedule
	 * @param Timeman\Model\Schedule\ShiftPlan\ShiftPlan $plan
	 * @param Timeman\Model\Schedule\Violation\ViolationRules $rules
	 */
	private function buildViolations($record, $rules)
	{
		if (!$record->obtainSchedule())
		{
			return [];
		}
		return DependencyManager::getInstance()
			->getViolationManager()
			->buildViolations(
				(new WorktimeViolationParams())
					->setRecord($record)
					->setShiftPlan($this->getPlanForRecord($record))
					->setShift($record->obtainSchedule()->obtainShiftByPrimary($record->getShiftId()))
					->setSchedule($record->obtainSchedule())
					->setViolationRules($rules)
			);
	}

	/**
	 * @param Timeman\Model\Worktime\Record\WorktimeRecord $record
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectException
	 * @throws Main\SystemException
	 */
	private function getPlanForRecord($record)
	{
		static $plan = false;
		if ($plan === false)
		{
			$plan = null;
			if ($record && $record->obtainSchedule() && $record->obtainSchedule()->isShifted())
			{
				$plan = DependencyManager::getInstance()
					->getShiftPlanRepository()
					->findActiveByRecord($record);
			}
		}
		return $plan;
	}

	private function initReportsTasks(Timeman\Model\Worktime\Record\WorktimeRecord $record)
	{
		$this->arResult['WORKTIME_REPORT']['REPORT'] = null;
		$this->arResult['WORKTIME_REPORT']['TASKS'] = [];
		$this->arResult['WORKTIME_REPORT']['EVENTS'] = [];

		$dbRes = CTimeManReportDaily::getList(['ID' => 'DESC'], ['ENTRY_ID' => $record->getId()]);
		if ($arRes = $dbRes->fetch())
		{
			$this->arResult['WORKTIME_REPORT']['REPORT'] = $arRes['REPORT'];
			if ((CBXFeatures::isFeatureEnabled('Tasks') && \Bitrix\Main\Loader::includeModule('tasks')))
			{
				$this->arResult['WORKTIME_REPORT']['TASKS'] = unserialize(
					$arRes['TASKS'] ?? '',
					['allowed_classes' => false]
				);
				if (!is_array($this->arResult['WORKTIME_REPORT']['TASKS']))
				{
					$this->arResult['WORKTIME_REPORT']['TASKS'] = [];
				}
				foreach ($this->arResult['WORKTIME_REPORT']['TASKS'] as $index => $task)
				{
					$this->arResult['WORKTIME_REPORT']['TASKS'][$index]['TIME_FORMATTED'] = '';
					if ($task['TIME'] >= 0)
					{
						$this->arResult['WORKTIME_REPORT']['TASKS'][$index]['TIME_FORMATTED'] = $this->timeHelper->convertSecondsToHoursMinutesLocal((int)$task['TIME']);
					}
				}
			}

			if (CBXFeatures::IsFeatureEnabled('Calendar'))
			{
				$this->arResult['WORKTIME_REPORT']['EVENTS'] = unserialize(
					$arRes['EVENTS'] ?? '',
					['allowed_classes' => false]
				);
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
		if ($this->arResult['WORKTIME_REPORT']['REPORT'] === null)
		{
			$this->arResult['WORKTIME_REPORT']['REPORT'] = '';
			$report = Timeman\Service\DependencyManager::getInstance()
				->getWorktimeReportRepository()
				->findRecordReport($record->getId());
			if ($report)
			{
				$this->arResult['WORKTIME_REPORT']['REPORT'] = $report->getReport();
			}
		}
		$this->arResult['WORKTIME_REPORT']['REPORT'] = nl2br(htmlspecialcharsbx($this->arResult['WORKTIME_REPORT']['REPORT']));
	}

	private function initHints(Timeman\Model\Worktime\Record\WorktimeRecord $record)
	{
		$validator = (new Timeman\Util\Form\Filter\Validator\RegularExpressionValidator())->configurePattern('#([0-9]{1,3}[\.]){3}[0-9]{1,3}#');
		$this->arResult['worktimeInfoHint'] = '';
		$this->arResult['FIELD_CELLS']['END']['RECORDED_VALUE_HINT'] = '';
		$this->arResult['FIELD_CELLS']['END']['ACTUAL_VALUE_HINT'] = '';
		$selfOffset = (int)$this->timeHelper->getUserUtcOffset($this->currentUserId);
		if ($record->getStartOffset() !== $selfOffset)
		{
			$this->arResult['FIELD_CELLS']['START']['RECORDED_VALUE_HINT'] = $this->recordFormHelper->buildTimeDifferenceHint(
				$this->mainUser,
				$this->oppositeUser,
				$this->shortTimeFormat,
				$record->buildRecordedStartDateTime()
			);
			$this->arResult['FIELD_CELLS']['START']['ACTUAL_VALUE_HINT'] = $this->recordFormHelper->buildTimeDifferenceHint(
				$this->mainUser,
				$this->oppositeUser,
				$this->shortTimeFormat,
				$this->timeHelper->createDateTimeFromFormat('U', $record->getActualStartTimestamp(), $record->getStartOffset())
			);
			if (!empty($this->arResult['FIELD_CELLS']['START']['ACTUAL_INFO']['EDITED_USER_DATE_TIME']))
			{
				$this->arResult['FIELD_CELLS']['START']['ACTUAL_INFO_HINT'] = $this->recordFormHelper->buildTimeDifferenceHint(
					$this->mainUser,
					$this->oppositeUser,
					$this->dateAndTimeFormat,
					$this->arResult['FIELD_CELLS']['START']['ACTUAL_INFO']['EDITED_USER_DATE_TIME']
				);
			}
			if (!empty($this->arResult['FIELD_CELLS']['BREAK']['ACTUAL_INFO']['EDITED_USER_DATE_TIME']))
			{
				$this->arResult['FIELD_CELLS']['BREAK']['ACTUAL_INFO_HINT'] = $this->recordFormHelper->buildTimeDifferenceHint(
					$this->mainUser,
					$this->oppositeUser,
					$this->dateAndTimeFormat,
					$this->arResult['FIELD_CELLS']['BREAK']['ACTUAL_INFO']['EDITED_USER_DATE_TIME']
				);
			}
			$this->arResult['worktimeInfoHint'] = Loc::getMessage('TM_RECORD_REPORT_HINT_RECORD_TIMEZONE_INFO', [
				'#TIME_OFFSET#' => $this->makeOffsetSign($record->getStartOffset()) . $this->timeHelper->convertSecondsToHoursMinutes($record->getStartOffset()),
				'#TIME_OFFSET_SELF#' => $this->makeOffsetSign($selfOffset) . $this->timeHelper->convertSecondsToHoursMinutes($selfOffset),
			]);
			$this->arResult['worktimeInfoHint'] .= '<br><br>';
		}
		$this->arResult['worktimeInfoHint'] .= Loc::getMessage('TM_RECORD_REPORT_HINT_RECORD_IP_INFO', [
			'#IP_OPEN#' => $validator->validate($record->getIpOpen())->isSuccess() ? $record->getIpOpen() : 'N/A',
			'#IP_CLOSE#' => $record->isClosed() && $validator->validate($record->getIpClose())->isSuccess() ? $record->getIpClose() : 'N/A',
		]);
		if ($record->getStopOffset() !== $selfOffset)
		{
			if ($record->getRecordedStopTimestamp() > 0)
			{
				$this->arResult['FIELD_CELLS']['END']['RECORDED_VALUE_HINT'] = $this->recordFormHelper->buildTimeDifferenceHint(
					$this->mainUser,
					$this->oppositeUser,
					$this->shortTimeFormat,
					$record->buildRecordedStopDateTime()
				);
			}
			if ($record->getActualStopTimestamp() > 0)
			{
				$this->arResult['FIELD_CELLS']['END']['ACTUAL_VALUE_HINT'] = $this->recordFormHelper->buildTimeDifferenceHint(
					$this->mainUser,
					$this->oppositeUser,
					$this->shortTimeFormat,
					$this->timeHelper->createDateTimeFromFormat('U', $record->getActualStopTimestamp(), $record->getStopOffset())
				);
			}
			if (!empty($this->arResult['FIELD_CELLS']['END']['ACTUAL_INFO']['EDITED_USER_DATE_TIME']))
			{
				$this->arResult['FIELD_CELLS']['END']['ACTUAL_INFO_HINT'] = $this->recordFormHelper->buildTimeDifferenceHint(
					$this->mainUser,
					$this->oppositeUser,
					$this->dateAndTimeFormat,
					$this->arResult['FIELD_CELLS']['END']['ACTUAL_INFO']['EDITED_USER_DATE_TIME']
				);
			}
		}
	}
}