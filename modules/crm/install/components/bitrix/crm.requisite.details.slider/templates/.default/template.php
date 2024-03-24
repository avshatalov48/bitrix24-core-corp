<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/* @var CBitrixComponentTemplate $this */
$errors = $this->getComponent()->getErrors();
if ($errors)
{
	foreach ($errors as $error)
	{
		ShowError($error->getMessage());
	}

	return;
}

/** @var CMain $APPLICATION */
/** @var array $arResult */
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	$arResult['COMPONENT_PARAMS'],
);
