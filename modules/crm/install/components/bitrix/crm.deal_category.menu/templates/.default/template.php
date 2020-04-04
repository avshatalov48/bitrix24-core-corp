<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;
if (!empty($arResult['BUTTONS']))
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		'',
		array(
			'BUTTONS' => $arResult['BUTTONS']
		),
		$component,
		array(
			'HIDE_ICONS' => 'Y'
		)
	);
}
?>