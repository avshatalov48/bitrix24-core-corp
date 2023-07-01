<?php

namespace Bitrix\Tasks\Ui\Filter;

use Bitrix\Tasks\Util;


class Task
{
	protected static $filterId = '';
	protected static $filterSuffix = '';
	protected static $groupId = 0;
	protected static $userId = 0;
	protected static $gridOptions = null;
	protected static $filterOptions = null;

	/**
	 * @return string
	 */
	public static function getFilterId()
	{
		if(!static::$filterId)
		{
			$stateInstance = static::getListStateInstance();
			$roleId = $stateInstance->getUserRole();
			$section = $stateInstance->getSection();
			$typeFilter = \CTaskListState::VIEW_SECTION_ADVANCED_FILTER == $section ? 'ADVANCED' : 'MAIN';

			$state = $stateInstance->getState();
			$presetSelected = array_key_exists('PRESET_SELECTED', $state) && $state['PRESET_SELECTED']['ID'] == -10  ? 'Y' : 'N';

			static::$filterId = 'TASKS_GRID_ROLE_ID_' . $roleId . '_' . (int)(static::getGroupId() > 0).'_'.$typeFilter.'_'.$presetSelected.static::$filterSuffix;
		}

		return static::$filterId;
	}

	/**
	 * @return \CTaskListState|null
	 */
	public static function getListStateInstance()
	{
		static $instance = null;

		if (is_null($instance))
		{
			$groupId = (int)static::getGroupId();
			$instance = \CTaskListState::getInstance(static::getUserId(), $groupId);
		}
		return $instance;
	}

	/**
	 * @return int
	 */
	public static function getUserId()
	{
		if (!static::$userId)
		{
			static::$userId = Util\User::getId();
		}

		return static::$userId;
	}

	/**
	 * @param $userId
	 */
	public static function setUserId($userId)
	{
		static::$userId = $userId;
	}

	/**
	 * @return \CTaskListCtrl|null
	 */
	public static function getListCtrlInstance()
	{
		static $instance = null;

		if (is_null($instance))
		{
			$instance = \CTaskListCtrl::getInstance(static::getUserId());
		}

		return $instance;
	}

	/**
	 * @return \CTaskFilterCtrl|null
	 */
	private static function getFilterCtrlInstance()
	{
		static $instance = null;

		if (is_null($instance))
		{
			$instance = \CTaskFilterCtrl::getInstance(static::getUserId(), (static::getGroupId() > 0));
		}

		return $instance;
	}

	/**
	 * @return int
	 */
	public static function getGroupId()
	{
		return self::$groupId;
	}

	/**
	 * @param $groupId
	 */
	public static function setGroupId($groupId)
	{
		self::$groupId = $groupId;
	}

	/**
	 * @return \CTaskListState|null
	 */
	public static function listStateInit()
	{
		$listStateInstance = self::getListStateInstance();
		$listCtrlInstance = self::getFilterCtrlInstance();

		$request = \Bitrix\Main\Context::getCurrent()->getRequest()->toArray();

		$listCtrlInstance->SwitchFilterPreset(\CTaskFilterCtrl::STD_PRESET_ALL_MY_TASKS);
		$listStateInstance->setSection(\CTaskListState::VIEW_SECTION_ADVANCED_FILTER);
		$listStateInstance->setTaskCategory(\CTaskListState::VIEW_TASK_CATEGORY_ALL);

		if (!isset($request['F_STATE']))
		{
			$request['F_STATE'] = [];
		}
		$stateParam = (array)$request[ 'F_STATE' ];

		if(!empty($stateParam))
		{
			foreach($stateParam as $state)
			{
				$symbol = mb_substr($state, 0, 2);
				$value = \CTaskListState::decodeState(mb_substr($state, 2));

				switch ($symbol)
				{
					case 'sV':    // set view
						$availableModes = $listStateInstance->getAllowedViewModes();
						if (in_array($value, $availableModes))
						{
							$listStateInstance->setViewMode($value);
						}
						else
						{
							$listStateInstance->setViewMode(\CTaskListState::VIEW_MODE_LIST);
						}

						break;
				}
			}
		}

		$listStateInstance->saveState(); // to db

		return $listStateInstance;
	}
}