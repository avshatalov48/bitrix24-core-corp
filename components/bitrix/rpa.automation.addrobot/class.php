<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if(!\Bitrix\Main\Loader::includeModule('rpa'))
{
	return;
}

use Bitrix\Main;
use Bitrix\Rpa;

class RpaAutomationAddRobotComponent extends Rpa\Components\Base
{
	public function onPrepareComponentParams($arParams)
	{
		$arParams["typeId"] = (int) $arParams["typeId"];
		static::fillParameterFromRequest('stage', $arParams);
		$arParams["SET_TITLE"] = ($arParams["SET_TITLE"] === "N" ? "N" : "Y");

		return $arParams;
	}

	public function executeComponent()
	{
		if (!Rpa\Integration\Bizproc\Automation\Factory::canUseAutomation())
		{
			return;
		}

		$this->arResult['DOCUMENT_TYPE'] = Rpa\Integration\Bizproc\Document\Item::makeComplexType(
			$this->arParams['typeId']
		);

		$this->arResult['ROBOTS'] = \CBPRuntime::getRuntime()
			->searchActivitiesByType('rpa_activity', $this->arResult['DOCUMENT_TYPE']);

		if ($this->arParams['SET_TITLE'] === 'Y')
		{
			$this->getApplication()->SetTitle(GetMessage("RPA_AUTOMATION_ADDROBOT_TITLE"));
		}

		$this->includeComponentTemplate();
	}
}