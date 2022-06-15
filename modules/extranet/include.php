<?php

if (!\Bitrix\Main\Loader::includeModule('intranet'))
{
	return false;
}

$arClasses = [
	"CExtranet" => "classes/general/extranet.php",
	"CUsersInMyGroupsCache" => "classes/general/extranet.php",
	"extranet" => "install/index.php",
];
CModule::AddAutoloadClasses("extranet", $arClasses);

global $obUsersCache;
$obUsersCache = new CUsersInMyGroupsCache;
