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

$customButtons = '<input type="submit" name="save" value="'.htmlspecialcharsbx(GetMessage("CRM_BUTTON_SAVE")).'" title="'.htmlspecialcharsbx(GetMessage("CRM_BUTTON_SAVE_TITLE")).'" />';
//$customButtons .= '<input type="button" name="cancel" value="'.htmlspecialcharsbx(GetMessage("CRM_BUTTON_CANCEL")).'" title="'.htmlspecialcharsbx(GetMessage("CRM_BUTTON_CANCEL_TITLE")).'" onclick="window.location=\''.htmlspecialcharsbx($arResult['BACK_URL']).'\'" />';

$sections = array(
	array(
		'ID' => 'tab_catalog_import',
		"TITLE" => GetMessage('CRM_TAB_CATALOG_IMPORT'),
		"FIELDS" => $arResult['FIELDS']['tab_catalog_import']
	),
	array(
		'ID' => 'tab_catalog_export',
		"TITLE" => GetMessage('CRM_TAB_CATALOG_EXPORT'),
		"FIELDS" => $arResult['FIELDS']['tab_catalog_export']
	)
);
$APPLICATION->IncludeComponent("bitrix:ui.form", "", array(
	'FORM_ID' => $arResult['FORM_ID'],
	"SECTIONS" => $sections,
	'BUTTONS' => array(
		'standard_buttons' =>  array("save")
	)
));
?>