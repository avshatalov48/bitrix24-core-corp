<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\DocumentGenerator\Integration\Bitrix24Manager;
use Bitrix\Main\Localization\Loc;

class DocumentGeneratorFeatureComponent extends \CBitrixComponent
{
//	public function onPrepareComponentParams($arParams)
//	{
//		if(isset($arParams['FEATURE']) && is_string($arParams['FEATURE']))
//		{
//			$this->arResult['featureName'] = $arParams['FEATURE'];
//		}
//	}

	public function executeComponent()
	{
		if(!\Bitrix\Main\Loader::includeModule('documentgenerator'))
		{
			ShowError(Loc::getMessage('DOCGEN_FEATURE_MODULE_ERROR'));
			return;
		}

		if(!Bitrix24Manager::isEnabled())
		{
			//do nothing
			return;
		}

		$this->arResult['title'] = Loc::getMessage('DOCGEN_FEATURE_TITLE');
		$this->arResult['message'] = Loc::getMessage('DOCGEN_FEATURE_MESSAGE', ['#MAX#' => Bitrix24Manager::getDocumentsLimit()]);

		$this->includeComponentTemplate();
	}
}