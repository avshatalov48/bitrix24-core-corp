<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage mobile
 * @copyright 2001-2015 Bitrix
 */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Security\Sign\Signer;

Loc::loadMessages(__FILE__);

class CBitrixMobileTasksFilterComponent extends CBitrixComponent
{
	protected $dbResult = 		array();
	protected $errors = 		array('FATAL' => array(), 'NONFATAL' => array());

	/**
	 * Function checks if required modules installed. If not, throws an exception
	 * @throws Exception
	 * @return void
	 */
	protected static function checkRequiredModules()
	{
		$result = array('errors' => array());

		if(!Loader::includeModule('mobile'))
			$result['errors'][] = "Mobile module is not installed";

		if(!Loader::includeModule('tasks'))
			$result['errors'][] = "Tasks module is not installed";

		return $result;
	}

	/**
	 * Function checks if user have basic permissions to launch the component
	 * @throws Exception
	 * @return void
	 */
	protected static function checkPermissions($parameters = array())
	{
		return array();
	}

	/**
	 * Additional parameters check, if needed.
	 * @return void
	 */
	protected function checkParameters()
	{
		$result = array('errors' => array());

		if(!isset($GLOBALS['USER']) || !is_object($GLOBALS['USER']))
			$result['errors'][] = "User is not definded";

		if(!intval($this->request['USER_ID']))
			$result['errors'][] = "USER_ID were not specified in request";

		return $result;
	}

	/**
	 * Move data read from database to a specially formatted $arResult
	 * @return void
	 */
	protected function formatResult()
	{
		$this->arResult =& $this->dbResult;
		$this->arResult['ERRORS'] =& $this->errors;
	}

	/**
	 * Function implements all the life cycle of our component
	 * @return void
	 */
	public function executeComponent()
	{
		$modules = static::checkRequiredModules();
		if(is_array($modules['errors']))
			$this->errors['FATAL'] = array_merge($this->errors['FATAL'], $modules['errors']);

		if(empty($this->errors['FATAL']))
		{
			$access = static::checkPermissions();
			if(is_array($access['errors']))
				$this->errors['FATAL'] = array_merge($this->errors['FATAL'], $access['errors']);

			if(empty($this->errors['FATAL']))
			{
				$params = $this->checkParameters();
				if(is_array($params['errors']))
					$this->errors['FATAL'] = array_merge($this->errors['FATAL'], $params['errors']);

				if(empty($this->errors['FATAL']))
				{
					//$this->dispatchAction();
					$this->obtainData();
				}
			}
		}

		$this->formatResult();

		$this->includeComponentTemplate();
	}

	/**
	 * Fetches all required data from database. Everyting that connected with data fetch lies here.
	 * @return void
	 */
	protected function obtainData()
	{
		$this->dbResult['USER_ID'] = intval($this->request['USER_ID']);
		$oFilter = CTaskFilterCtrl::GetInstance($this->dbResult['USER_ID']);
		$this->dbResult['PRESETS_TREE'] = $oFilter->ListFilterPresets($bTreeMode = true);

		if(method_exists($oFilter, 'listFilterSpecialPresets'))
		{
			$specPresets = $oFilter->listFilterSpecialPresets();
			if(is_array($specPresets))
			{
				foreach($specPresets as $id => $preset)
				{
					$this->dbResult['PRESETS_TREE'][$id] = $preset;
				}
			}
		}

		$emptyCondition = serialize(array());

		$listState = CTaskListState::getInstance($this->dbResult['USER_ID']);
		$state = $listState->getState();

		// put here roles instead of sub-presets
		$rootPreset = CTaskFilterCtrlInterface::STD_PRESET_ACTIVE_MY_TASKS;
		if(is_array($this->dbResult['PRESETS_TREE'][$rootPreset]))
		{
			$roles = array();
			if(is_array($state['ROLES']))
			{
				foreach($state['ROLES'] as $roleId => $roleData)
				{
					$roles[$roleData['ID']] = array(
						'Name' => $roleData['TITLE'],
						'Parent' => CTaskFilterCtrlInterface::STD_PRESET_ACTIVE_MY_TASKS,
						'Condition' => $emptyCondition,
						'IS_ROLE' => true
					);
				}
			}

			$this->dbResult['PRESETS_TREE'][$rootPreset]['#Children'] = $roles;
		}

		if($state['SECTION_SELECTED']['ID'] == CTaskListState::VIEW_SECTION_ROLES)
		{
			$filter = 'sR'.base_convert($listState->getUserRole(), 10, 32);
		}
		else
		{
			$filter = $oFilter->GetSelectedFilterPresetId();
		}

		$this->dbResult['CURRENT_PRESET_ID'] = $filter;
	}

	protected function flatterizePresetTreeIteration($level, $depth, &$result)
	{
		if(is_array($level))
		{
			foreach($level as $id => $item)
			{
				$item['ID'] = $id;
				$item['DEPTH_LEVEL'] = $depth;

				if($item['IS_ROLE'])
					$filter = 'sR'.base_convert($id, 10, 32);
				else
					$filter = $id;

				$item['FILTER'] = $filter;

				$children = false;
				if(isset($item['#Children']))
				{
					$children = $item['#Children'];
					unset($item['#Children']);
				}

				$result[] = $item;

				if($children !== false)
				{
					$this->flatterizePresetTreeIteration($children, $depth + 1, $result);
				}
			}
		}
	}

	public function flatterizePresetTree($presetList)
	{
		$result = array();

		$this->flatterizePresetTreeIteration($presetList, 0, $result);

		return $result;
	}
}