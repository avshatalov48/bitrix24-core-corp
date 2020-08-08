<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Util\User;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

/**
 * Class TasksWidgetRolesfilterComponent
 */
class TasksWidgetRolesfilterComponent extends TasksBaseComponent
{
	/**
	 * Function checks and prepares all the parameters passed
	 */
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

	/**
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\DB\SqlQueryException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function getRoles(): array
	{
		$roles = [];
		$countersId = $this->roleCodeToCounterId();

		foreach (Counter\Role::getRoles() as $roleId => $role)
		{
			$roleCode = $role['CODE'];
			$roles[$roleId] = [
				'TITLE' => $role['TITLE'],
				'COUNTER_ID' => 'tasks_'.$countersId[$roleCode],
				'COUNTER' => $this->getCounter($roleCode),
				'COUNTER_VIOLATIONS' => $this->getCounterViolations($roleCode),
				'HREF' => $this->getRoleUrl($role['ID']),
			];
		}

		return $roles;
	}

	/**
	 * @param string $roleCode
	 * @return int
	 * @throws Main\Db\SqlQueryException
	 */
	private function getCounter(string $roleCode): int
	{
		$counter = 0;

		$userType = $this->roleCodeToUserType()[$roleCode];
		$statuses = [
			CTasks::STATE_PENDING,
			CTasks::STATE_IN_PROGRESS,
			CTasks::STATE_SUPPOSEDLY_COMPLETED,
			CTasks::STATE_DEFERRED,
		];
		$statuses = implode(',', $statuses);

		$res = Application::getConnection()->query("
			SELECT COUNT(DISTINCT T.ID) as COUNT
			FROM b_tasks T
				INNER JOIN b_tasks_member TM ON TM.TASK_ID = T.ID
			WHERE 
				TM.USER_ID = {$this->arParams['USER_ID']}
				".($userType === 'O' ? 'AND TM.USER_ID != T.RESPONSIBLE_ID' : '')."
				AND TM.TYPE = '{$userType}'
				AND T.ZOMBIE = 'N'
				AND T.STATUS IN ({$statuses})
		");
		if ($row = $res->fetch())
		{
			$counter = (int)$row['COUNT'];
		}

		return ($counter ?: 0);
	}

	/**
	 * @return string[]
	 */
	private function roleCodeToUserType(): array
	{
		return [
			Counter\Role::RESPONSIBLE => 'R',
			Counter\Role::ACCOMPLICE => 'A',
			Counter\Role::ORIGINATOR => 'O',
			Counter\Role::AUDITOR => 'U',
		];
	}

	/**
	 * @param string $roleCode
	 * @return bool|int|mixed
	 * @throws Main\ArgumentException
	 * @throws Main\DB\SqlQueryException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function getCounterViolations(string $roleCode)
	{
		$countersId = $this->roleCodeToCounterId();
		return Counter::getInstance($this->arParams['USER_ID'])->get($countersId[$roleCode]);
	}

	/**
	 * @return array
	 */
	private function roleCodeToCounterId(): array
	{
		return [
			Counter\Role::RESPONSIBLE => Counter\Name::MY,
			Counter\Role::ACCOMPLICE => Counter\Name::ACCOMPLICES,
			Counter\Role::ORIGINATOR => Counter\Name::ORIGINATOR,
			Counter\Role::AUDITOR => Counter\Name::AUDITOR,
		];
	}

	/**
	 * @param $roleId
	 * @return string
	 */
	private function getRoleUrl($roleId): string
	{
		return $this->arParams['PATH_TO_TASKS'].'?F_CANCEL=Y&F_STATE=sR'.base_convert($roleId, 10, 32);
	}
}