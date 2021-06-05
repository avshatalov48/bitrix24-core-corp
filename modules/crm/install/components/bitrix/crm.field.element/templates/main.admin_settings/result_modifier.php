<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if($arResult['additionalParameters']['bVarsFromForm'])
{
	$entityTypeLead =
		($GLOBALS[$arResult['additionalParameters']['NAME']]['LEAD'] === 'Y' ? 'Y' : 'N');
	$entityTypeContact =
		($GLOBALS[$arResult['additionalParameters']['NAME']]['CONTACT'] === 'Y' ? 'Y' : 'N');
	$entityTypeCompany =
		($GLOBALS[$arResult['additionalParameters']['NAME']]['COMPANY'] === 'Y' ? 'Y' : 'N');
	$entityTypeDeal =
		($GLOBALS[$arResult['additionalParameters']['NAME']]['DEAL'] === 'Y' ? 'Y' : 'N');
	$entityTypeOrder =
		($GLOBALS[$arResult['additionalParameters']['NAME']]['ORDER'] === 'Y' ? 'Y' : 'N');
}
elseif(is_array($arResult['userField']))
{
	$entityTypeLead =
		($arResult['userField']['SETTINGS']['LEAD'] === 'Y' ? 'Y' : 'N');
	$entityTypeContact =
		($arResult['userField']['SETTINGS']['CONTACT'] === 'Y' ? 'Y' : 'N');
	$entityTypeCompany =
		($arResult['userField']['SETTINGS']['COMPANY'] === 'Y' ? 'Y' : 'N');
	$entityTypeDeal =
		($arResult['userField']['SETTINGS']['DEAL'] === 'Y' ? 'Y' : 'N');
	$entityTypeOrder =
		($arResult['userField']['SETTINGS']['ORDER'] === 'Y' ? 'Y' : 'N');
}
else
{
	$entityTypeLead = 'Y';
	$entityTypeContact = 'Y';
	$entityTypeCompany = 'Y';
	$entityTypeDeal = 'Y';
	$entityTypeOrder = 'Y';
}

$arResult['entityTypeLead'] = $entityTypeLead;
$arResult['entityTypeContact'] = $entityTypeContact;
$arResult['entityTypeCompany'] = $entityTypeCompany;
$arResult['entityTypeDeal'] = $entityTypeDeal;
$arResult['entityTypeOrder'] = $entityTypeOrder;
$arResult['dynamicTypes'] = \Bitrix\Crm\UserField\Types\ElementType::getUseInUserfieldTypes();