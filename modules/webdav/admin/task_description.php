<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

return array(
	"WEBDAV_FULL_ACCESS" => array(
		"title" => Loc::getMessage('TASK_NAME_WEBDAV_FULL_ACCESS'),
	),
);
