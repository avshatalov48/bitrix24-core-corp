<?php

$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__)."/../../..");
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/imopenlines/handlers/agents.php"))
{
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/imopenlines/handlers/agents.php");
}