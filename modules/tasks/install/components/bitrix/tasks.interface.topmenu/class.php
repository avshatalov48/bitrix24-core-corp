<?
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

/** !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */
/** This is alfa version of component! Don't use it! */

/** !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\Intranet;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Util\User;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksTopmenuComponent extends TasksBaseComponent
{
	protected function checkParameters()
	{
		$arParams = &$this->arParams;

		//		static::tryParseStringParameter($arParams[ 'SECTION_URL_PREFIX' ], '');
		//		static::tryParseStringParameter($arParams[ 'PATH_TO_REPORTS' ], $arParams[ 'SECTION_URL_PREFIX' ] . 'report/');
		//		static::tryParseStringParameter($arParams[ 'PATH_TO_DEPARTMENTS' ], $arParams[ 'SECTION_URL_PREFIX' ] . 'departments/');

		static::tryParseStringParameter($arParams['GROUP_ID'], 0);
		static::tryParseStringParameter($arParams['SHOW_SECTION_PROJECTS'], 'Y');
		static::tryParseStringParameter($arParams['SHOW_SECTION_TEMPLATES'], 'Y');
		static::tryParseStringParameter($arParams['SHOW_SECTION_REPORTS'], 'Y');
		static::tryParseStringParameter($arParams['SHOW_SECTION_MANAGE'], 'A');

		static::tryParseStringParameter($arParams['MARK_SECTION_PROJECTS'], 'N');
		static::tryParseStringParameter($arParams['MARK_SECTION_PROJECTS_LIST'], 'N');
		static::tryParseStringParameter($arParams['MARK_ACTIVE_ROLE'], 'N');
		static::tryParseStringParameter($arParams['MARK_SECTION_MANAGE'], 'N');
		static::tryParseStringParameter($arParams['MARK_SECTION_REPORTS'], 'N');
		static::tryParseStringParameter($arParams['MARK_SECTION_EMPLOYEE_PLAN'], 'N');
		static::tryParseStringParameter($arParams['MARK_SPECIAL_PRESET'], 'N');
		static::tryParseStringParameter($arParams['MARK_SECTION_ALL'], 'N');
		static::tryParseStringParameter($arParams['MARK_TEMPLATES'], 'N');
		static::tryParseStringParameter($arParams['PROJECT_VIEW'], 'N');
		static::tryParseStringParameter($arParams['LOGGED_USER_ID'], User::getId());
		static::tryParseStringParameter(
			$arParams['TASKS_GROUP_CREATE_URL_TEMPLATE'],
			'/company/personal/user/#user_id#/groups/create/?firstRow=project'
		);

		return parent::checkParameters();
	}

	protected function doPostAction()
	{
		if ($this->arParams['GROUP_ID'] > 0)
		{
			// $this->arParams['SHOW_SECTION_PROJECTS'] = 'N';
			$this->arParams['SHOW_SECTION_TEMPLATES'] = 'N';
		}

		$this->arParams['SHOW_SECTION_PROJECTS'] = ($this->arParams['SHOW_SECTION_PROJECTS'] == 'Y' &&
													$this->arParams['USER_ID'] == $this->userId) ? 'Y' : 'N';

		if (!CModule::IncludeModule('report'))
		{
			$this->arParams['SHOW_SECTION_REPORTS'] = 'N';
		}

		// Show this section ONLY if given user is head of department
		// and logged in user is admin or given user or manager of given user
		if ($this->arParams['SHOW_SECTION_MANAGE'] == 'A')
		{
			$this->arParams['SHOW_SECTION_MANAGE'] = 'N';

			if ($this->isAccessToCounters())
			{
				if (Intranet\User::isDirector($this->arParams['USER_ID']))
				{
					$this->arParams['SHOW_SECTION_MANAGE'] = 'Y';
				}
			}
		}
		if ($this->arParams['SHOW_SECTION_MANAGE'] == 'Y' && $this->arParams['GROUP_ID'] > 0)
		{
			$this->arParams['SHOW_SECTION_MANAGE'] = 'N';
		}

		$this->arResult['EFFECTIVE_COUNTER'] = Counter::getInstance($this->userId)->get(Counter\Name::EFFECTIVE);//\Bitrix\Tasks\Internals\Effective::getMiddleCounter($this->userId);

		$this->arResult['SECTION_MANAGE_COUNTER'] = 0;
		if ($this->arParams['SHOW_SECTION_MANAGE'] == 'Y' && $this->isAccessToCounters())
		{
			$this->arResult['SECTION_MANAGE_COUNTER'] = CUserCounter::GetValue(
				$this->arParams['USER_ID'],
				'departments_counter'
			);
		}

		$this->arResult['VIEW_STATE'] = \CTaskListState::getInstance($this->arParams['USER_ID'])->getState();

		return parent::doPostAction();
	}

	private function isAccessToCounters(): bool
	{
		return (int)$this->arParams['USER_ID'] === (int)$this->userId
			|| User::isSuper()
			|| CTasks::IsSubordinate($this->arParams['USER_ID'], $this->userId);
	}

	protected function doPreAction()
	{
		$this->arResult['USER_ID'] = (int)$this->userId;
		$this->arResult['OWNER_ID'] = (int)$this->arParams['USER_ID'];

		$this->arResult['ROLES'] = $this->getRoles();
		$this->arResult['TOTAL'] = Counter::getInstance($this->arParams['USER_ID'])->get(Counter\Name::TOTAL);

		return true;
	}

	private function getRoles()
	{
		$roles = array();
		$countersId = $this->roleCodeToCounterId();
		foreach (Counter\Role::getRoles() as $roleId => $role)
		{
			$roles[$roleId] = array(
				'TEXT' => $role['TITLE'],
				'COUNTER' => $this->getCounter($role['CODE']),
				'COUNTER_ID' => 'tasks_'.$countersId[$role['CODE']],
				'IS_ACTIVE' => false,
				'HREF' => $this->getRoleUrl($role['ID'])
			);
		}

		return $roles;
	}

	private function roleCodeToCounterId()
	{
		return array(
			Counter\Role::AUDITOR => Counter\Name::AUDITOR,
			Counter\Role::ACCOMPLICE => Counter\Name::ACCOMPLICES,
			Counter\Role::RESPONSIBLE => Counter\Name::MY,
			Counter\Role::ORIGINATOR => Counter\Name::ORIGINATOR,
		);
	}

	private function getCounter($roleCode)
	{
		$countersId = $this->roleCodeToCounterId();

		return Counter::getInstance($this->arParams['USER_ID'])->get($countersId[$roleCode]);
	}

	private function getRoleUrl($roleId)
	{
		return 'F_CANCEL=Y&F_STATE=sR'.base_convert($roleId, 10, 32);
	}
}