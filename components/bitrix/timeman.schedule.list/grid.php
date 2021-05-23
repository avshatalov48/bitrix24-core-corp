<?php
namespace Bitrix\Timeman\Component\ScheduleList;

use Bitrix\Main\Grid\Options;
use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Helper\Form\Schedule\ScheduleFormHelper;
use Bitrix\Timeman\Model\Schedule;
use Bitrix\Timeman\Repository\Schedule\ScheduleRepository;
use Bitrix\Timeman\Security\UserPermissionsManager;
use Bitrix\Timeman\Service\DependencyManager;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

class Grid
{
	private $id;
	private $headers;
	/** @var ScheduleRepository $scheduleRepository */
	private $scheduleRepository;
	private $navigation;
	/**
	 * @var Options
	 */
	private $gridOptions;
	/** @var UserPermissionsManager */
	private $userPermissionManager;

	/**
	 * @return mixed
	 */
	public function getId()
	{
		return $this->id;
	}

	public function __construct($id, $scheduleRepository, $userPermissionManager)
	{
		$this->id = $id;
		$this->scheduleRepository = $scheduleRepository;
		$this->userPermissionManager = $userPermissionManager;
		$this->headers = $this->getHeaders();
	}

	public function getSortableHeaders()
	{
		return ['ID', 'NAME', 'SCHEDULE_TYPE', 'REPORT_PERIOD',];
	}

	public function getHeaders()
	{
		if ($this->headers === null)
		{
			$this->headers = [
				['id' => 'ID', 'name' => Loc::getMessage('TM_SCHEDULE_LIST_COLUMN_ID'), 'default' => false, 'sort' => 'ID'],
				['id' => 'NAME', 'name' => Loc::getMessage('TM_SCHEDULE_LIST_COLUMN_NAME'), 'default' => true, 'sort' => 'NAME'],
				['id' => 'USER_COUNT', 'name' => Loc::getMessage('TM_SCHEDULE_LIST_COLUMN_USER_COUNT'), 'default' => true],
				['id' => 'SCHEDULE_TYPE', 'name' => Loc::getMessage('TM_SCHEDULE_LIST_COLUMN_TYPE'), 'default' => true, 'sort' => 'SCHEDULE_TYPE'],
				['id' => 'REPORT_PERIOD', 'name' => Loc::getMessage('TM_SCHEDULE_LIST_COLUMN_PERIOD'), 'default' => true, 'sort' => 'REPORT_PERIOD'],
			];
		}

		return $this->headers;
	}

	public function getGridOptions()
	{
		return $this->gridOptions = $this->gridOptions ?: new Options($this->id);
	}

	public static function getPageSizes()
	{
		$res = [];
		foreach ([5, 10, 15, 20, 30, 50] as $index)
		{
			$res[] = ['NAME' => $index, 'VALUE' => $index];
		}
		return $res;
	}

	public function getNavigation()
	{
		if (!$this->navigation)
		{
			$navData = $this->getGridOptions()->getNavParams(['nPageSize' => 25]);
			$this->navigation = new \Bitrix\Main\UI\PageNavigation($this->id);
			$this->navigation
				->setPageSize($navData['nPageSize'])
				->allowAllRecords(false)
				->setPageSizes(static::getPageSizes())
				->initFromUri();
		}
		return $this->navigation;
	}

	/**
	 * Get columns value
	 *
	 * @return array
	 */
	public function getRows($schedules)
	{
		$items = [];
		$scheduleFormHelper = new ScheduleFormHelper();
		foreach ($schedules as $schedule)
		{
			/** @var Schedule\Schedule $schedule */
			$fields['~ID'] = $schedule->getId();
			$fields['ID'] = intval($fields['~ID']);

			$fields['~NAME'] = $schedule->getName();
			$fields['NAME'] = htmlspecialcharsbx($fields['~NAME']);

			$userCount = DependencyManager::getInstance()->getScheduleProvider()->getUsersCount($schedule);
			$fields['~USER_COUNT'] = $userCount >= 0 ? $userCount : '';
			$fields['USER_COUNT'] = $fields['~USER_COUNT'];

			$fields['~SCHEDULE_TYPE'] = $scheduleFormHelper->getFormattedType($schedule->getScheduleType());
			$fields['SCHEDULE_TYPE'] = htmlspecialcharsbx($fields['~SCHEDULE_TYPE']);

			$fields['~REPORT_PERIOD'] = $scheduleFormHelper->getFormattedPeriod($schedule->getReportPeriod());
			$fields['REPORT_PERIOD'] = htmlspecialcharsbx($fields['~REPORT_PERIOD']);

			$fields['CAN_EDIT'] = $this->userPermissionManager->canUpdateSchedule($schedule->getId());
			$fields['CAN_DELETE'] = $this->userPermissionManager->canDeleteSchedule($schedule->getId());
			$fields['CAN_READ_SHIFT_PLAN'] = $this->userPermissionManager->canReadShiftPlan($schedule->getId());
			$fields['CAN_READ_SCHEDULE'] = $this->userPermissionManager->canReadSchedule($schedule->getId());
			$fields['CAN_UPDATE_SCHEDULE'] = $this->userPermissionManager->canUpdateSchedule($schedule->getId());
			$fields['CAN_READ_WORKTIME'] = true;
			$fields['PATH_TO_EDIT'] = $fields['PATH_TO_DELETE'] = '';

			$fields['IS_SHIFTED'] = (bool)$schedule->isShifted();

			$items[] = $fields;
		}
		return $items;
	}

	public function getPageSize()
	{
		return 30;
	}
}