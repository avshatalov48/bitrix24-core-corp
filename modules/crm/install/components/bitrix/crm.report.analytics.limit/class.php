<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Integration\Intranet\ToolsManager;
use Bitrix\Main\Loader;

class CrmReportAnalyticsLimit extends CBitrixComponent
{
	private const AVAILABLE_SLIDER_CODES = [
		ToolsManager::CRM_SLIDER_CODE,
		ToolsManager::REPORTS_ANALYTICS_SLIDER_CODE,
	];

	public function executeComponent()
	{
		$sliderCode = $this->arParams['SLIDER_CODE'] ?? null;
		$this->arResult['SLIDER_CODE'] = (
		in_array($sliderCode, self::AVAILABLE_SLIDER_CODES, true)
			? $sliderCode
			: null
		);

		if ($this->arResult['SLIDER_CODE'] === null && !Loader::includeModule('bitrix24'))
		{
			ShowError("Module bitrix24 is not installed");
			return;
		}

		$this->arResult['LIMITS'] = $this->arParams['LIMITS'] ?? [];
		$this->arResult['BOARD_ID'] = $this->arParams['BOARD_ID'];

		$this->includeComponentTemplate();
	}

}