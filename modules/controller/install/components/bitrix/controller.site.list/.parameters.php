<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!CModule::IncludeModule('controller'))
{
	return;
}

$arSiteGroups = [];
$rsSiteGroups = CControllerGroup::GetList(['ID' => 'ASC']);
while ($arSiteGroup = $rsSiteGroups->Fetch())
{
	$arSiteGroups[$arSiteGroup['ID']] = $arSiteGroup['NAME'];
}

$arComponentParameters = [
	'GROUPS' => [
	],
	'PARAMETERS' => [
		'TITLE' => [
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CP_BCSL_TITLE'),
			'TYPE' => 'STRING',
			'DEFAULT' => GetMessage('CP_BCSL_TITLE_DEFAULT'),
		],
		'GROUP' => [
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CP_BCSL_GROUP'),
			'TYPE' => 'LIST',
			'MULTIPLE' => 'Y',
			'VALUES' => $arSiteGroups,
			'DEFAULT' => 1,
		],
		'CACHE_TIME'  => ['DEFAULT' => 3600],
	],
];
