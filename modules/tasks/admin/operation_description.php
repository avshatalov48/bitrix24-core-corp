<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

// map action task operations into their lang names

return array(
	"READ" => array(
		"title" => Loc::getMessage("OP_NAME_READ"),
		"description" => Loc::getMessage("OP_NAME_READ")
	),
	"UPDATE" => array(
		"title" => Loc::getMessage("OP_NAME_UPDATE"),
		"description" => Loc::getMessage("OP_NAME_UPDATE")
	),
	"DELETE" => array(
		"title" => Loc::getMessage("OP_NAME_DELETE"),
		"description" => Loc::getMessage("OP_NAME_DELETE")
	),
);