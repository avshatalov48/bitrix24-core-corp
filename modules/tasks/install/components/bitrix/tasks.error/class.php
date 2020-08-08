<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

class TasksError extends CBitrixComponent
{
	public function executeComponent()
	{
		$this->fillResult();

		$this->includeComponentTemplate();
	}

	private function fillResult()
	{
		$this->arResult = [
			'TITLE' => \Bitrix\Main\Localization\Loc::getMessage('TASKS_ERROR_COMPONENT_TITLE_DEFAULT'),
			'DESCRIPTION' => \Bitrix\Main\Localization\Loc::getMessage('TASKS_ERROR_COMPONENT_DESCRIPTION_DEFAULT')
		];

		$this->arResult = array_merge($this->arResult, $this->arParams);
	}
}