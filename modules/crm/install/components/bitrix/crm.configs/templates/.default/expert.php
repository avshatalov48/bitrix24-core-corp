<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
global $APPLICATION;

$APPLICATION->IncludeComponent(
	'bitrix:crm.features.list',
	'',
	[]
);
