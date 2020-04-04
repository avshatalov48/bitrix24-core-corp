<?php
namespace Bitrix\Timeman\Component\SchedulePlan;

use \Bitrix\Main;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Timeman;
use Bitrix\Timeman\Repository\Schedule\ScheduleRepository;
use Bitrix\Timeman\Repository\Worktime\WorktimeRepository;
use Bitrix\Timeman\Service\DependencyManager;

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

class TimemanWorktimeStatsComponent extends Timeman\Component\BaseComponent
{
	/** @var array */
	protected $schedule;
	private $gridId = 'TM_WORKTIME_STATS_GRID';
	/** @var ScheduleRepository */
	private $scheduleRepository;
	/** @var WorktimeRepository */
	private $worktimeRepository;
	/** @var Timeman\Security\UserPermissionsManager */
	private $userPermissionsManager;

	public function __construct(\CBitrixComponent $component = null)
	{
		$this->scheduleRepository = DependencyManager::getInstance()->getScheduleRepository();
		$this->worktimeRepository = DependencyManager::getInstance()->getWorktimeRepository();
		global $USER;
		$this->userPermissionsManager = DependencyManager::getInstance()->getUserPermissionsManager($USER);
		parent::__construct($component);
	}

	public function onPrepareComponentParams($arParams)
	{
		$this->arResult['timeHelper'] = Timeman\Helper\TimeHelper::getInstance();
		$this->arResult['SCHEDULE_ID'] = $this->getFromParamsOrRequest($arParams, 'SCHEDULE_ID', 'int');

		return $arParams;
	}

	public function executeComponent()
	{
		if ($this->arResult['SCHEDULE_ID'] &&
			!($this->arResult['SCHEDULE'] = DependencyManager::getInstance()
				->getScheduleRepository()
				->findById($this->arResult['SCHEDULE_ID'])))
		{
			return showError('Schedule not found.');
		}
		$this->arResult['GRID_ID'] = $this->arResult['SCHEDULE_ID'] ? $this->gridId . '_' . $this->arResult['SCHEDULE_ID'] : $this->gridId;
		$this->arResult['SHOW_ADD_SCHEDULE_BTN'] = $this->userPermissionsManager->canCreateSchedule();
		$this->includeComponentTemplate();
	}
}
