<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$component = $this->__component;

if (!(isset($_REQUEST["IFRAME"]) && $_REQUEST["IFRAME"] === "Y"))
{
	$tbButtons = array(
		array(
			'TEXT' => GetMessage('CRM_CONFIGS_EXCH1C_LINK_TEXT'),
			'TITLE' => GetMessage('CRM_CONFIGS_EXCH1C_LINK_TITLE'),
			'LINK' => $arResult['BACK_URL'],
			'ICON' => 'go-back'
		)
	);
	if (!empty($tbButtons))
	{
		$APPLICATION->IncludeComponent(
			'bitrix:main.interface.toolbar',
			'',
			array(
				'BUTTONS' => $tbButtons
			),
			$component,
			array(
				'HIDE_ICONS' => 'Y'
			)
		);
	}
}

$path = POST_FORM_ACTION_URI;
$path.= mb_strripos(POST_FORM_ACTION_URI, "&")? "&" : "?";
$path.= bitrix_sessid_get();

$APPLICATION->IncludeComponent("bitrix:ui.form", "", array(
	'GUID' => $arResult['FORM_ID'],
	'CONFIG_ID' => $arResult['FORM_ID'],
	'IS_IDENTIFIABLE_ENTITY' => false,
	'INITIAL_MODE' => 'edit',
	'ENTITY_FIELDS' => $arResult['FIELDS'],
	'ENTITY_CONFIG' => $arResult['FIELDS_CONFIG'],
	'ENTITY_DATA' => $arResult['FIELDS_DATA'],
	"ENABLE_BOTTOM_PANEL" => false,
	'SERVICE_URL' => $path,
));
?>