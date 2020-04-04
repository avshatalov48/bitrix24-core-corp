<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
CJSCore::Init(array('mobile_crm'));

$APPLICATION->IncludeComponent("bitrix:mobile.interface.grid", "", array(
	"GRID_ID"=> $arParams["GRID_ID"],
	"FIELDS" => $arResult["FIELDS"],
	"ITEMS" => $arResult["ITEMS"],
	"SORT_EVENT_NAME" => "onCompanyListSort",
	"FIELDS_EVENT_NAME" => "onCompanyListFields",
	"FILTER_EVENT_NAME" => "onCompanyListFilter",
	"NAV_PARAMS" => $arResult["NAV_PARAM"],
	"AJAX_PAGE_PATH" => "/mobile/crm/company/?page=list",
	"SHOW_SEARCH" => "Y"
));

$arJsParams = array(
	"ajaxPath" => $arResult["AJAX_PATH"],
	"sortPath" => "/mobile/crm/company/?page=sort",
	"fieldsPath" => "/mobile/crm/company/?page=fields",
	"filterPath" => "/mobile/crm/company/?page=filter",
	"filterAjaxPath" => "/mobile/?mobile_action=mobile_grid_filter",
	"contextMenuTitle" => $arResult["CURRENT_FILTER"] !== "all" ? $arResult['FILTER_PRESETS'][$arResult["CURRENT_FILTER"]]["name"] : GetMessageJS("M_CRM_COMPANY_LIST_TITLE")
);
?>
<script>
	BX.ready(function(){
		BX.Mobile.Crm.List.init(<?=CUtil::PhpToJSObject($arJsParams)?>);

		var customItems = [
			<?if ($arResult["IS_CREATE_PERMITTED"]):?>
			{
				name: '<?=GetMessageJS("M_CRM_COMPANY_ADD")?>',
				image: "/bitrix/js/mobile/images/plus.png",
				action: function()
				{
					BXMobileApp.PageManager.loadPageModal({
						url: "<?=CUtil::JSEscape($arParams["COMPANY_CREATE_URL_TEMPLATE"])?>"
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

		BXMobileApp.addCustomEvent("onCrmCompanyListUpdate", function()
		{
			BXMobileApp.UI.Page.reload();
		});

		BXMobileApp.addCustomEvent("onCrmCompanyLoadPageBlank", BX.proxy(function(data){
			if (data.path)
				BX.Mobile.Crm.loadPageBlank(data.path);

			BXMobileApp.onCustomEvent("onCrmCompanyListUpdate", {}, true, true);
		}, this));
	});
</script>