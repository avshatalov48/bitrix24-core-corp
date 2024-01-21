<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loader::includeModule('crm');

class CrmFilterdependentWrapper extends \CBitrixComponent
{
	public function executeComponent()
	{
		$this->arResult = [];
		$this->includeComponentTemplate();
	}

}