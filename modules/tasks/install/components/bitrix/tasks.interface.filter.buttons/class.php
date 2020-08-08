<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Internals\UserOption;

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

/**
 * Class TasksInterfaceFilterButtonsComponent
 */
class TasksInterfaceFilterButtonsComponent extends TasksBaseComponent
{
	protected function checkParameters()
	{
		$this->arResult['USER_ID'] = static::tryParseIntegerParameter($this->arParams['USER_ID']);
		$this->arResult['ENTITY_ID'] = static::tryParseIntegerParameter($this->arParams['ENTITY_ID']);
		$this->arResult['DATA']['SECTION'] = static::tryParseStringParameter($this->arParams['SECTION']);
	}

	protected function getData()
	{
		$this->arResult['DATA']['CHECKLIST_OPTION_SHOW_COMPLETED'] = User::getOption(
			'task_options_checklist_show_completed',
			$this->arResult['USER_ID'],
			true
		);
		$this->arResult['DATA']['MUTED'] = UserOption::isOptionSet(
			$this->arResult['ENTITY_ID'],
			$this->arResult['USER_ID'],
			UserOption\Option::MUTED
		);
	}
}