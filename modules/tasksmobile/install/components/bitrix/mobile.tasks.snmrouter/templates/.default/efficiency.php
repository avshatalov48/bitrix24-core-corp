<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var CMain $APPLICATION
 * @var array $arParams
 * @var CBitrixComponentTemplate $this
 */

$APPLICATION->IncludeComponent(
	'bitrix:tasks.report.effective',
	'',
	$arParams,
	$this->__component,
	['HIDE_ICONS' => 'Y']
);