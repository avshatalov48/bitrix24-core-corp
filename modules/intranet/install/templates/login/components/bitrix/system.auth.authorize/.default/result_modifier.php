<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

if ($arResult['ALLOW_QRCODE_AUTH'])
{
	$arResult['QRCODE_TEXT'] = 'https://b24.to/a/'. SITE_ID .'/'. $arResult['QRCODE_UNIQUE_ID'] .'/'. $arResult['QRCODE_CHANNEL_TAG'] .'/';
}