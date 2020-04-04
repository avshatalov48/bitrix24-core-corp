<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 */

/** !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */
/** This is alfa version of component! Don't use it! */
/** !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Ui\Filter;
use Bitrix\Tasks\Util\Result;
use Bitrix\Tasks\Internals;
use \Bitrix\Tasks\Util\User;

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

		static::tryParseStringParameter($arParams[ 'DEFAULT_ROLEID' ], 'view_all');

		static::tryParseStringParameter($arParams[ 'SHOW_TOOLBAR' ], 'N');
		if($arParams['GROUP_ID'] > 0)
		{
			$arParams[ 'SHOW_TOOLBAR' ] = 'N';
		}
	}

	protected function doPreAction()
	{
		parent::doPreAction();

		$this->listState = Filter\Task::getListStateInstance();
		$this->listCtrl = Filter\Task::getListCtrlInstance();
		$this->listCtrl->useState($this->listState);

		$this->arResult[ 'VIEW_LIST' ] = $this->getViewList();

		//tmp
		if (isset($this->arResult['VIEW_LIST']['VIEW_MODE_TIMELINE']))
		{
			unset($this->arResult['VIEW_LIST']['VIEW_MODE_TIMELINE']);
		}
		$this->arResult[ 'COUNTERS' ] = $this->getCounters();
	}

	protected function getViewList()
	{
		$viewState = self::getViewState();
		return $viewState[ 'VIEWS' ];
	}

	private function getViewState()
	{
		static $viewState = null;
		if (is_null($viewState))
		{
			$viewState = $this->listState->getState();
		}

		return $viewState;
	}

	protected function getCounters()
	{
		if ($this->arParams['GROUP_ID'] > 0)
		{
			$counterInstance = Internals\Counter\Group::getInstance($this->arParams['GROUP_ID']);

			return $counterInstance->getCounters();
		}
		else
		{
			$counterInstance = Internals\Counter::getInstance($this->arParams['USER_ID'], $this->arParams['GROUP_ID']);

			$filterInstance = \Bitrix\Tasks\Helper\Filter::getInstance(
				$this->arParams['USER_ID'],
				$this->arParams['GROUP_ID']
			);
			$filterOptions = $filterInstance->getOptions();
			$filter = $filterOptions->getFilter();
			if (!array_key_exists('ROLEID', $filter))
			{
				$role = \Bitrix\Tasks\Internals\Counter\Role::ALL;
			}
			else
			{
				$role = $filter['ROLEID'];
			}

			return $counterInstance->getCounters($role);
		}
	}
}