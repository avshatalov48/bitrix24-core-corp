<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
CJSCore::Init(array('mobile_crm'));

$mode = $arResult['MODE'];
$formTitle = GetMessage("M_CRM_COMPANY_EDIT_{$mode}_TITLE");
$formId = "MOBILE_CRM_COMPANY_EDIT_".$arResult['ELEMENT_ID'];
$ajaxPath = '/mobile/?mobile_action=mobile_crm_company_actions';

$APPLICATION->IncludeComponent(
	'bitrix:main.interface.form',
	'mobile',
	array(
		'FORM_ID' => $formId,
		'THEME_GRID_ID' => $formId,
		'TABS' => array(array(
			"id" => "crm_company",
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
	"companyViewPath" => $arParams['COMPANY_VIEW_URL_TEMPLATE'],
	"mode" => $arResult['MODE'],
	"products" => $arResult["PRODUCT_ROWS"],
	"productDataFieldName" => $arResult["productDataFieldName"],
	"contactInfo" => !empty($arResult['ELEMENT_CONTACTS']) ? $arResult['ELEMENT_CONTACTS'] : "",
	"isRestrictedMode" => $arParams["RESTRICTED_MODE"]
);

if ($arResult['LEAD_ID'])
{
	$arJsParams["leadId"] = $arResult['LEAD_ID'];
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

	BX.Mobile.Crm.Company.Edit.init(<?=CUtil::PhpToJSObject($arJsParams)?>);

	<?if ($arResult['MODE'] == "VIEW"):?>
	var menu = new BXMobileApp.UI.Menu({
		items: [
			<?if ($arResult["IS_EDIT_PERMITTED"]):?>
			{
				name: '<?=GetMessageJS("M_CRM_COMPANY_MENU_EDIT")?>',
				image: "/bitrix/js/mobile/images/edit.png",
				action: BX.proxy(function()
				{
					BXMobileApp.PageManager.loadPageModal({
						url: "<?=CUtil::JSEscape($arResult['COMPANY_EDIT_PATH'])?>"
					});
				}, this)
			},
			<?endif?>
			<?if ($arResult["IS_DELETE_PERMITTED"]):?>
			{
				name: '<?=GetMessageJS("M_CRM_COMPANY_MENU_DELETE")?>',
				image: "/bitrix/js/mobile/images/del.png",
				action: BX.proxy(function()
				{
					BX.Mobile.Crm.deleteItem('<?=$arResult["ELEMENT_ID"]?>', '<?=$ajaxPath?>', 'detail', 'onCrmCompanyListUpdate');
				}, this)
			},
			<?endif?>
			{
				name: '<?=GetMessageJS("M_CRM_COMPANY_MENU_HISTORY")?>',
				image: "/bitrix/js/mobile/images/history.png",
				action: BX.proxy(function()
				{
					BX.Mobile.Crm.loadPageModal('<?=CUtil::JSEscape($arResult['EVENT_LIST_URL'])?>');
				}, this)
			},
			{
				name: '<?=GetMessageJS("M_CRM_COMPANY_MENU_ACTIVITY")?>',
				image: "/bitrix/js/mobile/images/activity.png",
				action: BX.proxy(function()
				{
					BX.Mobile.Crm.loadPageModal('<?=CUtil::JSEscape($arResult['ACTIVITY_LIST_URL'])?>');
				}, this)
			},
			{
				name: '<?=GetMessageJS("M_CRM_COMPANY_MENU_DEALS")?>',
				image: "/bitrix/js/mobile/images/deals.png",
				action: BX.proxy(function()
				{
					BX.Mobile.Crm.loadPageBlank('<?=CUtil::JSEscape($arResult['DEAL_LIST_URL'])?>');
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
				BX.Mobile.Crm.Company.Edit.submit();
			},
			name: "<?=($arResult['MODE'] == "CONVERT" ? GetMessageJS("M_CRM_COMPANY_EDIT_CONTINUE_BTN") : GetMessageJS("M_CRM_COMPANY_EDIT_SAVE_BTN"))?>",
			bar_type: "navbar",
			position: "right"
		}
	});
	<?endif?>

	//for convertaion
	<?if (isset($arResult['CONVERSION_LEGEND']) && !empty($arResult['CONVERSION_LEGEND'])):?>
	app.alert({title: "<?=GetMessageJS("M_CRM_COMPANY_CONVERSION_NOTIFY")?>", text: "<?=CUtil::JSEscape($arResult['CONVERSION_LEGEND'])?>"});
	<?endif?>
</script>