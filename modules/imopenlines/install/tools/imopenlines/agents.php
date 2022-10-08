<?php

$_SERVER["DOCUMENT_ROOT"] = realpath(__DIR__."/../../..");
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/imopenlines/handlers/agents.php"))
{
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/imopenlines/handlers/agents.php");
}