<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

return array(
	"WEBDAV_CHANGE_SETTINGS" => array(
		"title" => Loc::getMessage('OP_NAME_WEBDAV_CHANGE_SETTINGS'),
	),
);
