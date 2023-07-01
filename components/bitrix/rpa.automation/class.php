<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if(!\Bitrix\Main\Loader::includeModule('rpa'))
{
	return;
}

use Bitrix\Main;
use Bitrix\Rpa;

class RpaAutomationComponent extends Rpa\Components\Base
{
	public function onPrepareComponentParams($arParams)
	{
		$arParams["typeId"] = (int) $arParams["typeId"];
		$arParams["SET_TITLE"] = (isset($arParams["SET_TITLE"]) && $arParams["SET_TITLE"] === "N" ? "N" : "Y");

		return $arParams;
	}

	public function executeComponent()
	{
		if (!$this->isIframe())
		{
			$this->includeComponentTemplate('kanban');
			return;
		}

		$this->arResult['DOCUMENT_TYPE'] = Rpa\Integration\Bizproc\Document\Item::makeComplexType(
			$this->arParams['typeId']
		);

		if ($this->arParams['SET_TITLE'] === 'Y')
		{
			$this->getApplication()->SetTitle(GetMessage("RPA_AUTOMATION_TITLE"));
		}

		$this->includeComponentTemplate();
	}
}