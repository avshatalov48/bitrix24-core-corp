<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
CJSCore::Init(array('mobile_crm'));

$APPLICATION->IncludeComponent("bitrix:mobile.interface.grid", "", array(
	"GRID_ID"=> $arParams["GRID_ID"],
	"FIELDS" => $arResult["FIELDS"],
	"ITEMS" => $arResult["ITEMS"],
	"SORT_EVENT_NAME" => "onContactListSort",
	"FIELDS_EVENT_NAME" => "onContactListFields",
	"FILTER_EVENT_NAME" => "onContactListFilter",
	"NAV_PARAMS" => $arResult["NAV_PARAM"],
	"AJAX_PAGE_PATH" => "/mobile/crm/contact/?page=list",
	"SHOW_SEARCH" => "Y"
));

$arJsParams = array(
	"ajaxPath" => $arResult["AJAX_PATH"],
	"sortPath" => "/mobile/crm/contact/?page=sort",
	"fieldsPath" => "/mobile/crm/contact/?page=fields",
	"filterPath" => "/mobile/crm/contact/?page=filter",
	"filterAjaxPath" => "/mobile/?mobile_action=mobile_grid_filter",
	"contextMenuTitle" => $arResult["CURRENT_FILTER"] !== "all" ? $arResult['FILTER_PRESETS'][$arResult["CURRENT_FILTER"]]["name"] : GetMessageJS("M_CRM_CONTACT_LIST_TITLE")
);

$isBizCardScanSupported = (IsModuleInstalled("bizcard") && Bitrix\MobileApp\Mobile::$apiVersion>=18);
$hostName = BX24_HOST_NAME;
?>
<script>

	BX.ready(function(){
		BX.Mobile.Crm.List.init(<?=CUtil::PhpToJSObject($arJsParams)?>);
		var isBizCardScanSupported = <?echo ($isBizCardScanSupported? "true":"false");?>;
		var bizcardLoader = window.loader = BX.MobileUI.createLoader();

		var bizcardScanner = window.scanner = BX.MobileTools.createCardScanner({
			url:location.protocol+"//"+location.host+"/mobile/?mobile_action=bizcard",
			stripEmptyFields:true,
			onImageGet:function(){
				bizcardScanner.inProccess = true;
				bizcardLoader.show("<?=GetMessageJS("M_CRM_CONTACT_BIZCARD_IMAGE_UPLOAD")?>")
			},
			onImageUploaded:function(){
				bizcardLoader.show("<?=GetMessageJS("M_CRM_CONTACT_BIZCARD_RECOGNIZING")?>");
			},
			onResult: function(result)
			{
				bizcardScanner.inProccess = false;
				bizcardLoader.hide();

				if(result.CARD_ID)
				{
					BXMobileApp.PageManager.loadPageModal({
						url: "<?=CUtil::JSEscape($arParams["CONTACT_CREATE_URL_TEMPLATE"])?>&bizcard_id="+result.CARD_ID,
						data: result.DATA
					});
				}
			},
			onError: function(error)
			{
				bizcardScanner.inProccess = false;
				bizcardLoader.hide();
				if(error["code"] && error["code"] == 403)
				{
					app.confirm({
						title:"",
						text:'<?=GetMessageJS("M_CRM_CONTACT_BIZCARD_LIMIT_REACHED")?>',
						buttons: ["<?=GetMessageJS("M_CRM_CONTACT_BIZCARD_GO_TO_TARIFF_TABLE")?>", "<?=GetMessageJS("M_CRM_CONTACT_BIZCARD_CLOSE")?>"],
						callback: function(buttonIndex)
						{
							var action = (buttonIndex == 1) ? "tariff" : "close"
							BX.ajax.post("https://www.bitrix24.com/cnt/tf.php", {
								popupId: "bizcard",
								action: action,
								host: "<?=$hostName?>"
							});

							if(buttonIndex == 1)
							{
								BXMPager.loadPageBlank({
									url: "https://www.bitrix24.ru/prices/"
								});
							}
						}
					});
				}
				else
				{
					app.alert('<?=GetMessageJS("M_CRM_CONTACT_BIZCARD_UNKNOWN_ERROR")?>');
				}

			}
		});

		bizcardScanner.inProccess = false;

		var slidingPanelParams = {
			hidden_sliding_panel: true,
			buttons:
			{
				addContact: {
					name:'<?=GetMessageJS("M_CRM_CONTACT_ADD2")?>',
					iconHeight:20,
					image: "/bitrix/components/bitrix/mobile.crm.contact.list/images/icon-contact-plus.png",
					callback: BX.proxy(function () {
						BXMobileApp.PageManager.loadPageModal({
							url: "<?=CUtil::JSEscape($arParams["CONTACT_CREATE_URL_TEMPLATE"])?>"
						});
					},this)
				},
				scanCard: {
					name:'<?=GetMessageJS("M_CRM_CONTACT_BIZCARD")?>',
					iconHeight:20,
					image: "/bitrix/components/bitrix/mobile.crm.contact.list/images/icon_bizcard_scan.png",
					callback: BX.proxy(function () {
						if(!bizcardScanner.inProccess)
							bizcardScanner.open();
					},this)
				}

			}
		};

		bizcardLoader.onCancel = BX.proxy(function(){
			bizcardScanner.inProccess = false;
			return true;
		},this);

		if(isBizCardScanSupported)
		{

			BXMobileApp.UI.Page.SlidingPanel.show(slidingPanelParams);
		}
		else
		{
			BXMobileApp.UI.Page.SlidingPanel.hide();
		}
		var customItems = [
			<?if ($arResult["IS_CREATE_PERMITTED"]):?>
				{
					name: '<?=GetMessageJS("M_CRM_CONTACT_ADD")?>',
					image: "/bitrix/js/mobile/images/plus.png",
					action: function()
					{
						if(isBizCardScanSupported)
						{
							var actionSheet = new BXMobileApp.UI.ActionSheet(
								{
									buttons:[
										{
											title:'<?=GetMessageJS("M_CRM_CONTACT_ADD_MANUAL")?>',
											callback:function(){
												BXMobileApp.PageManager.loadPageModal({
													url: "<?=CUtil::JSEscape($arParams["CONTACT_CREATE_URL_TEMPLATE"])?>"
												});
											}
										},
										{
											title:'<?=GetMessageJS("M_CRM_CONTACT_FROM_BIZCARD")?>',
											callback:function(){
												if(!bizcardScanner.inProccess)
													bizcardScanner.open();
											}
										}
									]
								}
							);

							actionSheet.show();
						}
						else
						{
							BXMobileApp.PageManager.loadPageModal({
								url: "<?=CUtil::JSEscape($arParams["CONTACT_CREATE_URL_TEMPLATE"])?>"
							});
						}
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

		BXMobileApp.addCustomEvent("onCrmContactListUpdate", function(){
			BXMobileApp.UI.Page.reload();
		});

		BXMobileApp.addCustomEvent("onCrmContactLoadPageBlank", BX.proxy(function(data){
			if (data.path)
				BX.Mobile.Crm.loadPageBlank(data.path);

			BXMobileApp.onCustomEvent("onCrmContactListUpdate", {}, true, true);
		}, this));
	});
</script>