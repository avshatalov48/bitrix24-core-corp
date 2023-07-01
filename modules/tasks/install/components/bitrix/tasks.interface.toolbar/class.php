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
		static::tryParseStringParameter($arParams['SCOPE'], '');
		static::tryParseStringParameter($arParams['FILTER_FIELD'], 'PROBLEM');

		if (
			isset($arParams['GROUP_ID'])
			&& $arParams['GROUP_ID'] > 0
		)
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

		$this->arResult['SHOW_COUNTERS'] = $this->hasAccessToCounters();
		$this->arResult['SPOTLIGHT_SIMPLE_COUNTERS'] = (
			isset($this->arParams['SHOW_VIEW_MODE'])
			&& $this->arParams['SHOW_VIEW_MODE'] === 'Y'
			&& $this->showSpotlight('simple_counters')
		);
		$this->arResult['VIEW_LIST'] = $this->getViewList();
		$this->arResult['TASK_LIMIT_EXCEEDED'] = TaskLimit::isLimitExceeded();

		$this->arResult['USER_ID'] = $this->arParams['USER_ID'] ?? null;
		$this->arResult['OWNER_ID'] = $this->arParams['OWNER_ID'] ?? null;
		$this->arResult['GROUP_ID'] = $this->arParams['GROUP_ID'] ?? null;
		$this->arResult['COUNTERS'] = ($this->arParams['COUNTERS'] ?? []);

		$this->arResult['ROLE'] = $this->getFilterRole();
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
		$userId = $this->arParams['USER_ID'];
		$ownerId = $this->arParams['OWNER_ID'];

		return
			$userId === $ownerId
			|| User::isSuper($userId)
			|| CTasks::IsSubordinate($ownerId, $userId)
		;
	}

	/**
	 * @return string
	 */
	private function getFilterRole(): string
	{
		$filterInstance = Helper\Filter::getInstance($this->arResult['OWNER_ID'], $this->arResult['GROUP_ID']);
		$filterOptions = $filterInstance->getOptions();
		$filter = $filterOptions->getFilter();

		$possibleRoles = Counter\Role::getRoles();
		$role = Counter\Role::ALL;

		if (
			array_key_exists('ROLEID', $filter)
			&& array_key_exists($filter['ROLEID'], $possibleRoles)
		)
		{
			$role = $filter['ROLEID'];
		}

		return $role;
	}
}