<?php
namespace Bitrix\Timeman\Component\SchedulePlan;

use \Bitrix\Main;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Timeman;
use Bitrix\Timeman\Repository\Schedule\ScheduleRepository;
use Bitrix\Timeman\Service\DependencyManager;
use Bitrix\Timeman\TimemanUrlManager;

require_once __DIR__ . '/../timeman.worktime.grid/grid.php';

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__FILE__);

if (!Main\Loader::includeModule('timeman'))
{
	showError(Loc::getMessage('TIMEMAN_MODULE_NOT_INSTALLED'));
	return;
}

class TimemanShiftPlanComponent extends Timeman\Component\BaseComponent
{
	/** @var array */
	protected $schedule;
	private $gridId = 'TM_SHIFT_PLAN_GRID';
	/** @var ScheduleRepository $scheduleRepository */
	private $scheduleRepository;
	/** @var \Bitrix\Timeman\Security\UserPermissionsManager */
	private $userPermissionManager;

	public function __construct(\CBitrixComponent $component = null)
	{
		global $USER;
		$this->userPermissionManager = DependencyManager::getInstance()
			->getUserPermissionsManager($USER);
		$this->scheduleRepository = DependencyManager::getInstance()->getScheduleRepository();
		parent::__construct($component);
	}

	public function onPrepareComponentParams($arParams)
	{
		$this->arResult['SCHEDULE_ID'] = $this->getFromParamsOrRequest($arParams, 'SCHEDULE_ID', 'int');

		return $arParams;
	}

	public function executeComponent()
	{
		if (!$this->userPermissionManager->canReadShiftPlan($this->arResult['SCHEDULE_ID']))
		{
			showError(Loc::getMessage('TM_SCHEDULE_SHIFT_PLAN_ACCESS_DENIED'));
			return;
		}
		$schedule = Timeman\Service\DependencyManager::getInstance()->getScheduleRepository()
			->getActiveSchedulesQuery()
			->addSelect('ID')
			->addSelect('NAME')
			->addSelect('SCHEDULE_TYPE')
			->where('ID', $this->arResult['SCHEDULE_ID'])
			->exec()
			->fetchObject();
		if (!$schedule || !$schedule->isShifted())
		{
			showError(Loc::getMessage('TM_SCHEDULE_SHIFT_PLAN_SCHEDULE_NOT_FOUND'));
			return;
		}
		$this->arResult['SCHEDULE'] = $schedule;
		$this->arResult['SCHEDULE_NAME'] = $this->arResult['SCHEDULE']['NAME'];
		$this->arResult['GRID_ID'] = $this->arResult['SCHEDULE_ID'] ? $this->gridId . '_' . $this->arResult['SCHEDULE_ID'] : $this->gridId;
		$this->arResult['canUpdateSchedule'] = $this->userPermissionManager->canUpdateSchedule($this->arResult['SCHEDULE_ID']);
		$this->arResult['SHOW_DELETE_USER_BTN'] = $this->userPermissionManager->canUpdateSchedule($this->arResult['SCHEDULE_ID']);
		$this->arResult['SHOW_ADD_SHIFT_BTN'] = $this->userPermissionManager->canUpdateSchedule($this->arResult['SCHEDULE_ID']);
		if ($this->arResult['canUpdateSchedule'])
		{
			$this->arResult['ADD_SHIFT_LINK'] = DependencyManager::getInstance()->getUrlManager()->getUriTo(TimemanUrlManager::URI_SHIFT_CREATE, ['SCHEDULE_ID' => $this->arResult['SCHEDULE_ID']]);
			$this->arResult['scheduleUpdateLink'] = DependencyManager::getInstance()->getUrlManager()->getUriTo(TimemanUrlManager::URI_SCHEDULE_UPDATE, ['SCHEDULE_ID' => $this->arResult['SCHEDULE_ID']]) . '?hideShiftPlanBtn=Y';
		}
		$this->arResult['SHOW_ADD_USER_BUTTON'] = $this->userPermissionManager->canUpdateSchedule($this->arResult['SCHEDULE_ID']);
		$this->arResult['errorCodeOverlappingPlans'] = \Bitrix\Timeman\Service\Schedule\Result\ShiftPlanServiceResult::ERROR_CODE_OVERLAPPING_SHIFT_PLAN;

		$this->includeComponentTemplate();
	}
}
