<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 */

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Helper;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Ui\Filter;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\TaskLimit;
use Bitrix\Tasks\Util\User;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksToolbarComponent extends TasksBaseComponent
{
	protected $gridOptions;
	protected $listState;
	protected $listCtrl;

	protected function checkParameters()
	{
		parent::checkParameters();

		$arParams =& $this->arParams;

		static::tryParseStringParameter($arParams['DEFAULT_ROLEID'], 'view_all');
		static::tryParseStringParameter($arParams['SHOW_TOOLBAR'], 'N');
		static::tryParseStringParameter($arParams['PROJECT_VIEW'], 'N');

		if ($arParams['GROUP_ID'] > 0)
		{
			$arParams['SHOW_TOOLBAR'] = 'N';
		}

		$arParams['OWNER_ID'] = (int)$arParams['USER_ID'];
		$arParams['USER_ID'] = User::getId();
	}

	protected function doPreAction()
	{
		parent::doPreAction();

		$this->listState = Filter\Task::getListStateInstance();
		$this->listCtrl = Filter\Task::getListCtrlInstance();
		$this->listCtrl->useState($this->listState);

		$isNotSprint = !isset($this->arParams['SPRINT_SELECTED']) || $this->arParams['SPRINT_SELECTED'] !== 'Y';
		$showCounters = $isNotSprint && $this->hasAccessToCounters();
		$showSpotlightSimpleCounters = $this->showSpotlight('simple_counters')
			&& $this->arParams['SHOW_VIEW_MODE'] === 'Y';

		$this->arResult['IS_NOT_SPRINT'] = $isNotSprint;
		$this->arResult['SHOW_COUNTERS'] = $showCounters;
		$this->arResult['SPOTLIGHT_SIMPLE_COUNTERS'] = $showSpotlightSimpleCounters;

		$this->arResult['VIEW_LIST'] = $this->getViewList();
		$this->arResult['TASK_LIMIT_EXCEEDED'] = TaskLimit::isLimitExceeded();

		$this->arResult['USER_ID'] = $this->arParams['USER_ID'];
		$this->arResult['OWNER_ID'] = $this->arParams['OWNER_ID'];

		if ($showCounters)
		{
			$this->arResult['COUNTERS'] = $this->getCounters();
		}
	}

	/**
	 * @return mixed
	 */
	protected function getViewList()
	{
		$viewState = $this->getViewState();

		$viewList = [];

		if (array_key_exists('VIEW_MODE_LIST', $this->arParams) && is_array($this->arParams['VIEW_MODE_LIST']) && !empty($this->arParams['VIEW_MODE_LIST']))
		{
			foreach ($this->arParams['VIEW_MODE_LIST'] as $mode)
			{
				if (array_key_exists($mode, $viewState['VIEWS']))
				{
					$viewList[$mode] = $viewState['VIEWS'][$mode];
				}
			}
		}
		else
		{
			$viewList = $viewState['VIEWS'];
		}

		return $viewList;
	}

	/**
	 * @return array
	 */
	private function getViewState(): array
	{
		static $viewState = null;
		if ($viewState === null)
		{
			$viewState = $this->listState->getState();
		}

		return $viewState;
	}

	/**
	 * @return bool
	 */
	private function hasAccessToCounters(): bool
	{
		$userId = $this->arResult['USER_ID'];
		$ownerId = $this->arResult['OWNER_ID'];

		return $userId === $ownerId
			|| User::isSuper($userId)
			|| CTasks::IsSubordinate($ownerId, $userId);
	}

	/**
	 * @return string
	 */
	private function getFilterRole(): string
	{
		$filterInstance = Helper\Filter::getInstance($this->arParams['OWNER_ID'], $this->arParams['GROUP_ID']);
		$filterOptions = $filterInstance->getOptions();
		$filter = $filterOptions->getFilter();

		return (array_key_exists('ROLEID', $filter) ? $filter['ROLEID'] : Counter\Role::ALL);
	}

	/**
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\DB\SqlQueryException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	protected function getCounters(): array
	{
		$counterInstance = Counter::getInstance($this->arParams['OWNER_ID']);
		return $counterInstance->getCounters($this->getFilterRole(), (int) $this->arParams['GROUP_ID']);
	}
}