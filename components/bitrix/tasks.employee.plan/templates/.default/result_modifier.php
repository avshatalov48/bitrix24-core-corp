<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\User;

$arResult['HELPER'] = new UI\Component\TemplateHelper('EmployeePlan', $this, array(
	'RELATION' => array('tasks_util_datepicker', 'tasks_util_router', 'tasks_util', 'tasks_util_selector', 'tasks_util_dictionary', 'task_scheduler')
));

$this->__component->arParams["PATH_TO_TASKS"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS"]);
$this->__component->arParams["PATH_TO_TASKS_TASK"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS_TASK"]);

if($arResult['HELPER']->getErrors()->checkNoFatals())
{

	$options = User::getOption('scheduler');
	$options = is_array($options) ? $options : array();

// everything that lies in JS_DATA can be accessible through this.option('keyname') inside the js controller
	$arResult['JS_DATA']['gridData'] = $arResult['DATA']['REGION'];
	$arResult['JS_DATA']['filter'] = $arResult['FILTER'];
	$arResult['JS_DATA']['zoomLevel'] = isset($options['zoom_level']) ? $options['zoom_level'] : "";
	$arResult['JS_DATA']['gutterOffset'] = isset($options['gutter_offset']) ? $options['gutter_offset'] : "";
	$arResult['JS_DATA']['calendarSettings'] = UI::translateCalendarSettings($arResult['AUX_DATA']['COMPANY_WORKTIME']);
	$arResult['JS_DATA']['currentDayTime'] = UI::formatDateTime(User::getTime());
	$arResult['JS_DATA']['userProfileUrl'] = $arResult['HELPER']->findParameterValue('PATH_TO_USER_PROFILE');
	$arResult['JS_DATA']['pageSize'] = $arResult['COMPONENT_DATA']['PAGE_SIZE'];

	$statuses = array();
	if(Type::isIterable($arResult['AUX_DATA']['TASK']['STATUS']))
	{
		foreach($arResult['AUX_DATA']['TASK']['STATUS'] as $id => $code)
		{
			$id = intval($id);
			if($id <= 0 || $id == CTasks::STATE_DECLINED || $id == CTasks::STATE_NEW)
			{
				unset($arResult['AUX_DATA']['TASK']['STATUS'][$id]);
				continue;
			}
			$statuses[] = array(
				'VALUE' => $id,
				'DISPLAY' => ToLower(Loc::getMessage('TASKS_TASK_STATUS_'.$id)), // defined in tasks/lang/**/include.php
			);
		}
	}
	$arResult['JS_DATA']['statusList'] = $statuses;

	$departments = array();
	if(Type::isIterable($arResult['AUX_DATA']['FILTER']['DEPARTMENT']['STRUCTURE']))
	{
		foreach($arResult['AUX_DATA']['FILTER']['DEPARTMENT']['STRUCTURE'] as $dep)
		{
			$dep['VALUE'] = $dep['ID'];
			$dep['DISPLAY'] = $dep['NAME'];
			$dep['DISPLAY_PREFIX_UNSAFE'] = str_repeat('<span class="tasks-employee-plan-offset"></span>', ($dep['DEPTH_LEVEL'] - 1));

			$departments[] = $dep;
		}
	}
	$arResult['JS_DATA']['companyDepartments'] = $departments;

	$users = array();
	if(Type::isIterable($arResult['AUX_DATA']['FILTER']['DEPARTMENT']['USERS']))
	{
		foreach($arResult['AUX_DATA']['FILTER']['DEPARTMENT']['USERS'] as $user)
		{
			$users[] = array(
				'VALUE' => $user['ID'],
				'DISPLAY' => User::formatName($user),
				'DEP' => intval($user['DEPARTMENT_ID']),
			);
		}
	}
	$arResult['JS_DATA']['departmentUsers'] = $users;

	//_print_r($arResult['JS_DATA']['departmentUsers']);
}