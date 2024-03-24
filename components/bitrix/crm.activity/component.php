<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Crm\Restriction\AvailabilityManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;

if (!CModule::IncludeModule('crm'))
{
	ShowError(Loc::getMessage('CRM_MODULE_NOT_INSTALLED'));

	return;
}

$toolsManager = Container::getInstance()->getIntranetToolsManager();

$arResult['CUSTOM_SECTION_CODE'] = $arParams['CUSTOM_SECTION_CODE'] ?? null;
$isCustomSectionContext = IntranetManager::isCustomSectionExists($arResult['CUSTOM_SECTION_CODE']);
if ($isCustomSectionContext && !$toolsManager->checkExternalDynamicAvailability())
{
	print AvailabilityManager::getInstance()->getExternalDynamicInaccessibilityContent();

	return;
}

$isAvailable = $toolsManager->checkCrmAvailability();
if (!$isAvailable && !$isCustomSectionContext)
{
	print AvailabilityManager::getInstance()->getCrmInaccessibilityContent();

	return;
}

$arParams['NAME_TEMPLATE'] = (
	empty($arParams['NAME_TEMPLATE'])
		? CSite::GetNameFormat(false)
		: str_replace(['#NOBR#', '#/NOBR#'], ['', ''], $arParams['NAME_TEMPLATE'])
);
$arResult['ENABLE_CONTROL_PANEL'] = $arParams['ENABLE_CONTROL_PANEL'] ?? true;

$variables = [];
if ($arParams['SEF_MODE'] === 'Y')
{
	$defaultUrlTemplates404 = [
		'index' => 'index.php',
		'list' => '',
		'kanban' => 'kanban/',
		'widget' => 'widget/',
	];

	$urlTemplates = CComponentEngine::MakeComponentUrlTemplates($defaultUrlTemplates404, $arParams['SEF_URL_TEMPLATES']);
	$variableAliases = CComponentEngine::MakeComponentVariableAliases([], $arParams['VARIABLE_ALIASES']);
	$componentPage = CComponentEngine::ParseComponentPath($arParams['SEF_FOLDER'], $urlTemplates, $variables);

	if (empty($componentPage) || (!array_key_exists($componentPage, $defaultUrlTemplates404)))
	{
		$componentPage = 'index';
	}

	if (
		isset($arParams['COMPONENT_PAGE'])
		&& array_key_exists($arParams['COMPONENT_PAGE'], $defaultUrlTemplates404)
	)
	{
		$componentPage = $arParams['COMPONENT_PAGE'];
	}

	CComponentEngine::InitComponentVariables($componentPage, [], $variableAliases, $variables);

	foreach ($urlTemplates as $url => $value)
	{
		$strUpperUrl = mb_strtoupper($url);
		if (empty($arParams['PATH_TO_ACTIVITY_' . $strUpperUrl]))
		{
			$arResult['PATH_TO_ACTIVITY_' . $strUpperUrl] = $arParams['SEF_FOLDER'] . $value;
		}
		else
		{
			$arResult['PATH_TO_ACTIVITY_' . $strUpperUrl] = $arParams['PATH_TO_' . $strUpperUrl];
		}
	}

	$aliases = [];
}
else
{
	$variableAliases = CComponentEngine::MakeComponentVariableAliases([], $arParams['VARIABLE_ALIASES']);
	CComponentEngine::InitComponentVariables(false, [], $variableAliases, $variables);

	$componentPage = 'index';
//	if (isset($_REQUEST['widget']))
//	{
//		$componentPage = 'widget';
//	}

	$arResult['PATH_TO_ACTIVITY_LIST'] = $APPLICATION->GetCurPage();
	$arResult['PATH_TO_ACTIVITY_KANBAN'] = $APPLICATION->GetCurPage() . '?kanban';
	$arResult['PATH_TO_ACTIVITY_WIDGET'] = $APPLICATION->GetCurPage() . '?widget';

	$aliases = $variableAliases;
}

$arResult = array_merge(
	[
		'VARIABLES' => $variables,
		'ALIASES' => $aliases,
	],
	$arResult
);

$arResult['NAVIGATION_CONTEXT_ID'] = 'ACTIVITY';
if ($isCustomSectionContext)
{
	$router = Container::getInstance()->getRouter();
	$arResult['NAVIGATION_CONTEXT_ID'] = $router->getEntityViewNameInCustomSection(
		CCrmOwnerType::Activity,
		$arResult['CUSTOM_SECTION_CODE'])
	;
}

$this->IncludeComponentTemplate($componentPage);