<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
CJSCore::Init(array('mobile_crm'));
$APPLICATION->AddHeadScript('/bitrix/js/crm/common.js');

use \Bitrix\Crm\Conversion\LeadConversionScheme;

$mode = $arResult['MODE'];
$formTitle = GetMessageJS("M_CRM_LEAD_EDIT_{$mode}_TITLE");
$formId = "MOBILE_CRM_LEAD_EDIT_".$arResult['ELEMENT_ID'];
$ajaxPath = '/mobile/?mobile_action=mobile_crm_lead_actions';

$APPLICATION->IncludeComponent(
	'bitrix:main.interface.form',
	'mobile',
	array(
		'FORM_ID' => $formId,
		'THEME_GRID_ID' => $formId,
		'TABS' => $arResult['FIELDS'],
		'RESTRICTED_MODE' => $arParams['RESTRICTED_MODE'],
		'COMPONENT_NAME' => 'crm.lead.show'
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);
$arJsParams = array(
	"ajaxPath" => $ajaxPath,
	"formActionUrl" => $APPLICATION->GetCurPageParam(),
	"formId" => $formId,
	"leadViewPath" => $arParams["LEAD_VIEW_URL_TEMPLATE"],
	"mode" => $arResult['MODE'],
	"products" => $arResult["PRODUCT_ROWS"],
	"productDataFieldName" => $arResult["productDataFieldName"],
	"pageIdProductSelectorBack" => $arResult["PAGEID_PRODUCT_SELECTOR_BACK"]
);
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

	BX.Mobile.Crm.Lead.Edit.init(<?=CUtil::PhpToJSObject($arJsParams)?>);

	<?if ($arResult['MODE'] == "VIEW"):?>
	var menu = new BXMobileApp.UI.Menu({
		items: [
			<?if ($arResult["IS_EDIT_PERMITTED"]):?>
			{
				name: '<?=GetMessageJS("M_CRM_LEAD_MENU_EDIT")?>',
				image: "/bitrix/js/mobile/images/edit.png",
				action: BX.proxy(function()
				{
					BXMobileApp.PageManager.loadPageModal({
						url: "<?=CUtil::JSEscape($arResult['LEAD_EDIT_PATH'])?>"
					});
				}, this)
			},
			<?endif?>
			<?if ($arResult["IS_DELETE_PERMITTED"]):?>
			{
				name: '<?=GetMessageJS("M_CRM_LEAD_MENU_DELETE")?>',
				image: "/bitrix/js/mobile/images/del.png",
				action: BX.proxy(function()
				{
					BX.Mobile.Crm.deleteItem('<?=$arResult["ELEMENT_ID"]?>', '<?=$ajaxPath?>', 'detail', 'onCrmLeadListUpdate');
				}, this)
			},
			<?endif?>
			<?if ($arResult["IS_EDIT_PERMITTED"]):?>
			{
				name: '<?=GetMessageJS("M_CRM_LEAD_MENU_CREATE_ON_BASE")?>',
				image: "/bitrix/js/mobile/images/base.png",
				action: BX.proxy(function()
				{
					BX.message({
						"M_CRM_LEAD_CONV_OPEN_ENTITY_SEL_CONTACT" : '<?=GetMessageJS("M_CRM_LEAD_CONV_OPEN_ENTITY_SEL_CONTACT")?>',
						"M_CRM_LEAD_CONV_OPEN_ENTITY_SEL_COMPANY" : '<?=GetMessageJS("M_CRM_LEAD_CONV_OPEN_ENTITY_SEL_COMPANY")?>'
					});

					var jsParams = {
						ajaxPath: "<?=$ajaxPath?>",
						entityId: '<?=$arResult["ELEMENT_ID"]?>',
						permissions: {
							contact: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_CONTACT'])?>,
							company: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_COMPANY'])?>,
							deal: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_DEAL'])?>
						},
						messages: <?=CUtil::PhpToJSObject(LeadConversionScheme::getJavaScriptDescriptions(false))?>,
						contactSelectUrl: '<?=CUtil::JSEscape($arParams["CONTACT_SELECTOR_URL_TEMPLATE"])?>',
						companySelectUrl: '<?=CUtil::JSEscape($arParams["COMPANY_SELECTOR_URL_TEMPLATE"])?>'
					};

					if (!window.leadConverter)
						window.leadConverter = new BX.Mobile.Crm.LeadConversionScheme(jsParams);
					else
						window.leadConverter.showActionSheet();
				}, this)
			},
			<?endif?>

			{
				name: '<?=GetMessageJS("M_CRM_LEAD_MENU_HISTORY")?>',
				image: "/bitrix/js/mobile/images/history.png",
				action: BX.proxy(function()
				{
					BX.Mobile.Crm.loadPageModal('<?=CUtil::JSEscape($arResult['EVENT_LIST_URL'])?>');
				}, this)
			},
			{
				name: '<?=GetMessageJS("M_CRM_LEAD_MENU_ACTIVITY")?>',
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

	BX.addCustomEvent("onCrmLeadDetailUpdate", function(){
		BXMobileApp.UI.Page.reload();
	});

	<?if ($arResult['MODE'] == "EDIT" || $arResult['MODE'] == "CREATE"):?>
	window.BXMobileApp.UI.Page.TopBar.updateButtons({
		ok: {
			type: "back_text",
			callback: function(){
				BX.Mobile.Crm.Lead.Edit.submit();
			},
			name: "<?=GetMessageJS("M_CRM_LEAD_EDIT_SAVE_BTN")?>",
			bar_type: "navbar",
			position: "right"
		}
	});
	<?endif?>
</script>