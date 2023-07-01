<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Timeman\Form\Schedule\ScheduleForm;
use Bitrix\Timeman\Form\Schedule\ViolationForm;
use Bitrix\Timeman\Helper\EntityCodesHelper;
use Bitrix\Timeman\Helper\Form\Schedule\CalendarFormHelper;
use Bitrix\Timeman\Helper\Form\Schedule\ScheduleFormHelper;
use Bitrix\Timeman\Helper\UserHelper;
use Bitrix\Timeman\Model\Schedule\Calendar\CalendarTable;
use Bitrix\Timeman\Model\Schedule\Calendar\EO_Calendar_Collection;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;
use Bitrix\Timeman\Model\Schedule\Shift\ShiftTable;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRules;
use Bitrix\Timeman\Repository\Schedule\ScheduleRepository;
use Bitrix\Timeman\Repository\Schedule\ViolationRulesRepository;
use Bitrix\Timeman\Service\DependencyManager;
use Bitrix\Timeman\TimemanUrlManager;

if (!\Bitrix\Main\Loader::includeModule('timeman') ||
	!\Bitrix\Main\Loader::includeModule('intranet'))
{
	return;
}
Loc::loadMessages(__FILE__);

/**
 * Class MainNumeratorEdit
 */
class TimemanScheduleComponent extends \Bitrix\Timeman\Component\BaseComponent
{
	/** @var ScheduleRepository $scheduleRepository */
	private $scheduleRepository;
	private $nationalHolidays;
	/** @var \Bitrix\Timeman\Security\UserPermissionsManager */
	private $userPermissionsManager;
	/** @var ViolationRulesRepository $violationRepository */
	private $violationRepository;
	private $calendarRepository;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->scheduleRepository = DependencyManager::getInstance()->getScheduleRepository();
		$this->violationRepository = DependencyManager::getInstance()->getViolationRulesRepository();
		$this->calendarRepository = DependencyManager::getInstance()->getCalendarRepository();
		global $USER;
		$this->userPermissionsManager = DependencyManager::getInstance()->getUserPermissionsManager($USER);
	}

	public function onPrepareComponentParams($arParams)
	{
		$this->arResult['SCHEDULE_ID'] = $this->getFromParamsOrRequest($arParams, 'SCHEDULE_ID', 'int');
		$this->arResult['VIOLATIONS_ONLY'] = $this->getRequest()->get('VIOLATIONS_ONLY') === 'Y';
		if ($this->getRequest()->get('VIOLATIONS_ONLY') === null && !empty($this->arParams['VIOLATIONS_ONLY']))
		{
			$this->arResult['VIOLATIONS_ONLY'] = $this->arParams['VIOLATIONS_ONLY'] === 'Y';
		}
		$this->arResult['ENTITY_CODE'] = $this->getRequest()->get('ENTITY_CODE');
		if ($this->arResult['ENTITY_CODE'] === null)
		{
			$this->arResult['ENTITY_CODE'] = $this->arParams['ENTITY_CODE'] ?? null;
		}
		$this->arResult['hideShiftPlanBtn'] = $this->getRequest()->get('hideShiftPlanBtn') === 'Y';
		return $arParams;
	}

	/** @inheritdoc */
	public function executeComponent()
	{
		$editingSchedule = null;
		$this->arResult['isNewSchedule'] = true;
		$this->arResult['showShiftPlanBtn'] = false;
		if ($this->arResult['SCHEDULE_ID'])
		{
			if (!$this->userPermissionsManager->canReadSchedule($this->arResult['SCHEDULE_ID']))
			{
				return $this->showError(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_ERROR_SCHEDULE_READ_ACCESS_DENIED'));
			}
			$this->arResult['isNewSchedule'] = false;
			if ($this->arResult['VIOLATIONS_ONLY'])
			{
				$editingSchedule = $this->scheduleRepository->findById($this->arResult['SCHEDULE_ID']);
			}
			else
			{
				$editingSchedule = $this->scheduleRepository->findByIdWith($this->arResult['SCHEDULE_ID'], [
					'CALENDAR',
					'CALENDAR.EXCLUSIONS',
					'CALENDAR.PARENT_CALENDAR.ID',
					'CALENDAR.PARENT_CALENDAR.EXCLUSIONS',
					'SHIFTS',
					'DEPARTMENTS',
					'USER_ASSIGNMENTS',
					'SCHEDULE_VIOLATION_RULES',
				]);
			}
			if (!$editingSchedule)
			{
				return $this->showError(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_ERROR_SCHEDULE_NOT_FOUND'));
			}
			if ($editingSchedule->isShifted())
			{
				if (!$this->arResult['hideShiftPlanBtn'])
				{
					if ($this->userPermissionsManager->canUpdateShiftPlan($this->arResult['SCHEDULE_ID']))
					{
						$this->arResult['showShiftPlanBtn'] = true;
					}
					$this->arResult['shiftPlanLink'] = DependencyManager::getInstance()->getUrlManager()
						->getUriTo(TimemanUrlManager::URI_SCHEDULE_SHIFTPLAN, ['SCHEDULE_ID' => $this->arResult['SCHEDULE_ID']]);
				}
				$shifts = $editingSchedule->obtainShifts();
				$editingSchedule->removeAllShifts();
				$sortedShifts = [];
				foreach ($shifts as $shift)
				{
					$key = $shift->getWorkTimeStart();
					while (in_array($key, array_keys($sortedShifts), true))
					{
						$key = $key + 1;
					}
					$sortedShifts[$key] = $shift;
				}
				ksort($sortedShifts);
				foreach ($sortedShifts as $sortedShift)
				{
					$editingSchedule->addToShifts($sortedShift);
				}
			}
		}
		else
		{
			if (!$this->userPermissionsManager->canCreateSchedule())
			{
				return $this->showError(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_ERROR_SCHEDULE_CREATE_ACCESS_DENIED'));
			}
		}
		$this->arResult['hintWorktimeRestrictionMaxStartOffset'] = Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_HINT_RESTRICTION_MAX_START_OFFSET');
		$this->arResult['hintExactStartEndDay'] = Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_HINT_EXACT_START_END_DAY');
		$this->arResult['hintRelativeStartEndDay'] = Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_HINT_RELATIVE_START_END_DAY');
		$this->arResult['hintOffsetStartEndDay'] = Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_HINT_OFFSET_START_END_DAY');
		$this->arResult['hintMinDayDuration'] = Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_HINT_MIN_DAY_DURATION');
		$this->arResult['hintHoursLackForPeriod'] = Loc::getMessage('TIMEMAN_SCHEDULE_HOURS_LACK_FOR_PERIOD_HINT');
		$this->arResult['hintEditDay'] = Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_HINT_EDIT_DAY');
		if ($this->arResult['ENTITY_CODE'])
		{
			if (EntityCodesHelper::isUser($this->arResult['ENTITY_CODE']))
			{
				$this->arResult['ENTITY_TYPE_USER'] = true;
				$user = $this->scheduleRepository->getUsersBaseQuery()
					->where('ID', EntityCodesHelper::getUserId($this->arResult['ENTITY_CODE']))
					->exec()
					->fetch();
				if (!$user)
				{
					return $this->showError(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_ERROR_SCHEDULE_NOT_FOUND'));
				}
				$this->arResult['ENTITY_NAME'] = UserHelper::getInstance()->getFormattedName($user);
			}
			elseif (EntityCodesHelper::isDepartment($this->arResult['ENTITY_CODE']))
			{
				$this->arResult['ENTITY_TYPE_USER'] = false;
				$arDepartmentsData = CIntranetUtils::getDepartmentsData([EntityCodesHelper::getDepartmentId($this->arResult['ENTITY_CODE'])]);
				if ($arDepartmentsData !== false && !empty($arDepartmentsData))
				{
					$this->arResult['ENTITY_NAME'] = reset($arDepartmentsData);
				}
			}
		}
		if ($editingSchedule && $editingSchedule->getCalendar() && $editingSchedule->getCalendar()->getParentCalendarId() > 0)
		{
			$parentCalendar = $this->calendarRepository->findByIdWithExclusions($editingSchedule->getCalendar()->getParentCalendarId());
			if ($parentCalendar)
			{
				$editingSchedule->getCalendar()->setParentCalendar($parentCalendar);
			}
		}
		$scheduleForm = new ScheduleForm($editingSchedule);
		$this->arResult['scheduleForm'] = $scheduleForm;
		$this->arResult['selectedAssignmentCodes'] = $scheduleForm->assignments;
		$this->arResult['selectedAssignmentCodesExcluded'] = $scheduleForm->assignmentsExcluded;

		if ($this->arResult['VIOLATIONS_ONLY'])
		{
			$this->arResult['canUpdatePersonalViolations'] = $this->userPermissionsManager->canUpdateViolationRules($this->arResult['ENTITY_CODE']);
			if ($editingSchedule->isFlextime())
			{
				return $this->showError(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_ERROR_NO_VIOLATION_CONTROL'));
			}
			$foundByHierarchy = false;
			$violationRules = $this->violationRepository->findByScheduleIdEntityCode($this->arResult['SCHEDULE_ID'], $this->arResult['ENTITY_CODE']);
			if (!$violationRules)
			{
				$violationRules = $this->violationRepository->findFirstByScheduleIdAndEntityCode($this->arResult['SCHEDULE_ID'], $this->arResult['ENTITY_CODE']);
				$foundByHierarchy = true;
			}
			if (!$violationRules)
			{
				$violationRules = ViolationRules::create($editingSchedule->getId());
			}
			$this->arResult['violationForm'] = new ViolationForm($violationRules);
			if ($foundByHierarchy)
			{
				$this->arResult['violationForm']->id = null;
			}
			$this->includeComponentTemplate('violations_only');
			return;
		}
		$this->arResult['shiftWorkdaysOptions'] = ShiftTable::getWorkdaysOptions();
		$this->arResult['canUpdateSchedule'] = $this->userPermissionsManager->canUpdateSchedule($this->arResult['SCHEDULE_ID']);
		$this->fillCalendarsData($scheduleForm);
		if ($this->arResult['isNewSchedule'])
		{
			foreach ($this->arResult['calendarTemplates'] as $calendarTemplate)
			{
				if ($calendarTemplate['isCurrentCountry'] ?? false)
				{
					$scheduleForm->calendarForm->parentId = $calendarTemplate['id'];
					$scheduleForm->calendarForm->setDates(CalendarFormHelper::convertDatesToDbFormat($calendarTemplate['exclusions']));
				}
			}
			$shiftWorkdaysOption = array_keys($this->arResult['shiftWorkdaysOptions']);
			$scheduleForm->getShiftForms()[0]->workDays = reset($shiftWorkdaysOption);

			if (!DependencyManager::getInstance()->getScheduleRepository()->querySchedulesForAllUsers()->exec()->fetch() !== false)
			{
				$scheduleForm->assignments = ['UA'];
			}
		}

		$this->fillScheduleAssignmentsParams($scheduleForm);

		$this->arResult['feedbackParams'] = [
			'ID' => 'timeman-schedule',
			'FORMS' => [
				['zones' => ['com.br'], 'id' => '136', 'lang' => 'br', 'sec' => 'v4qdlt'],
				['zones' => ['es'], 'id' => '134', 'lang' => 'la', 'sec' => 'urn3tp'],
				['zones' => ['de'], 'id' => '132', 'lang' => 'de', 'sec' => 'm3653b'],
				['zones' => ['ua'], 'id' => '128', 'lang' => 'ua', 'sec' => '8jxhur'],
				['zones' => ['ru', 'by', 'kz'], 'id' => '126', 'lang' => 'ru', 'sec' => 'ft3wie'],
				['zones' => ['en'], 'id' => '130', 'lang' => 'en', 'sec' => 'ou6ezy'],
			],
			'PRESETS' => [],
		];

		$this->arResult['weekDays'][1] = Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_MON');
		$this->arResult['weekDays'][2] = Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_TUE');
		$this->arResult['weekDays'][3] = Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_WED');
		$this->arResult['weekDays'][4] = Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_THU');
		$this->arResult['weekDays'][5] = Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_FRI');
		$this->arResult['weekDays'][6] = Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_SAT');
		$this->arResult['weekDays'][7] = Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_SUN');
		$shiftWorkdaysOption = array_values($this->arResult['shiftWorkdaysOptions']);
		$this->arResult['customWorkdaysText'] = end($shiftWorkdaysOption);
		$this->arResult['WEEKS_PERIODS'] = [ScheduleTable::REPORT_PERIOD_WEEK, ScheduleTable::REPORT_PERIOD_TWO_WEEKS];
		/** @noinspection PhpVoidFunctionResultUsedInspection */
		$this->includeComponentTemplate();
	}

	private function addError($errorMessage)
	{
		$this->arResult['errorMessages'][] = $errorMessage;
	}

	private function showError($errorMessage)
	{
		$this->addError($errorMessage);
		$this->includeComponentTemplate('error');
	}

	private function fillCalendarsData(ScheduleForm $scheduleForm)
	{
		$this->arResult['calendarTemplates'] = [];
		$systemCalendars = $this->calendarRepository->findByCodesWithExclusions(CalendarTable::getSystemCalendarCodes());
		foreach ($systemCalendars as $systemCalendar)
		{
			$name = Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_SYSTEM_CALENDAR_HOLIDAYS_SINGLE_TITLE_'.mb_strtoupper($systemCalendar->getSystemCode()));
			$nameList = Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_SYSTEM_CALENDAR_HOLIDAYS_LIST_TITLE_'.mb_strtoupper($systemCalendar->getSystemCode()));
			if (empty($name) || empty($nameList))
			{
				continue;
			}
			$this->arResult['calendarTemplates'][] = [
				'nameSingle' => $name,
				'nameList' => $nameList,
				'title' => Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_CALENDAR_RUS_HOLIDAYS_HINT'),
				'exclusions' => CalendarFormHelper::convertDatesToViewFormat($systemCalendar->obtainFinalExclusions()),
				'id' => $systemCalendar->getId(),
				'systemCode' => $systemCalendar->getSystemCode(),
				'isCurrentCountry' => $this->isCurrentCountry($systemCalendar),
			];
		}
		\Bitrix\Main\Type\Collection::sortByColumn(
			$this->arResult['calendarTemplates'],
			['name' => SORT_ASC]
		);

		$schedules = DependencyManager::getInstance()
			->getScheduleRepository()
			->getActiveSchedulesQuery()
			->addSelect('ID')
			->addSelect('CALENDAR_ID')
			->where('CALENDAR_ID', '>', 0)
			->addGroup('CALENDAR_ID')
			->addOrder('ID')
			->setLimit(70);
		if ($scheduleForm->calendarForm->calendarId)
		{
			$schedules->where('CALENDAR_ID', '!=', $scheduleForm->calendarForm->calendarId);
		}
		$schedules = $schedules->exec()->fetchAll();
		if ($schedules)
		{
			$schedulesIds = array_column($schedules, 'CALENDAR_ID');
			if ($calParentId = $scheduleForm->calendarForm->parentId)
			{
				$schedulesIds[] = $calParentId;
			}
			/** @var EO_Calendar_Collection $calendars */
			$calendars = \Bitrix\Timeman\Model\Schedule\Calendar\CalendarTable::query()
				->addSelect('*')
				->addSelect('EXCLUSIONS')
				->addSelect('SCHEDULE.NAME')
				->addSelect('SCHEDULE.ID')
				->registerRuntimeField((new Reference('SCHEDULE', ScheduleTable::class,
					Join::on('this.ID', 'ref.CALENDAR_ID')
				))->configureJoinType('INNER')
				)
				->whereIn('ID', $schedulesIds)
				->where('PARENT_CALENDAR_ID', 0)
				->exec()
				->fetchCollection();

			foreach ($calendars as $calendar)
			{
				$this->arResult['calendarTemplates'][] = [
					'name' => htmlspecialcharsbx($calendar->get('SCHEDULE')->getName()),
					'title' => Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_CALENDAR_HOLIDAYS_TEMPLATE_HINT'),
					'exclusions' => CalendarFormHelper::convertDatesToViewFormat($calendar->obtainFinalExclusions()),
					'id' => htmlspecialcharsbx($calendar->getId()),
					'systemCode' => '',
				];
			}
		}
	}

	/**
	 * @param ScheduleForm $form
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function fillScheduleAssignmentsParams($form)
	{
		$this->arResult['assignmentsMap'] = [];
		if (!$form->getSchedule())
		{
			return;
		}
		$this->arResult['assignmentsMap'] = (new ScheduleFormHelper())
			->calculateSchedulesMapBySchedule($form->getSchedule(), true);
	}

	/**
	 * @param \Bitrix\Timeman\Model\Schedule\Calendar\Calendar $systemCalendar
	 */
	private function isCurrentCountry($systemCalendar)
	{
		if (Loader::includeModule("bitrix24"))
		{
			return \CBitrix24::getPortalZone() === $systemCalendar->getSystemCode();
		}
		return false;
	}
}