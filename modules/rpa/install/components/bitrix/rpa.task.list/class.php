<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if(!\Bitrix\Main\Loader::includeModule('rpa'))
{
	return;
}

use Bitrix\Main;
use Bitrix\Rpa;
use Bitrix\Bizproc;

class RpaAutomationTaskListComponent extends Rpa\Components\Base
{
	public function onPrepareComponentParams($arParams)
	{
		$arParams["typeId"] = (int)($arParams["typeId"] ?? 0);
		$arParams["SET_TITLE"] = (($arParams["SET_TITLE"] ?? '') === "N" ? "N" : "Y");

		return $arParams;
	}

	public function executeComponent()
	{
		$this->arResult['DOCUMENT_TYPE'] = Rpa\Integration\Bizproc\Document\Item::makeComplexType(
			$this->arParams['typeId']
		);

		$this->arResult['DOCUMENT_STATUS'] = $this->arParams['stage'] ?? null;

		if ($this->arParams['SET_TITLE'] === 'Y')
		{
			$this->getApplication()->SetTitle(GetMessage("RPA_AUTOMATION_TASK_LIST_TITLE"));
		}

		$this->includeComponentTemplate();
	}
}