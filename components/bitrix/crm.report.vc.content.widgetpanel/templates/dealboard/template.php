<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');

CJSCore::RegisterExt('crm_common', [
	'js' => '/bitrix/js/crm/common.js',
	'css' => '/bitrix/js/crm/css/crm.css',
]);

CJSCore::Init(['crm_common']);

?>
<div id="report-widget-panel-container">
	<?
	$APPLICATION->IncludeComponent(
		'bitrix:crm.widget_panel',
		'',
		$arResult['WIDGET_PANEL_PARAMS']
	);
	?>
</div>

<script type="text/javascript">
	BX.message({
		'CRM_REPORT_DEAL_ALL_DEALS': '<?= GetMessageJS("CRM_REPORT_DEAL_ALL_DEALS")?>'
	});
	BX.ready(
		function()
		{
			BX.CrmDealCategory.infos = <?=CUtil::PhpToJSObject(Bitrix\Crm\Category\DealCategory::getJavaScriptInfos())?>;
			BX.CrmDealWidgetFactory.messages =
				{
					notSelected: "<?=GetMessageJS('CRM_REPORT_DEAL_NO_SELECTED')?>",
					current: "<?=GetMessageJS('CRM_REPORT_DEAL_CURRENT')?>",
					categoryConfigParamCaption: "<?=GetMessageJS('CRM_REPORT_DEAL_CATEGORY')?>"
				};
			BX.CrmWidgetManager.getCurrent().registerFactory(
				BX.CrmEntityType.names.deal,
				BX.CrmDealWidgetFactory.create(BX.CrmEntityType.names.deal, {})
			);
		}
	);
</script>