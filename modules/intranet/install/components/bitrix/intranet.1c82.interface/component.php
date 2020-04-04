<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
			
	$arParams["SET_TITLE"] = $arParams["SET_TITLE"]!="N";	
	$URL_PARAMS=explode('?',$arParams['1C_URL']);
	$arParams['LOGIN']=$APPLICATION->ConvertCharset($arParams['LOGIN'],SITE_CHARSET,"UTF-8");
	$arParams['PASS']=$APPLICATION->ConvertCharset($arParams['PASS'],SITE_CHARSET,"UTF-8");
	$arResult['AUTH_URL']=$URL_PARAMS[0].'?n='.$arParams['LOGIN'];
	if ($arParams['PASS'])
		$arResult['AUTH_URL'].='&p='.$arParams['PASS'];
	if ($URL_PARAMS[1])
		$arResult['AUTH_URL'].=$URL_PARAMS[1];
	if($arParams["SET_TITLE"]&&$arParams["NAME"]!="")
		$APPLICATION->SetTitle($arParams["NAME"]);
	$this->IncludeComponentTemplate();
 
?>