<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
$APPLICATION->AddHeadScript('/bitrix/js/crm/common.js');

if($arResult['ENABLE_CONTROL_PANEL'])
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.control_panel',
		'',
		array(
			'ID' => 'SEND_AND_SAVE',
			'ACTIVE_ITEM_ID' => '',
			'PATH_TO_COMPANY_LIST' => isset($arParams['PATH_TO_COMPANY_LIST']) ? $arParams['PATH_TO_COMPANY_LIST'] : '',
			'PATH_TO_COMPANY_EDIT' => isset($arParams['PATH_TO_COMPANY_EDIT']) ? $arParams['PATH_TO_COMPANY_EDIT'] : '',
			'PATH_TO_CONTACT_LIST' => isset($arParams['PATH_TO_CONTACT_LIST']) ? $arParams['PATH_TO_CONTACT_LIST'] : '',
			'PATH_TO_CONTACT_EDIT' => isset($arParams['PATH_TO_CONTACT_EDIT']) ? $arParams['PATH_TO_CONTACT_EDIT'] : '',
			'PATH_TO_DEAL_LIST' => isset($arParams['PATH_TO_DEAL_LIST']) ? $arParams['PATH_TO_DEAL_LIST'] : '',
			'PATH_TO_DEAL_EDIT' => isset($arParams['PATH_TO_DEAL_EDIT']) ? $arParams['PATH_TO_DEAL_EDIT'] : '',
			'PATH_TO_LEAD_LIST' => isset($arParams['PATH_TO_LEAD_LIST']) ? $arParams['PATH_TO_LEAD_LIST'] : '',
			'PATH_TO_LEAD_EDIT' => isset($arParams['PATH_TO_LEAD_EDIT']) ? $arParams['PATH_TO_LEAD_EDIT'] : '',
			'PATH_TO_QUOTE_LIST' => isset($arResult['PATH_TO_QUOTE_LIST']) ? $arResult['PATH_TO_QUOTE_LIST'] : '',
			'PATH_TO_QUOTE_EDIT' => isset($arResult['PATH_TO_QUOTE_EDIT']) ? $arResult['PATH_TO_QUOTE_EDIT'] : '',
			'PATH_TO_INVOICE_LIST' => isset($arResult['PATH_TO_INVOICE_LIST']) ? $arResult['PATH_TO_INVOICE_LIST'] : '',
			'PATH_TO_INVOICE_EDIT' => isset($arResult['PATH_TO_INVOICE_EDIT']) ? $arResult['PATH_TO_INVOICE_EDIT'] : '',
			'PATH_TO_REPORT_LIST' => isset($arParams['PATH_TO_REPORT_LIST']) ? $arParams['PATH_TO_REPORT_LIST'] : '',
			'PATH_TO_DEAL_FUNNEL' => isset($arParams['PATH_TO_DEAL_FUNNEL']) ? $arParams['PATH_TO_DEAL_FUNNEL'] : '',
			'PATH_TO_EVENT_LIST' => isset($arParams['PATH_TO_EVENT_LIST']) ? $arParams['PATH_TO_EVENT_LIST'] : '',
			'PATH_TO_PRODUCT_LIST' => isset($arParams['PATH_TO_PRODUCT_LIST']) ? $arParams['PATH_TO_PRODUCT_LIST'] : ''
		),
		$component
	);
}

$listID = $arResult['LIST_ID'];
$prefix = strtolower($listID);
$messageContainerID = "{$prefix}_messages";
$limiSummaryContainerID = "{$prefix}_limits";
?>
<div class="sidebar-block-text">
	<div class="reports-description-text">
		<p><?=htmlspecialcharsbx(GetMessage('CRM_WGT_SLST_GENERAL_INTRO'))?></p>
		<p><?=htmlspecialcharsbx(GetMessage('CRM_WGT_SLST_LIMITS_INTRO', array('#OVERALL#' => $arResult['LIMIT']['OVERALL'], '#ENTITY#' => $arResult['LIMIT']['ENTITY'])))?></p>
	</div>
</div>
<div id="<?=htmlspecialcharsbx($messageContainerID)?>"></div>
<div id="<?=htmlspecialcharsbx($limiSummaryContainerID)?>" class="crm-double-result-search" style="margin-top: 10px;margin-bottom: 25px;">
	<?=GetMessage('CRM_WGT_SLST_LIMITS_TOTALS', array('#TOTAL#' => $arResult['TOTAL'], '#OVERALL#' => $arResult['LIMIT']['OVERALL']))?>
