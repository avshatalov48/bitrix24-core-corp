<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Loader;

class CrmReportAnalyticsLimit extends CBitrixComponent
{
	public function executeComponent()
	{
		if(!Loader::includeModule("bitrix24"))
		{
			ShowError("Module bitrix24 is not installed");
			return;
		}

		$this->arResult['LIMITS'] = isset($this->arParams['LIMITS']) ? $this->arParams['LIMITS'] : [];
		$this->arResult['BOARD_ID'] = $this->arParams['BOARD_ID'];
		$this->includeComponentTemplate();
	}

}