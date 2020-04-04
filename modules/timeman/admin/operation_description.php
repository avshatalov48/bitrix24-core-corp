<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

return array(
	"TM_MANAGE" => array(
		"title" => Loc::getMessage('OP_NAME_TM_MANAGE'),
		"description" => Loc::getMessage('OP_DESC_TM_MANAGE'),
	),
	"TM_MANAGE_ALL" => array(
		"title" => Loc::getMessage('OP_NAME_TM_MANAGE_ALL'),
		"description" => Loc::getMessage('OP_DESC_TM_MANAGE_ALL'),
	),
	"TM_READ_SUBORDINATE" => array(
		"title" => Loc::getMessage('OP_NAME_TM_READ_SUBORDINATE'),
		"description" => Loc::getMessage('OP_DESC_TM_READ_SUBORDINATE'),
	),
	"TM_READ" => array(
		"title" => Loc::getMessage('OP_NAME_TM_READ'),
		"description" => Loc::getMessage('OP_DESC_TM_READ'),
	),
	"TM_WRITE_SUBORDINATE" => array(
		"title" => Loc::getMessage('OP_NAME_TM_WRITE_SUBORDINATE'),
		"description" => Loc::getMessage('OP_DESC_TM_WRITE_SUBORDINATE'),
	),
	"TM_WRITE" => array(
		"title" => Loc::getMessage('OP_NAME_TM_WRITE'),
		"description" => Loc::getMessage('OP_DESC_TM_WRITE'),
	),
	"TM_SETTINGS" => array(
		"title" => Loc::getMessage('OP_NAME_TM_SETTINGS'),
		"description" => Loc::getMessage('OP_DESC_TM_SETTINGS'),
	),
);
