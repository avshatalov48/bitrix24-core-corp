<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
?>
<div style="padding-bottom: 46px">
	<div class="mobile-crm-convert" id="bx-crm-convert-sync-block">
		<div class="mobile-crm-convert-desc" data-role="crm-convert-sync-legend"></div>
		<div class="mobile-crm-convert-field-list-title"><?=GetMessage("M_CRM_CONVERT_SYNC_FIELDS")?></div>
		<div class="mobile-crm-convert-field-list" id="bx-convert-crm-fields"></div>
		<div class="mobile-crm-convert-field-list-title"><?=GetMessage("M_CRM_CONVERT_SYNC_ENTITIES")?></div>
		<div class="mobile-crm-convert-field-list" data-role="crm-convert-sync-entities"></div>
	</div>
</div>

<a href="javascript:void(0)" id="bx-convert-crm-button" class="mobile-grid-convert-button"><?=GetMessage("M_CRM_CONVERT_SYNC_CONTINUE")?></a>

<script>
	app.pullDown({
		enable:   true,
		pulltext: '<?=GetMessageJS('M_CRM_CONVERT_SYNC_PULL_TEXT');?>',
		downtext: '<?=GetMessageJS('M_CRM_CONVERT_SYNC_DOWN_TEXT');?>',
		loadtext: '<?=GetMessageJS('M_CRM_CONVERT_SYNC_LOAD_TEXT');?>',
		callback: function()
		{
			app.reload();
		}
	});

	BXMobileApp.UI.Page.TopBar.title.setText('<?=GetMessageJS("M_CRM_CONVERT_SYNC_TITLE")?>');
	BXMobileApp.UI.Page.TopBar.title.show();

	BX.message({
		"M_CRM_CONVERT_SYNC_LEGEND_LEAD": "<?=GetMessageJS("M_CRM_CONVERT_SYNC_LEGEND_LEAD")?>",
		"M_CRM_CONVERT_SYNC_LEGEND_DEAL": "<?=GetMessageJS("M_CRM_CONVERT_SYNC_LEGEND_DEAL")?>",
		"M_CRM_CONVERT_SYNC_LEGEND_QUOTE": "<?=GetMessageJS("M_CRM_CONVERT_SYNC_LEGEND_QUOTE")?>",
		"M_CRM_CONVERT_SYNC_MORE": "<?=GetMessageJS("M_CRM_CONVERT_SYNC_MORE")?>"
	});

	BXMobileApp.UI.Page.params.get({callback: function(data)
	{
		BX.Mobile.Crm.ConvertSync.init(data, <?=CUtil::PhpToJSObject($arResult["ENTITIES"])?>);
	}});
</script>
