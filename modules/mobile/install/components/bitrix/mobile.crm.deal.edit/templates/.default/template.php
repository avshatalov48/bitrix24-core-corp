<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
CJSCore::Init(array('mobile_crm'));
use \Bitrix\Crm\Conversion\DealConversionScheme;

$mode = $arResult['MODE'];
$formTitle = GetMessage("M_CRM_DEAL_EDIT_{$mode}_TITLE");
$formId = "MOBILE_CRM_DEAL_EDIT_".$arResult['ELEMENT_ID'];
$ajaxPath = '/mobile/?mobile_action=mobile_crm_deal_actions';

$APPLICATION->IncludeComponent(
	'bitrix:main.interface.form',
	'mobile',
	array(
		'FORM_ID' => $formId,
		'THEME_GRID_ID' => $formId,
		'TABS' => array(array(
			"id" => "crm_deal",
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
	"dealViewPath" => $arParams['DEAL_VIEW_URL_TEMPLATE'],
	"mode" => $arResult['MODE'],
	"products" => $arResult["PRODUCT_ROWS"],
	"productDataFieldName" => $arResult["productDataFieldName"],
	"contactInfo" => !empty($arResult['ELEMENT_CONTACT']) ? $arResult['ELEMENT_CONTACT'] : "",
	"companyInfo" => !empty($arResult['ELEMENT_COMPANY']) ? $arResult['ELEMENT_COMPANY'] : "",
	"isRestrictedMode" => $arParams["RESTRICTED_MODE"],
	"onDeleteCompanyEventName" => $arResult["ON_DELETE_COMPANY_EVENT_NAME"],
	"onSelectCompanyEventName" => $arResult["ON_SELECT_COMPANY_EVENT_NAME"],
	"onSelectContactEventName" => $arResult["ON_SELECT_CONTACT_EVENT_NAME"],
	"pageIdProductSelectorBack" => $arResult["PAGEID_PRODUCT_SELECTOR_BACK"]
);

if ($arResult['LEAD_ID'])
{
	$arJsParams["leadId"] = $arResult['LEAD_ID'];
}

if ($arResult['QUOTE_ID'])
{
	$arJsParams["quoteId"] = $arResult['QUOTE_ID'];
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

	BX.Mobile.Crm.Deal.Edit.init(<?=CUtil::PhpToJSObject($arJsParams)?>);

	<?if ($arResult['MODE'] == "VIEW"):?>
	var menu = new BXMobileApp.UI.Menu({
		items: [
			<?if ($arResult["IS_EDIT_PERMITTED"]):?>
			{
				name: '<?=GetMessageJS("M_CRM_DEAL_MENU_EDIT")?>',
				image: "/bitrix/js/mobile/images/edit.png",
				action: BX.proxy(function()
				{
					BXMobileApp.PageManager.loadPageModal({
						url: "<?=CUtil::JSEscape($arResult['DEAL_EDIT_PATH'])?>"
					});
				}, this)
			},
			<?endif?>
			<?if ($arResult["IS_DELETE_PERMITTED"]):?>
			{
				name: '<?=GetMessageJS("M_CRM_DEAL_MENU_DELETE")?>',
				image: "/bitrix/js/mobile/images/del.png",
				action: BX.proxy(function()
				{
					BX.Mobile.Crm.deleteItem('<?=$arResult["ELEMENT_ID"]?>', '<?=$ajaxPath?>', 'detail', 'onCrmDealListUpdate');
				}, this)
			},
			<?endif?>
			<?if ($arResult["IS_EDIT_PERMITTED"]):?>
			{
				name: '<?=GetMessageJS("M_CRM_DEAL_MENU_CREATE_ON_BASE")?>',
				image: "/bitrix/js/mobile/images/base.png",
				action: BX.proxy(function()
				{
					var jsParams = {
						ajaxPath: "<?=$ajaxPath?>",
						entityId: '<?=$arResult["ELEMENT_ID"]?>',
						permissions: {
							invoice: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_INVOICE'])?>,
							quote: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_QUOTE'])?>
						},
						messages: <?=CUtil::PhpToJSObject(DealConversionScheme::getJavaScriptDescriptions(false))?>
					};

					if (!window.dealConverter)
						window.dealConverter = new BX.Mobile.Crm.DealConversionScheme(jsParams);
					else
						window.dealConverter.showActionSheet();
				}, this)
			},
			<?endif?>
			{
				name: '<?=GetMessageJS("M_CRM_DEAL_MENU_HISTORY")?>',
				image: "/bitrix/js/mobile/images/history.png",
				action: BX.proxy(function()
				{
					BX.Mobile.Crm.loadPageModal('<?=CUtil::JSEscape($arResult['EVENT_LIST_URL'])?>');
				}, this)
			},
			{
				name: '<?=GetMessageJS("M_CRM_DEAL_MENU_ACTIVITY")?>',
				image: "/bitrix/js/mobile/images/activity.png",
				action: BX.proxy(function()
				{
					BX.Mobile.Crm.loadPageModal('<?=CUtil::JSEscape($arResult['ACTIVITY_LIST_URL'])?>');
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
				BX.Mobile.Crm.Deal.Edit.submit();
			},
			name: "<?=($arResult['MODE'] == "CONVERT" ? GetMessageJS("M_CRM_DEAL_EDIT_CONTINUE_BTN") : GetMessageJS("M_CRM_DEAL_EDIT_SAVE_BTN"))?>",
			bar_type: "navbar",
			position: "right"
		}
	});
	<?endif?>

	BX.addCustomEvent("onCrmDealDetailUpdate", function(){
		BXMobileApp.UI.Page.reload();
	});

	//for convertaion
	<?if (isset($arResult['CONVERSION_LEGEND']) && !empty($arResult['CONVERSION_LEGEND'])):?>
		app.alert({title: "<?=GetMessageJS("M_CRM_DEAL_CONVERSION_NOTIFY")?>", text: "<?=CUtil::JSEscape($arResult['CONVERSION_LEGEND'])?>"});
	<?endif?>
</script>
