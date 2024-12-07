<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!CModule::IncludeModule('crm'))
{
	return;
}

$defaultUrlTemplates = [
	'details' => 'details/#routeId#/',
];

$arDefaultVariableAliases404 = [];
$arDefaultVariableAliases = [];
$componentPage = '';
$componentVariables = ['route_id'];

$variables = [];
$urlTemplates = CComponentEngine::MakeComponentUrlTemplates($defaultUrlTemplates, []);
$componentPage = CComponentEngine::ParseComponentPath($arParams['SEF_FOLDER'] ?? '', $urlTemplates, $variables);

if (empty($componentPage) || (!array_key_exists($componentPage, $defaultUrlTemplates)))
{
	$componentPage = 'index';
}

$arResult['VARIABLES'] = $variables;

$this->IncludeComponentTemplate($componentPage);

