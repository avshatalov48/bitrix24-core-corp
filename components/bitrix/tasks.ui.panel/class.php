<?php if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

class TasksUiPanelComponent extends CBitrixComponent
{
	protected $userId = 0;

	/**
	 * Init class' vars, check conditions.
	 * @return bool
	 */
	protected function init()
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return false;
		}
		if (($this->userId = (int) \Bitrix\Tasks\Util\User::getId()) <= 0)
		{
			return false;
		}

		$params =& $this->arParams;

		if (!isset($params['FILTER_CLASS']) || !class_exists($params['FILTER_CLASS']))
		{
			return false;
		}

		$pages = array('TASK', 'KANBAN', 'GANTT', 'WIDGET');
		foreach ($pages as $code)
		{
			if (!isset($params['PATH_TO_TASKS_' . $code]))
			{
				$params['PATH_TO_TASKS_' . $code] = '';
			}
		}

		$params['NAVIGATION_BAR_ACTIVE'] = isset($params['NAVIGATION_BAR_ACTIVE']) ? trim($params['NAVIGATION_BAR_ACTIVE']) : '';
		$params['USER_ID'] = isset($params['USER_ID']) ? intval($params['USER_ID']) : 0;
		$params['GROUP_ID'] = isset($params['GROUP_ID']) ? intval($params['GROUP_ID']) : 0;

		return true;
	}

	/**
	 * I am the owner of this list?
	 * @return bool
	 */
	protected function itsMyTasks()
	{
		return $this->userId == $this->arParams['USER_ID'];
	}

	/**
	 * Get request-var for http-hit.
	 * @param string $var Request-var code.
	 * @return mixed
	 */
	protected function request($var)
	{
		static $request = null;

		if ($request === null)
		{
			$context = \Bitrix\Main\Application::getInstance()->getContext();
			$request = $context->getRequest();
		}

		return $request->get($var);
	}

	/**
	 * Get counters of tasks (deadline, etc.).
	 * @return array
	 */
	protected function getCounters()
	{
		$counters = array();
		// not in groups
		if ($params['GROUP_ID'] > 0)
		{
			return $counters;
		}
		// check rights
		if (
			$this->arParams['USER_ID'] != $this->userId &&
			!$GLOBALS['USER']->isAdmin() &&
			!\CTasksTools::IsPortalB24Admin() &&
			!\CTasks::IsSubordinate($this->arParams['USER_ID'], $this->userId)
		)
		{
			return $counters;
		}
		// alright - get counters
		$listCtrl = \CTaskListCtrl::getInstance($this->arParams['USER_ID']);
		$counters = array(
			'TOTAL' => array(
				'COUNTER' => $listCtrl->getMainCounter()
			),
			'ROLES' => array(
				'VIEW_ROLE_RESPONSIBLE' => array(
					'TOTAL' => array(
						'COUNTER' => $listCtrl->getUserRoleCounter(\CTaskListState::VIEW_ROLE_RESPONSIBLE),
						'COUNTER_ID' => $listCtrl->resolveCounterIdByRoleAndCategory(\CTaskListState::VIEW_ROLE_RESPONSIBLE)
					)
				),
				'VIEW_ROLE_ACCOMPLICE' => array(
					'TOTAL' => array(
						'COUNTER' => $listCtrl->getUserRoleCounter(\CTaskListState::VIEW_ROLE_ACCOMPLICE),
						'COUNTER_ID' => $listCtrl->resolveCounterIdByRoleAndCategory(\CTaskListState::VIEW_ROLE_ACCOMPLICE)
					)
				),
				'VIEW_ROLE_ORIGINATOR' => array(
					'TOTAL' => array(
						'COUNTER' => $listCtrl->getUserRoleCounter(\CTaskListState::VIEW_ROLE_ORIGINATOR),
						'COUNTER_ID' => $listCtrl->resolveCounterIdByRoleAndCategory(\CTaskListState::VIEW_ROLE_ORIGINATOR)
					)
				),
				'VIEW_ROLE_AUDITOR' => array(
					'TOTAL' => array(
						'COUNTER' => $listCtrl->getUserRoleCounter(\CTaskListState::VIEW_ROLE_AUDITOR),
						'COUNTER_ID' => $listCtrl->resolveCounterIdByRoleAndCategory(\CTaskListState::VIEW_ROLE_AUDITOR)
					)
				)
			)
		);

		return $counters;
	}

	/**
	 * Get user roles for tasks.
	 * @return array
	 */
	protected function getRoles()
	{
		$roles = array();

		$listState = \CTaskListState::getInstance($this->userId);
		$state = $listState->getState();
		if (isset($state['ROLES']) && is_array($state['ROLES']))
		{
			$i = 0;
			$getKey = 'F_STATE';
			$getValue = $this->request($getKey);
			$me = $this->itsMyTasks();
			$counters = $this->getCounters();
			foreach ($state['ROLES'] as $roleId => $role)
			{
				if (!empty($counters) && isset($counters['ROLES'][$roleId]['TOTAL']))
				{
					$counter = $counters['ROLES'][$roleId]['TOTAL']['COUNTER'];
					$counterId = $counters['ROLES'][$roleId]['TOTAL']['COUNTER_ID'];
				}
				else
				{
					$counter = $counterId = '';
				}
				// for disable automatic refresh counter
				if (!$me)
				{
					$counterId = '';
				}
				$convId = 'sR' . base_convert($role['ID'], 10, 32);
				$roles[$roleId] = array(
					'TEXT' => $role['TITLE'],
					'HREF' => 'F_CANCEL=Y&' . $getKey . '=' . $convId,
					'IS_ACTIVE' => ($getValue == $convId) || (!$getValue && $i == 0),
					'COUNTER' => $counter,
					'COUNTER_ID' => $counterId
				);
				$i++;
			}
		}

		return $roles;
	}

	/**
	 * Base executable method.
	 */
	public function executeComponent()
	{
		if (!$this->init())
		{
			return false;
		}

		$this->arResult['ROLES'] = $this->getRoles();
		$this->arResult['BX24_RU_ZONE'] = \Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24')
											&& preg_match('/^(ru)_/', \Bitrix\Main\Config\Option::get('main', '~controller_group_name', ''));

		$this->IncludeComponentTemplate();
	}
}