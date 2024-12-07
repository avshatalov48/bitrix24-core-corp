<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var CMain $APPLICATION */

$APPLICATION->IncludeComponent(
	'bitrix:crm.communicationchannel.rule.list',
	'',
	[],
	false
);
