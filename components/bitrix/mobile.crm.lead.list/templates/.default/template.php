<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
CJSCore::Init(array('mobile_crm'));

$APPLICATION->IncludeComponent("bitrix:mobile.interface.grid", "", array(
	"GRID_ID"=> $arParams["GRID_ID"],
	"FIELDS" => $arResult["FIELDS"],
	"ITEMS" => $arResult["ITEMS"],
	"SORT_EVENT_NAME" => "onLeadListSort",
	"FIELDS_EVENT_NAME" => "onLeadListFields",
	"FILTER_EVENT_NAME" => "onLeadListFilter",
	"NAV_PARAMS" => $arResult["NAV_PARAM"],
	"AJAX_PAGE_PATH" => "/mobile/crm/lead/?page=list",
	"SHOW_SEARCH" => "Y"
));

$arJsParams = array(
	"ajaxPath" => $arResult["AJAX_PATH"],
	"sortPath" => "/mobile/crm/lead/?page=sort",
	"fieldsPath" => "/mobile/crm/lead/?page=fields",
	"filterPath" => "/mobile/crm/lead/?page=filter",
	"filterAjaxPath" => "/mobile/?mobile_action=mobile_grid_filter",
	"contextMenuTitle" => $arResult["CURRENT_FILTER"] !== "all" ? $arResult['FILTER_PRESETS'][$arResult["CURRENT_FILTER"]]["name"] : GetMessageJS("M_CRM_LEAD_LIST_TITLE")
);
?>
<script>
	BX.ready(function(){
		BX.Mobile.Crm.List.init(<?=CUtil::PhpToJSObject($arJsParams)?>);

		var customItems = [
			<?if ($arResult["IS_CREATE_PERMITTED"]):?>
			{
				name: '<?=GetMessageJS("M_CRM_LEAD_ADD")?>',
				image: "/bitrix/js/mobile/images/plus.png",
				action: function()
				{
					BXMobileApp.PageManager.loadPageModal({
						url: "<?=CUtil::JSEscape($arParams["LEAD_CREATE_URL_TEMPLATE"])?>"
					});
				}
			},
			<?endif?>
			<?foreach($arResult['FILTER_PRESETS'] as $code => $preset):
				$imagePath = "/bitrix/js/mobile/images/filter.png";
				if ($code == $arResult["CURRENT_FILTER"])
					$imagePath = "/bitrix/js/mobile/images/select.png";
				elseif ($code == "all")
					$imagePath = "/bitrix/js/mobile/images/all.png";
			?>
			{
				name: '<?=$preset["name"]?>',
				image: "<?=$imagePath?>",
				action: function()
				{
					BX.Mobile.Crm.List.applyListFilter('<?=($code == "all" ? "" : CUtil::JSEscape($code))?>', '<?=CUtil::JSEscape($arParams["GRID_ID"])?>');
				}
			},
			<?endforeach?>
		];
		BX.Mobile.Crm.List.showContextMenu(customItems);

		BXMobileApp.addCustomEvent("onCrmLeadListUpdate", function(){
			BXMobileApp.UI.Page.reload();
		});

		BXMobileApp.addCustomEvent("onCrmLeadLoadPageBlank", BX.proxy(function(data){
			if (data.path)
				BX.Mobile.Crm.loadPageBlank(data.path);

			BXMobileApp.onCustomEvent("onCrmLeadListUpdate", {}, true, true);
		}, this));

		BX.message({
			"M_CRM_LEAD_CONV_OPEN_ENTITY_SEL_CONTACT" : '<?=GetMessageJS("M_CRM_LEAD_CONV_OPEN_ENTITY_SEL_CONTACT")?>',
			"M_CRM_LEAD_CONV_OPEN_ENTITY_SEL_COMPANY" : '<?=GetMessageJS("M_CRM_LEAD_CONV_OPEN_ENTITY_SEL_COMPANY")?>'
		});

		BX.Mobile.Crm.Lead.ListConverter.init(<?=CUtil::PhpToJSObject(array(
			"convertMessages" => \Bitrix\Crm\Conversion\LeadConversionScheme::getJavaScriptDescriptions(false),
			"ajaxConvertPath" => '/mobile/?mobile_action=mobile_crm_lead_actions',
			"contactSelectUrl" => $arParams["CONTACT_SELECTOR_URL_TEMPLATE_FOR_CONVERT"],
			"companySelectUrl" => $arParams["COMPANY_SELECTOR_URL_TEMPLATE_FOR_CONVERT"]
		))?>);
	});
</script>

