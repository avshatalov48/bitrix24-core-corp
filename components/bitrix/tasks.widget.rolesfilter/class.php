<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Internals\Task\MemberTable;

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
		$counter = Counter::getInstance((int) $this->arParams['USER_ID']);

		$roles = [];
		$countersId = $this->roleCodeToCounterId();
		foreach (Counter\Role::getRoles() as $roleId => $role)
		{
			$roleCode = $role['CODE'];
			$counters = $counter->getCounters($roleCode);
			$roles[$roleId] = [
				'TITLE' => $role['TITLE'],
				'COUNTER_ID' => 'tasks_'.$countersId[$roleCode],
				'COUNTER' => $this->getCounter($roleCode),
				'COUNTER_VIOLATIONS' => $this->getCounterViolations($counters),
				'HREF' => $this->getRoleUrl($role['ID']),
			];
		}

		return $roles;
	}

	/**
	 * @param string $roleCode
	 * @return string
	 * @throws Main\DB\SqlQueryException
	 */
	private function getCounter(string $roleCode): string
	{
		$userType = $this->roleCodeToUserType()[$roleCode];
		$statuses = [
			Status::PENDING,
			Status::IN_PROGRESS,
			Status::SUPPOSEDLY_COMPLETED,
			Status::DEFERRED,
		];
		$statuses = implode(',', $statuses);

		$sql = "
			SELECT DISTINCT T.ID
			FROM b_tasks T
				INNER JOIN b_tasks_member TM ON TM.TASK_ID = T.ID
			WHERE 
				TM.USER_ID = {$this->arParams['USER_ID']}
				" . ($userType === 'O' ? 'AND TM.USER_ID != T.RESPONSIBLE_ID' : '') . "
				AND TM.TYPE = '{$userType}'
				AND T.STATUS IN ({$statuses})
			LIMIT 100
		";

		$res = Application::getConnection()->query($sql);
		$counter = $res->getSelectedRowsCount();

		return ($counter > 99 ? '99+' : $counter);
	}

	/**
	 * @return string[]
	 */
	private function roleCodeToUserType(): array
	{
		return [
			Counter\Role::RESPONSIBLE => MemberTable::MEMBER_TYPE_RESPONSIBLE,
			Counter\Role::ACCOMPLICE => MemberTable::MEMBER_TYPE_ACCOMPLICE,
			Counter\Role::ORIGINATOR => MemberTable::MEMBER_TYPE_ORIGINATOR,
			Counter\Role::AUDITOR => MemberTable::MEMBER_TYPE_AUDITOR,
		];
	}

	/**
	 * @param array $counters
	 * @return string
	 */
	private function getCounterViolations(array $counters): string
	{
		$counter = $counters[Counter\CounterDictionary::COUNTER_EXPIRED]['counter'];
		$counter = ($counter ?? 0);

		return ($counter > 99 ? '99+' : $counter);
	}

	/**
	 * @return array
	 */
	private function roleCodeToCounterId(): array
	{
		return [
			Counter\Role::RESPONSIBLE => Counter\CounterDictionary::COUNTER_MY,
			Counter\Role::ACCOMPLICE => Counter\CounterDictionary::COUNTER_ACCOMPLICES,
			Counter\Role::ORIGINATOR => Counter\CounterDictionary::COUNTER_ORIGINATOR,
			Counter\Role::AUDITOR => Counter\CounterDictionary::COUNTER_AUDITOR,
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