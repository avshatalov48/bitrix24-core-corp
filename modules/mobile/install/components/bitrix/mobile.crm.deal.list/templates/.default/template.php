<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
CJSCore::Init(array('mobile_crm'));

$APPLICATION->IncludeComponent("bitrix:mobile.interface.grid", "", array(
	"GRID_ID"=> $arParams["GRID_ID"],
	"FIELDS" => $arResult["FIELDS"],
	"ITEMS" => $arResult["ITEMS"],
	"SORT_EVENT_NAME" => "onDealListSort",
	"FIELDS_EVENT_NAME" => "onDealListFields",
	"FILTER_EVENT_NAME" => "onDealListFilter",
	"NAV_PARAMS" => $arResult["NAV_PARAM"],
	"AJAX_PAGE_PATH" => "/mobile/crm/deal/?page=list",
	"SHOW_SEARCH" => "Y"
));

$arJsParams = array(
	"ajaxPath" => $arResult["AJAX_PATH"],
	"sortPath" => "/mobile/crm/deal/?page=sort",
	"fieldsPath" => "/mobile/crm/deal/?page=fields",
	"filterPath" => "/mobile/crm/deal/?page=filter",
	"filterAjaxPath" => "/mobile/?mobile_action=mobile_grid_filter",
	"contextMenuTitle" => $arResult["CURRENT_FILTER"] !== "all" && $arResult['FILTER_PRESETS'][$arResult["CURRENT_FILTER"]]["name"] ? $arResult['FILTER_PRESETS'][$arResult["CURRENT_FILTER"]]["name"] : GetMessageJS("M_CRM_DEAL_LIST_TITLE")
);
?>
<script>
	BX.ready(function(){
		BX.Mobile.Crm.List.init(<?=CUtil::PhpToJSObject($arJsParams)?>);

		var customItems = [
			<?if ($arResult["IS_CREATE_PERMITTED"]):?>
			{
				name: '<?=GetMessageJS("M_CRM_DEAL_ADD")?>',
				image: "/bitrix/js/mobile/images/plus.png",
				action: function()
				{
					BXMobileApp.PageManager.loadPageModal({
						url: "<?=CUtil::JSEscape($arParams["DEAL_CREATE_URL_TEMPLATE"])?>"
					});
				}
			},
			<?endif?>
			<?if (is_array($arResult['FILTER_PRESETS']) && !empty($arResult['FILTER_PRESETS'])):?>
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
			<?endif?>
		];
		BX.Mobile.Crm.List.showContextMenu(customItems);

		BXMobileApp.addCustomEvent("onCrmDealListUpdate", function(){
			BXMobileApp.UI.Page.reload();
		});

		BXMobileApp.addCustomEvent("onCrmDealLoadPageBlank", BX.proxy(function(data){
			if (data.path)
				BX.Mobile.Crm.loadPageBlank(data.path);

			BXMobileApp.onCustomEvent("onCrmDealListUpdate", {}, true, true);
		}, this));

		BX.Mobile.Crm.Deal.ListConverter.init(<?=CUtil::PhpToJSObject(array(
			"convertMessages" => \Bitrix\Crm\Conversion\DealConversionScheme::getJavaScriptDescriptions(false),
			"ajaxConvertPath" => '/mobile/?mobile_action=mobile_crm_deal_actions'
		))?>);
	});
</script>