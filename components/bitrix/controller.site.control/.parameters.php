<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
/** @var array $arCurrentValues */
if (!CModule::IncludeModule('controller'))
{
	return;
}

$arUGroupsEx = [];
$dbUGroups = CGroup::GetList();
while ($arUGroups = $dbUGroups -> Fetch())
{
	$arUGroupsEx[$arUGroups['ID']] = $arUGroups['NAME'];
}

$arComponentParameters = [
	'GROUPS' => [
	],
	'PARAMETERS' => [
		'SITE_URL' => [
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CP_BCSC_SITE_URL'),
			'TYPE' => 'STRING',
			'DEFAULT' => '={$_REQUEST["site_url"]}',
		],
		'COMMAND' => [
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CP_BCSC_COMMAND'),
			'TYPE' => 'STRING',
			'DEFAULT' => '={$_REQUEST["command"]}',
		],
		'ACTION' => [
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CP_BCSC_ACTION'),
			'TYPE' => 'STRING',
			'DEFAULT' => '={$_REQUEST["action"]}',
		],
		'SEPARATOR' => [
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CP_BCSC_SEPARATOR'),
			'TYPE' => 'STRING',
			'DEFAULT' => ',',
		],
		'ACCESS_RESTRICTION' => [
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CP_BCSC_ACCESS_RESTRICTION'),
			'TYPE' => 'LIST',
			'VALUES' => [
				'GROUP' => GetMessage('CP_BCSC_BY_GROUP'),
				'IP' => GetMessage('CP_BCSC_BY_IP'),
				'NONE' => GetMessage('MAIN_NO'),
			],
			'DEFAULT' => ['GROUP'],
			'REFRESH' => 'Y',
		],
		'IP_PERMISSIONS' => [
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CP_BCSC_IP_PERMISSIONS'),
			'TYPE' => 'STRING',
			'DEFAULT' => '',
		],
		'GROUP_PERMISSIONS' => [
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CP_BCSC_GROUP_PERMISSIONS'),
			'TYPE' => 'LIST',
			'VALUES' => $arUGroupsEx,
			'DEFAULT' => [1],
			'MULTIPLE' => 'Y',
		],
	],
];
if ($arCurrentValues['ACCESS_RESTRICTION'] == 'NONE')
{
	unset($arComponentParameters['PARAMETERS']['GROUP_PERMISSIONS']);
	unset($arComponentParameters['PARAMETERS']['IP_PERMISSIONS']);
}
elseif ($arCurrentValues['ACCESS_RESTRICTION'] == 'IP')
{
	unset($arComponentParameters['PARAMETERS']['GROUP_PERMISSIONS']);
}
else
{
	unset($arComponentParameters['PARAMETERS']['IP_PERMISSIONS']);
}
