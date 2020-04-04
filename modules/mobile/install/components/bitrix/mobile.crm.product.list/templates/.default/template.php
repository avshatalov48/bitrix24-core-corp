<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;

CJSCore::Init(array('mobile_crm'));

$APPLICATION->IncludeComponent("bitrix:mobile.interface.grid", "", array(
	"GRID_ID"=> $arParams["GRID_ID"],
	"FIELDS" => $arResult["FIELDS"],
	"SECTIONS" => $arResult['SECTIONS'],
	"ITEMS" => $arResult["ITEMS"],
	"SORT_EVENT_NAME" => "onProductListSort",
	"FIELDS_EVENT_NAME" => "onProductListFields",
	"FILTER_EVENT_NAME" => "onProductListFilter",
	"NAV_PARAMS" => $arResult["NAV_PARAM"],
	"AJAX_PAGE_PATH" => $APPLICATION->GetCurPageParam(),
	"SHOW_SEARCH" => "Y"
));

$arJsParams = array(
	"ajaxPath" => '/mobile/?mobile_action=mobile_crm_product_actions',
	"sortPath" => "/mobile/crm/product/?page=sort",
	"fieldsPath" => "/mobile/crm/product/?page=fields",
	"filterPath" => "/mobile/crm/product/?page=filter",
	"filterAjaxPath" => "/mobile/?mobile_action=mobile_grid_filter",
	"contextMenuTitle" => $arResult["CURRENT_FILTER"] !== "all" ? $arResult['FILTER_PRESETS'][$arResult["CURRENT_FILTER"]]["name"] : GetMessageJS("M_CRM_PRODUCT_LIST_TITLE")
);
?>
<script>
	BX.ready(function(){
		BX.Mobile.Crm.List.init(<?=CUtil::PhpToJSObject($arJsParams)?>);

		var customItems = [
			<?if ($arResult["IS_CREATE_PERMITTED"]):?>
			{
				name: '<?=GetMessageJS("M_CRM_PRODUCT_ADD")?>',
				image: "/bitrix/js/mobile/images/plus.png",
				action: function()
				{
					BXMobileApp.PageManager.loadPageModal({
						url: "<?=CUtil::JSEscape($arParams["PRODUCT_CREATE_URL_TEMPLATE"])?>"
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

		BXMobileApp.addCustomEvent("onCrmProductListUpdate", function(){
			BXMobileApp.UI.Page.reload();
		});

		BXMobileApp.addCustomEvent("onCrmProductLoadPageBlank", BX.proxy(function(data){
			if (data.path)
				BX.Mobile.Crm.loadPageBlank(data.path);

			BXMobileApp.onCustomEvent("onCrmProductListUpdate", {}, true, true);
		}, this));

		<?if ($arResult['LIST_MODE'] == "SELECTOR"):?>
		var onProductSelectorCloseHandler = function()
		{
			BX.removeCustomEvent("onOpenPageAfter", onProductSelectorCloseHandler);
			BXMobileApp.UI.Page.close({drop: true});
		};

		BXMobileApp.addCustomEvent("onProductSelectorClose", function () {
			if (!(window.isCurrentPage && window.isCurrentPage == "Y"))
			{
				BX.addCustomEvent("onOpenPageAfter", onProductSelectorCloseHandler);
			}
			else
			{
				window.isCurrentPage = "";
			}
		});
		<?endif?>
	});
</script>