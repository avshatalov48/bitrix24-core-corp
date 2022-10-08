<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load(['ui.design-tokens']);

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

$arTabs[] = array(
	'id' => 'tab_invoice_export',
	'name' => GetMessage('CRM_TAB_INVOICE_EXPORT'),
	'title' => GetMessage('CRM_TAB_INVOICE_EXPORT_TITLE'),
	'icon' => '',
	'fields' => $arResult['FIELDS']['tab_invoice_export']
);
$arTabs[] = array(
	'id' => 'tab_invoice_prof_com',
	'name' => GetMessage('CRM_TAB_INVOICE_PROF_COM'),
	'title' => GetMessage('CRM_TAB_INVOICE_PROF_COM_TITLE'),
	'icon' => '',
	'fields' => $arResult['FIELDS']['tab_invoice_prof_com']
);
$arTabs[] = array(
	'id' => 'tab_invoice_prof_con',
	'name' => GetMessage('CRM_TAB_INVOICE_PROF_CON'),
	'title' => GetMessage('CRM_TAB_INVOICE_PROF_CON_TITLE'),
	'icon' => '',
	'fields' => $arResult['FIELDS']['tab_invoice_prof_con']
);

$customButtons = '<input type="submit" name="save" value="'.htmlspecialcharsbx(GetMessage("CRM_BUTTON_SAVE")).'" title="'.htmlspecialcharsbx(GetMessage("CRM_BUTTON_SAVE_TITLE")).'" />';
$customButtons .= '<input type="button" name="cancel" value="'.htmlspecialcharsbx(GetMessage("CRM_BUTTON_CANCEL")).'" title="'.htmlspecialcharsbx(GetMessage("CRM_BUTTON_CANCEL_TITLE")).'" onclick="window.location=\''.htmlspecialcharsbx($arResult['BACK_URL']).'\'" />';
?>

<div class="crm-config-exch1c">
<?
$APPLICATION->IncludeComponent(
	'bitrix:main.interface.form',
	'',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'TABS' => $arTabs,
		'BUTTONS' => array(
			'standard_buttons' =>  false,
			'custom_html' => $customButtons,
			'back_url' => '/crm/exch1c'
		),
		'DATA' => $arResult['ELEMENT'],
		'SHOW_SETTINGS' => 'N'
	),
	$component, array('HIDE_ICONS' => 'Y')
);
?>
<script>
BX.ready(function(){
	BX.crmExch1cInvMan = new BX.CrmExch1cInvManager(<?= CUtil::PhpToJSObject($arResult['EXCH1C_MAN_SETTINGS']) ?>);
	BX.crmExch1cInvMan.BuildRekvBlocks();
	BX.crmExch1cInvMan.HideEmptyFields();
	BX.crmExch1cInvMan.ShowAccountNumberWarning();
});
</script>
</div>
