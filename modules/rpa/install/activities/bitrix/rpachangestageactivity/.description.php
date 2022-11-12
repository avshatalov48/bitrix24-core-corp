<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'SORT' => 400,
	'NAME' => Loc::getMessage('RPA_BP_CHS_DESCR_NAME'),
	'DESCRIPTION' => Loc::getMessage('RPA_BP_CHS_DESCR_DESCRIPTION'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'RpaChangeStageActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
	],
	'FILTER' => [
		'INCLUDE' => [['rpa']],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'RESPONSIBLE_PROPERTY' => 'ModifiedBy',
	],
];