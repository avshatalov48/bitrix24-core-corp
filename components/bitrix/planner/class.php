<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

class CPlannerComponent extends CBitrixComponent
{
	public function executeComponent()
	{
		if(CModule::IncludeModule('intranet'))
		{
			$arData = CIntranetPlanner::getData();
			CIntranetPlanner::initScripts($arData);

			$this->arResult['DATA'] = $arData['DATA'];

			$this->includeComponentTemplate();
		}
	}
}
?>