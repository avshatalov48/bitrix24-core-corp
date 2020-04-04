<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
if($arResult['ENABLE_CONTROL_PANEL'])
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.control_panel',
		'',
		array(
			'ID' => 'SEND_AND_SAVE',
			'ACTIVE_ITEM_ID' => '',
			'PATH_TO_COMPANY_LIST' => isset($arParams['PATH_TO_COMPANY_LIST']) ? $arParams['PATH_TO_COMPANY_LIST'] : '',
			'PATH_TO_COMPANY_EDIT' => isset($arParams['PATH_TO_COMPANY_EDIT']) ? $arParams['PATH_TO_COMPANY_EDIT'] : '',
			'PATH_TO_CONTACT_LIST' => isset($arParams['PATH_TO_CONTACT_LIST']) ? $arParams['PATH_TO_CONTACT_LIST'] : '',
			'PATH_TO_CONTACT_EDIT' => isset($arParams['PATH_TO_CONTACT_EDIT']) ? $arParams['PATH_TO_CONTACT_EDIT'] : '',
			'PATH_TO_DEAL_LIST' => isset($arParams['PATH_TO_DEAL_LIST']) ? $arParams['PATH_TO_DEAL_LIST'] : '',
			'PATH_TO_DEAL_EDIT' => isset($arParams['PATH_TO_DEAL_EDIT']) ? $arParams['PATH_TO_DEAL_EDIT'] : '',
			'PATH_TO_LEAD_LIST' => isset($arParams['PATH_TO_LEAD_LIST']) ? $arParams['PATH_TO_LEAD_LIST'] : '',
			'PATH_TO_LEAD_EDIT' => isset($arParams['PATH_TO_LEAD_EDIT']) ? $arParams['PATH_TO_LEAD_EDIT'] : '',
			'PATH_TO_QUOTE_LIST' => isset($arResult['PATH_TO_QUOTE_LIST']) ? $arResult['PATH_TO_QUOTE_LIST'] : '',
			'PATH_TO_QUOTE_EDIT' => isset($arResult['PATH_TO_QUOTE_EDIT']) ? $arResult['PATH_TO_QUOTE_EDIT'] : '',
			'PATH_TO_INVOICE_LIST' => isset($arResult['PATH_TO_INVOICE_LIST']) ? $arResult['PATH_TO_INVOICE_LIST'] : '',
			'PATH_TO_INVOICE_EDIT' => isset($arResult['PATH_TO_INVOICE_EDIT']) ? $arResult['PATH_TO_INVOICE_EDIT'] : '',
			'PATH_TO_REPORT_LIST' => isset($arParams['PATH_TO_REPORT_LIST']) ? $arParams['PATH_TO_REPORT_LIST'] : '',
			'PATH_TO_DEAL_FUNNEL' => isset($arParams['PATH_TO_DEAL_FUNNEL']) ? $arParams['PATH_TO_DEAL_FUNNEL'] : '',
			'PATH_TO_EVENT_LIST' => isset($arParams['PATH_TO_EVENT_LIST']) ? $arParams['PATH_TO_EVENT_LIST'] : '',
			'PATH_TO_PRODUCT_LIST' => isset($arParams['PATH_TO_PRODUCT_LIST']) ? $arParams['PATH_TO_PRODUCT_LIST'] : ''
		),
		$component
	);
}

$editMode = isset($arResult['SETTINGS']['MAILBOX_ID']) && intval($arResult['SETTINGS']['MAILBOX_ID']) > 0;

$arTabs[] = array(
	'id' => 'tab_config',
	'name' => GetMessage('CRM_TAB_CONFIG'),
	'title' =>
		GetMessage(
			$editMode ? 'CRM_TAB_CONFIG_TITLE_EDIT' : 'CRM_TAB_CONFIG_TITLE_CREATE'
		),
	'icon' => '',
	'fields' => $arResult['FIELDS']['tab_config']
);

$customButtons = '<input type="submit" name="save" value="'.htmlspecialcharsbx(GetMessage("CRM_BUTTON_SAVE")).'" title="'.htmlspecialcharsbx(GetMessage("CRM_BUTTON_SAVE_TITLE")).'" />';
if($editMode)
{
	$customButtons .= '<input type="submit" name="delete" value="'.htmlspecialcharsbx(GetMessage("CRM_BUTTON_DELETE")).'" title="'.htmlspecialcharsbx(GetMessage("CRM_BUTTON_DELETE_TITLE")).'" />';
}
$customButtons .= '<input type="button" name="cancel" value="'.htmlspecialcharsbx(GetMessage("CRM_BUTTON_CANCEL")).'" title="'.htmlspecialcharsbx(GetMessage("CRM_BUTTON_CANCEL_TITLE")).'" onclick="window.location=\''.htmlspecialcharsbx($arResult['BACK_URL']).'\'" />';
$customButtons .= '<input type="hidden" name="MAILBOX_ID" value="'.htmlspecialcharsbx(isset($arResult['SETTINGS']['MAILBOX_ID']) ? $arResult['SETTINGS']['MAILBOX_ID'] : 0).'" />';
?>

<div class="crm-config-sendsave">
<?
$APPLICATION->IncludeComponent(
	'bitrix:main.interface.form',
	'',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'TABS' => $arTabs,
		'BUTTONS' => array(
			'standard_buttons' =>  false,
			'custom_html' => $customButtons
			//'back_url' => $arResult['BACK_URL']
		),
		'DATA' => $arResult['ELEMENT'],
		'SHOW_SETTINGS' => 'N'
	),
	$component, array('HIDE_ICONS' => 'Y')
);

$editorSettings = array(
	'FORM_ID' => 'form_'.$arResult['FORM_ID'],
	'MAILBOXES' => array_values($arResult['SETTINGS']['MAILBOXES'])
);
?>
</div>
<script type="text/javascript">
BX.ready(
	function()
	{
		BX.CrmSendSaveEditor.createDefault(<?= CUtil::PhpToJSObject($editorSettings);?>);
	}
);
</script>
