<?php if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

class TasksUiFilterComponent extends CBitrixComponent
{
	/**
	 * Base executable method.
	 */
	public function executeComponent()
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return false;
		}
		if (!isset($this->arParams['FILTER_CLASS']) || !class_exists($this->arParams['FILTER_CLASS']))
		{
			return false;
		}

		$this->IncludeComponentTemplate();
	}
}