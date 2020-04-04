<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CIntranetPopupPopup extends \CBitrixComponent
{
	public function onPrepareComponentParams($arParams)
	{
		if (!isset($arParams["POPUP_COMPONENT_PARAMS"]) || !is_array($arParams["POPUP_COMPONENT_PARAMS"]))
		{
			$arParams["POPUP_COMPONENT_PARAMS"] = array();
		}

		$arParams["POPUP_COMPONENT_PARAMS"]["IFRAME"] = true;

		return parent::onPrepareComponentParams($arParams);
	}

	public function executeComponent()
	{
		global $APPLICATION;

		$APPLICATION->RestartBuffer();

		\CJSCore::init("sidepanel");

		$this->includeComponentTemplate();

		require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
		exit;
	}
}