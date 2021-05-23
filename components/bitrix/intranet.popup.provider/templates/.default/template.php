<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

global $APPLICATION;
$this->setFrameMode(true);

if ($arResult['IFRAME'])
{
	$APPLICATION->IncludeComponent(
		"bitrix:intranet.popup.popup",

		$arParams["COMPONENT_POPUP_TEMPLATE_NAME"],
		array(
			"POPUP_COMPONENT_NAME" => $arParams["COMPONENT_NAME"],
			"POPUP_COMPONENT_TEMPLATE_NAME" => $arParams["COMPONENT_TEMPLATE"],
			"POPUP_COMPONENT_PARAMS" => $arParams["COMPONENT_PARAMS"],
		)
	);
}
else
{
	$APPLICATION->IncludeComponent(
		$arParams["COMPONENT_NAME"],
		$arParams["COMPONENT_TEMPLATE"],
		$arParams["COMPONENT_PARAMS"]
	);
}


