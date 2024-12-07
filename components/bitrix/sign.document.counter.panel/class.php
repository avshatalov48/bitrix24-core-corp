<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}


use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

\CBitrixComponent::includeComponentClass('bitrix:sign.base');

class SignDocumentCounterPanelComponent extends SignBaseComponent
{
	private ErrorCollection $errors;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errors = new ErrorCollection();
	}

	protected function exec(): void
	{
		if (!$this->errors->isEmpty())
		{
			$this->showErrors();
			return;
		}

		$this->prepareResult();
	}

	private function prepareResult(): void
	{
		$this->arResult['GUID'] = $this->arParams['GUID'] ?? 'counter_panel';
		$this->arResult['IS_MULTISELECT'] = false;
		$this->arResult['ITEMS'] = $this->prepareItems();
		$this->arResult['TITLE'] = $this->arParams['TITLE'] ?? null;
		$this->arResult['FILTER_ID'] = $this->arParams['FILTER_ID'] ?? 'DEFAULT_FILTER_ID';
	}

	private function prepareItems(): array
	{
		if (empty($this->arParams['ITEMS']) || !is_array($this->arParams['ITEMS']))
		{
			return [];
		}

		return $this->arParams['ITEMS'];
	}
}
