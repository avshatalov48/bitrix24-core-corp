<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

return array(
	"TIMEMAN_DENIED" => array(
		"title" => Loc::getMessage('TASK_NAME_TIMEMAN_DENIED'),
		"description" => Loc::getMessage('TASK_DESC_TIMEMAN_DENIED'),
	),
	"TIMEMAN_SUBORDINATE" => array(
		"title" => Loc::getMessage('TASK_NAME_TIMEMAN_SUBORDINATE'),
		"description" => Loc::getMessage('TASK_DESC_TIMEMAN_SUBORDINATE'),
	),
	"TIMEMAN_READ" => array(
		"title" => Loc::getMessage('TASK_NAME_TIMEMAN_READ'),
		"description" => Loc::getMessage('TASK_DESC_TIMEMAN_READ'),
	),
	"TIMEMAN_WRITE" => array(
		"title" => Loc::getMessage('TASK_NAME_TIMEMAN_WRITE'),
		"description" => Loc::getMessage('TASK_DESC_TIMEMAN_WRITE'),
	),
	"TIMEMAN_FULL_ACCESS" => array(
		"title" => Loc::getMessage('TASK_NAME_TIMEMAN_FULL_ACCESS'),
		"description" => Loc::getMessage('TASK_DESC_TIMEMAN_FULL_ACCESS'),
	),
);
