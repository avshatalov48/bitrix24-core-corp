<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;

$APPLICATION->IncludeComponent("bitrix:mobile.interface.grid", "", array(
	"GRID_ID"=> $arParams["GRID_ID"],
	"FIELDS" => $arResult["FIELDS"],
	"ITEMS" => $arResult["ITEMS"],
	"NAV_PARAMS" => $arResult["NAV_PARAM"],
	"AJAX_PAGE_PATH" => $APPLICATION->GetCurPageParam(),
	"SHOW_SEARCH" => "Y"
));

?>
<script type="text/javascript">
	app.pullDown({
		enable:   true,
		pulltext: '<?=GetMessageJS('M_CRM_CLIENT_LIST_PULL_TEXT');?>',
		downtext: '<?=GetMessageJS('M_CRM_CLIENT_LIST_DOWN_TEXT');?>',
		loadtext: '<?=GetMessageJS('M_CRM_CLIENT_LIST_LOAD_TEXT');?>',
		callback: function()
		{
			app.reload();
		}
	});
	BX.ready(function(){
		BX.Mobile.Crm.EntityList.setParams({ closePage : <?=($aParams["CLOSE_PAGE"] == "N" ? "false" : "true")?>});
	});
</script>
