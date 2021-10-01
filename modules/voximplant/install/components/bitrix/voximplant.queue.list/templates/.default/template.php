<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

CJSCore::Init(["sidepanel"]);

$isBitrix24Template = (SITE_TEMPLATE_ID == "bitrix24");
if($isBitrix24Template)
{
	$this->SetViewTarget("pagetitle", 100);
}
?>
	<div class="pagetitle-container pagetitle-align-right-container">
		<span id="add-queue" class="webform-small-button webform-small-button-blue bx24-top-toolbar-add">
			<span class="webform-small-button-left"></span>
			<span class="webform-small-button-icon"></span>
			<span class="webform-small-button-text"><?=GetMessage('VOX_QUEUE_LIST_ADD')?></span>
			<? if(!$arResult["CAN_CREATE_GROUP"]): ?>
				<span class="webform-small-button-right voximplant-queue-lock-icon"></span>
			<? endif ?>
		</span>
	</div>
<?

if($isBitrix24Template)
{
	$this->EndViewTarget();
}

$APPLICATION->IncludeComponent("bitrix:ui.info.helper", "", array());

$APPLICATION->IncludeComponent(
	"bitrix:main.ui.grid",
	"",
	array(
		"GRID_ID" => $arResult["GRID_ID"],
		"HEADERS" => $arResult["HEADERS"],
		"ROWS" => $arResult["ROWS"],
		"NAV_OBJECT" => $arResult["NAV_OBJECT"],
		"SORT" => $arResult["SORT"],
		"FOOTER" => array(
			array("title" => GetMessage("VOX_QUEUE_LIST_SELECTED"), "value" => $arResult["ROWS_COUNT"])
		),
		"AJAX_MODE" => "Y",
		"AJAX_ID" => CAjax::GetComponentID("bitrix:voximplant.queue.list", ".default", ""),
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_HISTORY" => "N",
	),
	$component, array("HIDE_ICONS" => "Y")
);
?>

<script>
	BX.message({
		'VOX_QUEUE_DELETE_ERROR': '<?=GetMessageJS('VOX_QUEUE_DELETE_ERROR')?>',
		'VOX_QUEUE_IS_USED': '<?=GetMessageJS('VOX_QUEUE_IS_USED')?>',
		'VOX_QUEUE_NUMBER': '<?=GetMessageJS('VOX_QUEUE_NUMBER')?>',
		'VOX_QUEUE_IVR': '<?=GetMessageJS('VOX_QUEUE_IVR')?>',
		'VOX_QUEUE_CLOSE': '<?=GetMessageJS('VOX_QUEUE_CLOSE')?>'
	});

	BX.ready(function()
	{
		BX.Voximplant.QueueList.setDefaults({
			canCreateGroup: <?= $arResult['CAN_CREATE_GROUP'] ? 'true' : 'false'?>,
			maximumGroups: <?= (int)$arResult['MAXIMUM_GROUPS'] ?>,
			createUrl: '<?=CUtil::JSEscape($arResult['CREATE_QUEUE_URL'])?>'
		});
		var instance = BX.Voximplant.QueueList.getInstance();
	});


</script>