</div>
<div class="bx-crm-interface-grid"><table id="<?=htmlspecialcharsbx($listID)?>" class="bx-interface-grid-double" cellspacing="0"><tbody>
<tr class="bx-grid-head">
<td class="bx-checkbox-col" style="width: 10px;"></td><?
foreach($arResult['COLUMNS'] as $columnID => &$column)
{
	$colspan = isset($column['COLSPAN']) ? $column['COLSPAN'] : 1;
	?><td class="bx-grid-sortable" data-column-id="<?=htmlspecialcharsbx($columnID)?>" <?=$colspan > 1 ? ' colspan="'.$colspan.'"' : ''?>><?
	echo htmlspecialcharsbx($column['TITLE']);
	?></td><?
}
unset($column);
?></tr><?
foreach($arResult['ITEMS'] as $item)
{
	$itemID = $item['ID'];
	$itemPrefix = "{$prefix}_{$itemID}";
	?><tr class="bx-odd bx-top bx-double-open" data-node-id="<?=htmlspecialcharsbx($itemID)?>"></tr><?
}
?></tbody></table></div>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmWidgetSlotList.messages =
			{
				limit: "<?=GetMessageJS('CRM_WGT_SLST_LIMITS_TOTALS')?>"
			};

			BX.CrmWidgetSlotNode.messages =
			{
				add: "<?=GetMessageJS('CRM_WGT_SLST_ADD')?>",
				totalSum: "<?=GetMessageJS('CRM_WGT_SLST_TOTAL_SUM')?>",
				userFields: "<?=GetMessageJS('CRM_WGT_SLST_USER_FIELDS')?>",
				limit: "<?=GetMessageJS('CRM_WGT_SLST_LIMITS_TOTALS')?>",
				errorSelectField: "<?=GetMessageJS('CRM_WGT_SLST_ERR_SELECT_FIELD')?>",
				errorFieldAlreadyExists: "<?=GetMessageJS('CRM_WGT_SLST_ERR_FIELD_ALREADY_EXISTS')?>",
				errorFieldLimitExceeded: "<?=GetMessageJS('CRM_WGT_SLST_ERR_FIELD_LIMT_EXCEEDED')?>",
				errorNoFreeSlots: "<?=GetMessageJS('CRM_WGT_SLST_ERR_NO_FREE_SLOTS')?>"
			};

			BX.CrmWidgetSlotItem.messages =
			{
				notSelected: "<?=GetMessageJS('CRM_WGT_SLST_NOT_SELECTED')?>",
				byDefault: "<?=GetMessageJS('CRM_WGT_SLST_BY_DEFALT')?>",
				edit: "<?=GetMessageJS('CRM_WGT_SLST_EDIT')?>",
				save: "<?=GetMessageJS('CRM_WGT_SLST_SAVE')?>",
				cancel: "<?=GetMessageJS('CRM_WGT_SLST_CALCEL')?>",
				remove: "<?=GetMessageJS('CRM_WGT_SLST_REMOVE')?>",
				reset: "<?=GetMessageJS('CRM_WGT_SLST_RESET')?>",
				addProductSum: "<?=GetMessageJS('CRM_WGT_SLST_ADD_PRODUCT_SUM')?>"
			};

			//List
			var list = BX.CrmWidgetSlotList.create(
				"<?=CUtil::JSEscape($listID)?>",
				{
					tableId: "<?=CUtil::JSEscape($listID)?>",
					prefix: "<?=CUtil::JSEscape($prefix)?>",
					messageContainerId: "<?=CUtil::JSEscape($messageContainerID)?>",
					limitSummaryContainerId: "<?=CUtil::JSEscape($limiSummaryContainerID)?>",
					data: <?=CUtil::PhpToJSObject($arResult['ITEMS'])?>,
					limit: <?=CUtil::PhpToJSObject($arResult['LIMIT'])?>,
					serviceUrl: "<?='/bitrix/components/bitrix/crm.widget_slot.list/ajax.php?'.bitrix_sessid_get()?>",
					nodeHelpUrl: "<?=$arResult['HELP_ARTICLE_URL']?>",
					nodeTolltip: "<?=GetMessageJS("CRM_WGT_SLST_NODE_TOOLTIP")?>",
					enableBitrix24Helper: <?=$arResult['ENABLE_B24_HELPER'] ? 'true' : 'false'?>
				}
			);
			list.layout();
			list.expandAll();

			//Rebuild process
			BX.CrmLongRunningProcessDialog.messages =
			{
				startButton: "<?=GetMessageJS('CRM_WGT_SLST_LRP_DLG_BTN_START')?>",
				stopButton: "<?=GetMessageJS('CRM_WGT_SLST_LRP_DLG_BTN_STOP')?>",
				closeButton: "<?=GetMessageJS('CRM_WGT_SLST_LRP_DLG_BTN_CLOSE')?>",
				wait: "<?=GetMessageJS('CRM_WGT_SLST_DLG_WAIT')?>",
				requestError: "<?=GetMessageJS('CRM_WGT_SLST_LRP_DLG_REQUEST_ERR')?>"
			};
		}
	);
</script>