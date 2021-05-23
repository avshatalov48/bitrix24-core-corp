<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

class SalesCenterFeatureComponent extends \CBitrixComponent
{
	public function onPrepareComponentParams($arParams)
	{
		if(isset($arParams['FEATURE']) && is_string($arParams['FEATURE']))
		{
			$this->arResult['featureName'] = $arParams['FEATURE'];
		}
	}

	public function executeComponent()
	{
		if(!\Bitrix\Main\Loader::includeModule('salescenter'))
		{
			ShowError(Loc::getMessage('SALESCENTER_FEATURE_MODULE_ERROR'));
			return;
		}

		$manager = \Bitrix\SalesCenter\Integration\Bitrix24Manager::getInstance();

		if(!$manager->isEnabled())
		{
			//do nothing
			return;
		}

		if(!$this->arResult['featureName'])
		{
			return;
		}

		if($this->arResult['featureName'] === 'salescenter')
		{
			$this->arResult['title'] = Loc::getMessage('SALESCENTER_FEATURE_TITLE');
			$this->arResult['message'] = Loc::getMessage('SALESCENTER_FEATURE_MESSAGE');
		}
		elseif($this->arResult['featureName'] === 'salescenterPaymentsLimit')
		{
			$this->arResult['title'] = Loc::getMessage('SALESCENTER_LIMITS_TITLE');
			$this->arResult['message'] = Loc::getMessage('SALESCENTER_LIMITS_MESSAGE');
		}
		else
		{
			return;
		}

		global $APPLICATION;
		$APPLICATION->SetTitle($this->arResult['title']);

		$this->includeComponentTemplate();
	}
}