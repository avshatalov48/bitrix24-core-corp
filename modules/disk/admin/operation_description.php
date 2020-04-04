<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
return array(
	'DISK_READ' => array(
		'title' => Loc::getMessage('OP_NAME_DISK_READ'),
	),
	'DISK_ADD' => array(
		'title' => Loc::getMessage('OP_NAME_DISK_ADD'),
	),
	'DISK_EDIT' => array(
		'title' => Loc::getMessage('OP_NAME_DISK_EDIT'),
	),
	'DISK_SETTINGS' => array(
		'title' => Loc::getMessage('OP_NAME_DISK_SETTINGS'),
	),
	'DISK_DELETE' => array(
		'title' => Loc::getMessage('OP_NAME_DISK_DELETE_2'),
	),
	'DISK_DESTROY' => array(
		'title' => Loc::getMessage('OP_NAME_DISK_DISK_DESTROY'),
	),
	'DISK_RESTORE' => array(
		'title' => Loc::getMessage('OP_NAME_DISK_DISK_RESTORE'),
	),
	'DISK_RIGHTS' => array(
		'title' => Loc::getMessage('OP_NAME_DISK_RIGHTS'),
	),
	'DISK_SHARING' => array(
		'title' => Loc::getMessage('OP_NAME_DISK_SHARING'),
	),
	'DISK_START_BP' => array(
		'title' => Loc::getMessage('OP_NAME_DISK_START_BP'),
	),
	'DISK_CREATE_WF' => array(
		'title' => Loc::getMessage('OP_NAME_DISK_CREATE_WF'),
	),
);
