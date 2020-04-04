<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;

class CIntranetPopupProvider extends \CBitrixComponent
{
	public function onPrepareComponentParams($arParams)
	{
		if (is_array($arParams["MODULES"]) && !empty($arParams["MODULES"]))
		{
			foreach ($arParams["MODULES"] as $module)
			{
				Loader::includeModule($module);
			}
		}

		if (!isset($arParams["COMPONENT_PARAMS"]) || !is_array($arParams["COMPONENT_PARAMS"]))
		{
			$arParams["COMPONENT_PARAMS"] = array();
		}

		if (!isset($arParams["COMPONENT_TEMPLATE"]))
		{
			$arParams["COMPONENT_TEMPLATE"] = ".default";
		}
		if (!isset($arParams["COMPONENT_POPUP_TEMPLATE_NAME"]))
		{
			$arParams["COMPONENT_POPUP_TEMPLATE_NAME"] = ".default";
		}

		return parent::onPrepareComponentParams($arParams);
	}

	public function executeComponent()
	{
		$this->arResult["IFRAME"] = ($this->request["IFRAME"] == "Y");

		$this->includeComponentTemplate();
	}
}