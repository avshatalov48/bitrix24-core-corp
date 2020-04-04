<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\Util\User;

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

/**
 * Class TasksInterfaceFilterButtonsComponent
 */
class TasksInterfaceFilterButtonsComponent extends TasksBaseComponent
{
	protected function checkParameters()
	{
		$this->arResult['USER_ID'] = static::tryParseIntegerParameter($this->arParams['USER_ID']);
		$this->arResult['TASK_ID'] = static::tryParseIntegerParameter($this->arParams['TASK_ID']);
		$this->arResult['DATA']['SECTION'] = static::tryParseStringParameter($this->arParams['SECTION']);
	}

	protected function getData()
	{
		$checklistGroupCount = count(
			TaskCheckListFacade::getList(['ID'], ['TASK_ID' => $this->arResult['TASK_ID'], 'PARENT_ID' => 0])
		);

		if (in_array($this->arResult['DATA']['SECTION'], ['EDIT_TASK', 'VIEW_TASK'], true))
		{
			$this->arResult['DATA']['FEEDBACK_FORM_PARAMETERS'] = [
				'ID' => 'tasks-checklist',
				'VIEW_TARGET' => null,
				'FORMS' => [
					['zones' => ['com.br'], 'id' => '112','lang' => 'br', 'sec' => 'fcujin'],
					['zones' => ['es'], 'id' => '110','lang' => 'la', 'sec' => 'hj410g'],
					['zones' => ['de'], 'id' => '108','lang' => 'de', 'sec' => '9fgkgr'],
					['zones' => ['ua'], 'id' => '104','lang' => 'ua', 'sec' => 'pahi9k'],
					['zones' => ['ru', 'kz', 'by'], 'id' => '102','lang' => 'ru', 'sec' => 'xsbhvf'],
					['zones' => ['en'], 'id' => '106','lang' => 'en', 'sec' => 'etwdsc'],
				],
				'PRESETS' => [
					'check_list' => ($checklistGroupCount > 0? 1 : 0),
					'amount_list' => $checklistGroupCount,
				],
			];
		}

		$optionName = 'task_options_checklist_show_completed';
		$this->arResult['CHECKLIST_OPTION_SHOW_COMPLETED'] = User::getOption($optionName, $this->arResult['USER_ID'], true);
	}
}