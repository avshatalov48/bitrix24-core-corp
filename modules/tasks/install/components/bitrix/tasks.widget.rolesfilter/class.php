<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Main\Application;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksWidgetRolesfilterComponent extends TasksBaseComponent
{
	protected function checkParameters()
	{
		$arParams = &$this->arParams;

		static::tryParseIntegerParameter($arParams['USER_ID'], User::getId());
		static::tryParseStringParameter(
			$arParams['PATH_TO_TASKS'],
			"/company/personal/user/{$arParams['USER_ID']}/tasks/"
		);
		static::tryParseStringParameter(
			$arParams['PATH_TO_TASKS_CREATE'],
			"/company/personal/user/{$arParams['USER_ID']}/tasks/task/edit/0/"
		);

		return $this->errors->checkNoFatals();
	}

	protected function getData()
	{
		$this->arResult['ROLES'] = $this->getRoles();
	}

	private function getRoles()
	{
		$roles = array();
		$countersId = $this->roleCodeToCounterId();
		foreach (Counter\Role::getRoles() as $roleId => $role)
		{
			$roles[$roleId] = array(
				'TITLE' => $role['TITLE'],
				'COUNTER' => $this->getCounter($role['CODE']),
				'COUNTER_VIOLATIONS' => $this->getCounterViolations($role['CODE']),
				'COUNTER_ID' => 'tasks_'.$countersId[$role['CODE']],
				'HREF' => $this->getRoleUrl($role['ID'])
			);
		}

		return $roles;
	}

	private function getCounter($roleCode)
	{
		$types = $this->roleCodeToUserType();
		$userType = $types[ $roleCode ];

				$sql = "
					SELECT 
						COUNT(tm.TASK_ID) as COUNT
					FROM 
						b_tasks_member as tm
						JOIN b_tasks as t ON t.ID = tm.TASK_ID
					WHERE 
						tm.USER_ID = {$this->arParams['USER_ID']}
						".($userType == 'O' ? 'AND tm.USER_ID != t.RESPONSIBLE_ID' : '')."
						AND tm.TYPE = '{$userType}'
						AND t.ZOMBIE = 'N'
						AND t.STATUS < ".CTasks::STATE_SUPPOSEDLY_COMPLETED." /*4 > STATE_NEW(1), STATE_PENDING(2), STATE_IN_PROGRESS(3)*/
				";


		$result = Application::getConnection()->query($sql)->fetch();
		if(!$result || !array_key_exists('COUNT', $result))
		{
			return 0;
		}

		return $result['COUNT'];
	}

	private function roleCodeToUserType()
	{
		return array(
			Counter\Role::AUDITOR => 'U',
			Counter\Role::ACCOMPLICE => 'A',
			Counter\Role::RESPONSIBLE => 'R',
			Counter\Role::ORIGINATOR => 'O',
		);
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

	private function getCounterViolations($roleCode)
	{
		$countersId = $this->roleCodeToCounterId();

		return Counter::getInstance($this->arParams['USER_ID'])->get($countersId[$roleCode]);
	}

	private function getRoleUrl($roleId)
	{
		return $this->arParams['PATH_TO_TASKS'].'?F_CANCEL=Y&F_STATE=sR'.base_convert($roleId, 10, 32);
	}
}