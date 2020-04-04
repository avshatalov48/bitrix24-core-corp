<?php
namespace Bitrix\Timeman\Component\SchedulePlan;

use \Bitrix\Main;
use Bitrix\Main\Application;
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

		$this->initCookieMenuParam('SHOW_START_FINISH');
		$this->initCookieMenuParam('SHOW_STATS_COLUMNS');
		$this->initCookieMenuParam('SHOW_VIOLATIONS_COMMON');
		$this->initCookieMenuParam('SHOW_VIOLATIONS_PERSONAL', null, true);

		if ($this->arResult['SHOW_VIOLATIONS_COMMON'] === true && $this->arResult['SHOW_VIOLATIONS_PERSONAL'] === true)
		{
			$this->initCookieMenuParam('SHOW_VIOLATIONS_COMMON', false);
		}
		return $arParams;
	}

	private function setCookie($name, $value)
	{
		if ($this->getCookie($name) === $value)
		{
			return;
		}
		$cookie = new \Bitrix\Main\Web\Cookie($name, $value, null);
		$cookie->setHttpOnly(false);
		$cookie->setPath($this->getRequest()->getRequestedPage());
		Main\Context::getCurrent()->getResponse()->addCookie($cookie);
	}

	private function getCookie($name)
	{
		$raw = Application::getInstance()->getContext()->getRequest()->getCookieRaw($name);
		return $raw === 'true' ? true : ($raw === 'false' ? false : null);
	}

	public function executeComponent()
	{
		if ($this->arResult['SCHEDULE_ID'] &&
			!($this->arResult['SCHEDULE'] = DependencyManager::getInstance()
				->getScheduleRepository()
				->findById($this->arResult['SCHEDULE_ID'])))
		{
			return showError('SCHEDULE_ID must not be null. Schedule not found.');
		}
		$this->arResult['GRID_ID'] = $this->arResult['SCHEDULE_ID'] ? $this->gridId . '_' . $this->arResult['SCHEDULE_ID'] : $this->gridId;
		$schedules = $this->scheduleRepository->getActiveSchedulesQuery()
			->addSelect('ID')
			->addSelect('NAME')
			->setLimit(20)
			->exec()
			->fetchAll();
		$this->arResult['schedulesData'] = [];
		foreach ($schedules as $schedule)
		{
			if ($this->userPermissionsManager->canReadSchedule($schedule['ID']))
			{
				$this->arResult['schedulesData'][] = [
					'id' => $schedule['ID'],
					'name' => $schedule['NAME'],
				];
			}
		}

		$shiftedSchedules = $this->scheduleRepository->getActiveSchedulesQuery()
			->addSelect('ID')
			->addSelect('NAME')
			->where('SCHEDULE_TYPE', Timeman\Model\Schedule\ScheduleTable::SCHEDULE_TYPE_SHIFT)
			->setLimit(20)
			->exec()
			->fetchAll();
		$this->arResult['SHIFTED_SCHEDULES'] = [];
		foreach ($shiftedSchedules as $shiftedSchedule)
		{
			if ($this->userPermissionsManager->canUpdateShiftPlan($shiftedSchedule['ID']))
			{
				$this->arResult['SHIFTED_SCHEDULES'][] = $shiftedSchedule;
			}
		}
		$this->arResult['gridOptions'] = [
			'SHOW_START_FINISH' => $this->arResult['SHOW_START_FINISH'],
			'SHOW_STATS_COLUMNS' => $this->arResult['SHOW_STATS_COLUMNS'],
			'SHOW_VIOLATIONS_COMMON' => $this->arResult['SHOW_VIOLATIONS_COMMON'],
			'SHOW_VIOLATIONS_PERSONAL' => $this->arResult['SHOW_VIOLATIONS_PERSONAL'],
		];
		$this->arResult['SHOW_ADD_SCHEDULE_BTN'] = $this->userPermissionsManager->canCreateSchedule();

		$this->arResult['canDeleteSchedule'] = $this->userPermissionsManager->canDeleteSchedules();
		$this->arResult['canUpdateSchedule'] = $this->userPermissionsManager->canUpdateSchedules();

		$this->includeComponentTemplate();
	}

	private function initCookieMenuParam($name, $value = null, $defaultValue = false)
	{
		if (!is_null($value))
		{
			$this->arResult[$name] = $value;
			$this->setCookie($name, $this->arResult[$name]);
		}
		else
		{
			if ($this->getRequest()->get($name) !== null)
			{
				$this->arResult[$name] = $this->getRequest()->get($name) === 'Y';
				$this->setCookie($name, $this->arResult[$name]);
			}
			else
			{
				$this->arResult[$name] = $this->getCookie($name);
				if ($this->arResult[$name] === null)
				{
					$this->arResult[$name] = $defaultValue;
					$this->setCookie($name, $this->arResult[$name]);
				}
			}
		}
	}
}
