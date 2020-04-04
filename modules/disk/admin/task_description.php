<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

return array(
	'DISK_ACCESS_FULL' => array(
		'title' => Loc::getMessage('TASK_NAME_DISK_FULL'),
	),
	'DISK_ACCESS_SHARING' => array(
		'title' => Loc::getMessage('TASK_NAME_DISK_SHARING'),
	),
	'DISK_ACCESS_EDIT' => array(
		'title' => Loc::getMessage('TASK_NAME_DISK_EDIT'),
	),
	'DISK_ACCESS_READ' => array(
		'title' => Loc::getMessage('TASK_NAME_DISK_READ'),
	),
	'DISK_ACCESS_ADD' => array(
		'title' => Loc::getMessage('TASK_NAME_DISK_ADD'),
	),
);
