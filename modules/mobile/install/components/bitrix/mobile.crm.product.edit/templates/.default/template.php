<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
CJSCore::Init(array('mobile_crm'));
$APPLICATION->AddHeadScript('/bitrix/js/crm/common.js');

$mode = $arResult['MODE'];
$formTitle = GetMessage("M_CRM_PRODUCT_EDIT_{$mode}_TITLE");
$formId = "MOBILE_CRM_PRODUCT_EDIT_".$arResult['ELEMENT_ID'];
$ajaxPath = '/mobile/?mobile_action=mobile_crm_product_actions';

$APPLICATION->IncludeComponent(
	'bitrix:main.interface.form',
	'mobile',
	array(
		'FORM_ID' => $formId,
		'THEME_GRID_ID' => $formId,
		'TABS' => array(array(
			"id" => "crm_product",
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
	"productViewPath" => $arParams["PRODUCT_VIEW_URL_TEMPLATE"],
	"mode" => $arResult['MODE']
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

	BX.Mobile.Crm.Product.Edit.init(<?=CUtil::PhpToJSObject($arJsParams)?>);

	<?if ($arResult['MODE'] == "VIEW"):?>
	var menu = new BXMobileApp.UI.Menu({
		items: [
			<?if ($arResult["IS_EDIT_PERMITTED"]):?>
			{
				name: '<?=GetMessageJS("M_CRM_PRODUCT_MENU_EDIT")?>',
				image: "/bitrix/js/mobile/images/edit.png",
				action: BX.proxy(function()
				{
					BXMobileApp.PageManager.loadPageModal({
						url: "<?=CUtil::JSEscape($arResult['PRODUCT_EDIT_PATH'])?>"
					});
				}, this)
			},
			<?endif?>
			<?if ($arResult["IS_DELETE_PERMITTED"]):?>
			{
				name: '<?=GetMessageJS("M_CRM_PRODUCT_MENU_DELETE")?>',
				image: "/bitrix/js/mobile/images/del.png",
				action: BX.proxy(function()
				{
					BX.Mobile.Crm.deleteItem('<?=$arResult["ELEMENT_ID"]?>', '<?=$ajaxPath?>', 'detail', 'onCrmProductListUpdate');
				}, this)
			}
			<?endif?>
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

	<?if ($arResult['MODE'] == "EDIT" || $arResult['MODE'] == "CREATE"):?>
	window.BXMobileApp.UI.Page.TopBar.updateButtons({
		ok: {
			type: "back_text",
			callback: function(){
				BX.Mobile.Crm.Product.Edit.submit();
			},
			name: "<?=GetMessageJS("M_CRM_PRODUCT_EDIT_SAVE_BTN")?>",
			bar_type: "navbar",
			position: "right"
		}
	});
	<?endif?>
</script>