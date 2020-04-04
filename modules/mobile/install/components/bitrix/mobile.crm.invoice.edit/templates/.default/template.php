<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
CJSCore::Init(array('mobile_crm'));

$mode = $arResult['MODE'];
$formTitle = GetMessage("M_CRM_INVOICE_EDIT_{$mode}_TITLE");
$formId = "MOBILE_CRM_INVOICE_EDIT_".$arResult['ELEMENT_ID'];
$ajaxPath = '/mobile/?mobile_action=mobile_crm_invoice_actions';

$APPLICATION->IncludeComponent(
	'bitrix:main.interface.form',
	'mobile',
	array(
		'FORM_ID' => $formId,
		'THEME_GRID_ID' => $formId,
		'TABS' => array(array(
			"id" => "crm_invoice",
			"fields" => $arResult['FIELDS']
		)),
		"RESTRICTED_MODE" => $arParams["RESTRICTED_MODE"]
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);

$arJsParams = array(
	"ajaxPath" => $ajaxPath,
	"formActionUrl" => $APPLICATION->GetCurPageParam(),
	"formId" => $formId,
	"invoiceId" => $arResult['ELEMENT_ID'],
	"invoiceAccountNumber" => $arResult['ELEMENT']['ACCOUNT_NUMBER'],
	"invoiceViewPath" => $arParams['INVOICE_VIEW_URL_TEMPLATE'],
	"mode" => $arResult['MODE'],
	"products" => $arResult["PRODUCT_ROWS"],
	"productDataFieldName" => $arResult["productDataFieldName"],
	"contactInfo" => $arResult['ELEMENT_CONTACT'],
	"clientInfo" => $arResult['ELEMENT_CLIENT'],
	"clientPrefix" => $arResult['ELEMENT_CLIENT_PREFIX'],
	"clientType" => $arResult['ELEMENT_CLIENT_TYPE'],
	"dealInfo" => !empty($arResult['ELEMENT_DEAL']) ? $arResult['ELEMENT_DEAL'] : "",
	"quoteInfo" => !empty($arResult['ELEMENT_QUOTE']) ? $arResult['ELEMENT_QUOTE'] : "",
	"isRestrictedMode" => $arParams["RESTRICTED_MODE"],
	"pageId" => $arResult["pageId"],
	"onDeleteClientEventName" => $arResult["ON_DELETE_CLIENT_EVENT_NAME"],
	"onSelectClientEventName" => $arResult["ON_SELECT_CLIENT_EVENT_NAME"],
	"onSelectContactEventName" => $arResult["ON_SELECT_CONTACT_EVENT_NAME"],
	"onSelectQuoteEventName" => $arResult["ON_SELECT_QUOTE_EVENT_NAME"],
	"onSelectDealEventName" => $arResult["ON_SELECT_DEAL_EVENT_NAME"],
	"emailEditUrl" => $arResult['ELEMENT_CLIENT_TYPE'] === "CONTACT" ? $arResult['CONTACT_EMAIL_EDIT_URL'] : $arResult['COMPANY_EMAIL_EDIT_URL'],
	"statusSort" => $arResult['STATUS_SORT'],
	"pageIdProductSelectorBack" => $arResult["PAGEID_PRODUCT_SELECTOR_BACK"]
);

if ($arResult['DEAL_ID'])
{
	$arJsParams["convDealId"] = $arResult['DEAL_ID'];
}

if ($arResult['QUOTE_ID'])
{
	$arJsParams["convQuoteId"] = $arResult['QUOTE_ID'];
}

if ($arParams["RESTRICTED_MODE"])
{
	$arJsParams["emailSubject"] = $arResult['EMAIL_SUBJECT'];
	//$arJsParams["contextId"] = $arResult['CONTEXT_ID'];
}
?>

<script>
	app.pullDown({
		enable:   true,
		pulltext: '<?=GetMessageJS('M_DETAIL_PULL_TEXT');?>',
		downtext: '<?=GetMessageJS('M_DETAIL_DOWN_TEXT');?>',
		loadtext: '<?=GetMessageJS('M_DETAIL_LOAD_TEXT');?>',
		callback: function()
		{
			app.reload();
		}
	});

	BX.ready(function(){
		BX.Mobile.Crm.Invoice.Edit.init(<?=CUtil::PhpToJSObject($arJsParams)?>);
	});

	window.CrmProductRowSetLocation = function(){ BX.onCustomEvent('CrmProductRowSetLocation', ['LOC_CITY']); };

	<?if ($arResult['MODE'] == "VIEW"):?>
	var menu = new BXMobileApp.UI.Menu({
		items: [
			<?if ($arResult["IS_EDIT_PERMITTED"]):?>
			{
				name: '<?=GetMessageJS("M_CRM_INVOICE_MENU_EDIT")?>',
				image: "/bitrix/js/mobile/images/edit.png",
				action: BX.proxy(function()
				{
					BXMobileApp.PageManager.loadPageModal({
						url: "<?=CUtil::JSEscape($arResult['INVOICE_EDIT_PATH'])?>"
					});
				}, this)
			},
			<?endif?>

			<?if ($arResult["IS_DELETE_PERMITTED"]):?>
			{
				name: '<?=GetMessageJS("M_CRM_INVOICE_MENU_DELETE")?>',
				image: "/bitrix/js/mobile/images/del.png",
				action: BX.proxy(function()
				{
					BX.Mobile.Crm.deleteItem('<?=$arResult["ELEMENT_ID"]?>', '<?=$ajaxPath?>', 'detail', 'onCrmInvoiceListUpdate');
				}, this)
			},
			<?endif?>

			{
				name: '<?=GetMessageJS("M_CRM_INVOICE_MENU_SEND_EMAIL")?>',
				image: "/bitrix/js/mobile/images/send-email.png",
				action: BX.proxy(function()
				{
					BX.Mobile.Crm.Invoice.Edit.getInvoicePdfContent('<?=$arResult["ELEMENT_ID"]?>', '<?=CUtil::JSEscape($arResult['ELEMENT']['ACCOUNT_NUMBER'])?>');
				}, this)
			},
			{
				name: '<?=GetMessageJS("M_CRM_INVOICE_MENU_HISTORY")?>',
				image: "/bitrix/js/mobile/images/history.png",
				action: BX.proxy(function()
				{
					BX.Mobile.Crm.loadPageModal('<?=CUtil::JSEscape($arResult['EVENT_LIST_URL'])?>');
				}, this)
			}
		]
	}, "crmMobileMenu");
	<?endif?>

	BXMobileApp.UI.Page.TopBar.title.setText('<?=$formTitle?>');
	BXMobileApp.UI.Page.TopBar.title.show();

	<?if ($arResult['MODE'] == "VIEW"):?>
	BXMobileApp.UI.Page.TopBar.title.setCallback(function (){
		menu.show();
	});
	<?endif?>

	<?if ($arResult['MODE'] == "EDIT" || $arResult['MODE'] == "CREATE" || $arResult['MODE'] == "CONVERT"):?>
	window.BXMobileApp.UI.Page.TopBar.updateButtons({
		ok: {
			type: "back_text",
			callback: function(){
				BX.Mobile.Crm.Invoice.Edit.submit();
			},
			name: "<?=($arResult['MODE'] == "CONVERT" ? GetMessageJS("M_CRM_INVOICE_EDIT_CONTINUE_BTN") : GetMessageJS("M_CRM_INVOICE_EDIT_SAVE_BTN"))?>",
			bar_type: "navbar",
			position: "right"
		}
	});
	<?endif?>

	//for convertaion
	<?if (isset($arResult['CONVERSION_LEGEND']) && !empty($arResult['CONVERSION_LEGEND'])):?>
	app.alert({title: "<?=GetMessageJS("M_CRM_INVOICE_CONVERSION_NOTIFY")?>", text: "<?=CUtil::JSEscape($arResult['CONVERSION_LEGEND'])?>"});
	<?endif?>

	BX.addCustomEvent("onCrmInvoiceDetailUpdate", function(){
		BXMobileApp.UI.Page.reload();
	});

	BX.message({
		"M_CRM_INVOICE_SELECT" : "<?=GetMessageJS("M_CRM_INVOICE_SELECT")?>"
	});
</script>
