<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
			
	$arParams["SET_TITLE"] = $arParams["SET_TITLE"]!="N";	
	$URL_PARAMS=explode('?',$arParams['1C_URL']);
	$arResult['AUTH_URL']=$URL_PARAMS[0].'?n='.$arParams['LOGIN'];
	if ($arParams['PASS'])
		$arResult['AUTH_URL'].='&p='.$arParams['PASS'];
	if ($URL_PARAMS[1])
		$arResult['AUTH_URL'].=$URL_PARAMS[1];
	if($arParams["SET_TITLE"]&&$arParams["NAME"]!="")
		$APPLICATION->SetTitle($arParams["NAME"]);
	$this->IncludeComponentTemplate();
 
?>