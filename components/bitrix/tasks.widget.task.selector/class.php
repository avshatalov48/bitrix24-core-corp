<?php

use Bitrix\Main\Engine\CurrentUser;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class TasksWidgetTaskSelectorComponent extends TasksBaseComponent
{
	public function executeComponent(): void
	{
		$this->parseParameters();
		$this->initResultParams();
		$this->includeComponentTemplate();
	}

	private function initResultParams(): void
	{
		$this->arResult['TEMPLATE_ID'] = $this->arParams['TEMPLATE_ID'];
		$this->arResult['INPUT_PREFIX'] = $this->arParams['INPUT_PREFIX'];
		$this->arResult['BLOCK_NAME'] = $this->arParams['BLOCK_NAME'];
		$this->arResult['TASKS'] = $this->arParams['TASKS'];
		$this->arResult['MULTIPLE'] = $this->arParams['MULTIPLE'];
		$this->arResult['NAME'] = $this->arParams['NAME'];
		$this->arResult['USER_ID'] = CurrentUser::get()->getId();
	}

	private function parseParameters(): void
	{
		static::tryParseStringParameter($this->arParams['TEMPLATE_ID']);
		static::tryParseStringParameter($this->arParams['INPUT_PREFIX']);
		static::tryParseStringParameter($this->arParams['BLOCK_NAME']);
		static::tryParseArrayParameter($this->arParams['TASKS']);
		static::tryParseBooleanParameter($this->arParams['MULTIPLE']);
		static::tryParseStringParameter($this->arParams['NAME']);
	}
}
