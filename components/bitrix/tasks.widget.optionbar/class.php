<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksWidgetOptionBarComponent extends TasksBaseComponent
{
	protected function checkParameters()
	{
		if (!is_array($this->arParams['OPTIONS']))
		{
			$this->arParams['OPTIONS'] = [];
		}

		$this->arResult['TASK_LIMIT_EXCEEDED'] = static::tryParseBooleanParameter($this->arParams['TASK_LIMIT_EXCEEDED']);

		return $this->errors->checkNoFatals();
	}
}