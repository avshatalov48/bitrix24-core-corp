<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @global CMain $APPLICATION
 * @var array $arParams
 */

$this->__component->arResult = $APPLICATION->IncludeComponent(
	'bitrix:tasksmobile.fragment.renderer',
	'.default',
	$arParams,
	$this->__component
);