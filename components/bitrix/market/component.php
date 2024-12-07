<?php

use Bitrix\Main\Web\Uri;
use Bitrix\Market\Categories;
use Bitrix\Market\PageRules;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $this
 * @global CMain $APPLICATION
 * @global CUser $USER
 */

$arDefaultUrlTemplates404 = PageRules::DEFAULT_URL_TEMPLATES;

$arDefaultVariableAliases404 = [];

$arDefaultVariableAliases = [
	'category' => 'category',
	'app' => 'app'
];

$arComponentVariables = ["category", "app"];

$SEF_FOLDER = "";
$arUrlTemplates = [];

if($arParams["SEF_MODE"] == "Y")
{
	$arVariables = [];

	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams["SEF_URL_TEMPLATES"]);
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams["VARIABLE_ALIASES"]);

	$componentPage = CComponentEngine::ParseComponentPath(
		$arParams["SEF_FOLDER"],
		$arUrlTemplates,
		$arVariables
	);

	if($componentPage == '' || $componentPage == 'booklet')
	{
		$componentPage = "main";
	}

	CComponentEngine::InitComponentVariables($componentPage,
		$arComponentVariables,
		$arVariableAliases,
		$arVariables
	);

	$SEF_FOLDER = $arParams["SEF_FOLDER"];

}
else
{
	$arVariables = [];
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams["VARIABLE_ALIASES"]);
	CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

	$componentPage = "";
	if($arVariables["app"] <> '')
	{
		$componentPage = "detail";
	}
	elseif($arVariables["category"] <> '')
	{
		$componentPage = "category";
	}
	else
	{
		$componentPage = "top";
	}

	if (\CRestUtil::isSlider())
		$arParams['DETAIL_URL_TPL'] = $APPLICATION->GetCurPageParam('app=#app#');
	else
		$arParams['DETAIL_URL_TPL'] = $APPLICATION->GetCurPageParam('app=#app#', ['IFRAME', 'IFRAME_TYPE']);
}

if ($componentPage === 'install_version' || $componentPage === 'install_hash')
{
	$componentPage = 'install';
}

$uri = new Uri($APPLICATION->GetCurPageParam());

$arResult = [
	"FOLDER" => $SEF_FOLDER,
	"URL_TEMPLATES" => $arUrlTemplates,
	"VARIABLES" => $arVariables,
	"ALIASES" => $arVariableAliases,
	"CURRENT_PAGE" => $uri->toAbsolute()->getLocator(),
];

$arParams["COMPONENT_PAGE"] = $componentPage;

Categories::initFromCache();

$this->IncludeComponentTemplate($componentPage);