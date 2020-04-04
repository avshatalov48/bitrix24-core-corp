<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
$APPLICATION->AddHeadScript("/bitrix/js/main/admin_tools.js");
?>
<div class="content-edit-form-notice-successfully" id="backupSuccessBlock" <?if (!isset($_GET['success'])):?>style="display:none"<?endif?>>
	<span class="content-edit-form-notice-text"><span class="content-edit-form-notice-icon"></span><?=GetMessage('BACKUP_SUCCESS')?></span>
</div>
<?
if (isset($_REQUEST["action_button_backup_grid"]) && $_REQUEST["action_button_backup_grid"] == "delete")
{
	$APPLICATION->RestartBuffer();
}
$columns = array(
	"NAME" => array(
		"id" => "NAME",
		"name" => GetMessage("BACKUP_HEADER_NAME"),
		"default" => true
	),
	"SIZE" => array(
		"id" => "SIZE",
		"name" => GetMessage("BACKUP_HEADER_SIZE"),
		"default" => true
	),
	"DATE" => array(
		"id" => "DATE",
		"name" => GetMessage("BACKUP_HEADER_DATE"),
		"sort" => "DATE",
		"default" => true
	),
	"PLACE" => array(
		"id" => "PLACE",
		"name" => GetMessage("BACKUP_HEADER_PLACE"),
		"default" => true
	),
);

$snippet = new \Bitrix\Main\Grid\Panel\Snippet();
$controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getRemoveButton();

$APPLICATION->IncludeComponent(
	"bitrix:main.ui.grid",
	"",
	array(
		"GRID_ID" => "backup_grid",
		"COLUMNS" => $columns,
		"ROWS" => $arResult["BACKUP_FILES"],
		'AJAX_MODE'           => 'Y',
		"AJAX_OPTION_JUMP"    => "N",
		"AJAX_OPTION_STYLE"   => "N",
		"AJAX_OPTION_HISTORY" => "N",
		"NAV_OBJECT" => $arResult["NAV"],
		"TOTAL_ROWS_COUNT"  => $arResult['TOTAL_RECORD_COUNT'],
		"SHOW_CHECK_ALL_CHECKBOXES" => true,
		"SHOW_ROW_CHECKBOXES"       => true,
		"SHOW_ROW_ACTIONS_MENU"     => true,
		"SHOW_GRID_SETTINGS_MENU"   => true,
		"SHOW_NAVIGATION_PANEL"     => true,
		"SHOW_PAGINATION"           => true,
		"SHOW_SELECTED_COUNTER"     => true,
		"SHOW_TOTAL_COUNTER"        => true,
		"SHOW_PAGESIZE"             => true,
		"SHOW_ACTION_PANEL"         => true,
		//"ENABLE_COLLAPSIBLE_ROWS" => true,
		"ALLOW_COLUMNS_RESIZE"    => true,
		"ALLOW_HORIZONTAL_SCROLL" => true,
		"ALLOW_SORT"              => true,
		"ACTION_PANEL"            => $controlPanel
	)
);
if (isset($_REQUEST["action_button_backup_grid"]) && $_REQUEST["action_button_backup_grid"] == "delete")
{
	die();
}
?>

<div class="content-edit-form-notice-error" id="backupErrorBlock" style="display:none">
	<span class="content-edit-form-notice-text"><span class="content-edit-form-notice-icon"></span><span id="backupErrorText"><?=GetMessage('BACKUP_ERROR')?></span></span>
</div>

<div id="backupProgressBlock" style="display: none; margin-top: 15px;">
	<div class="config_notify_message">
		<?=GetMessage("BACKUP_PROGRESS_HINT")?>
	</div>
	<div  class="progressbar-container">
		<div class="progressbar-track">
			<div id="backupProgressBar" class="progressbar-loader" style="width: 1%"></div>
		</div>
		<div class="progressbar-counter"><?=GetMessage("BACKUP_PROGRESS_TEXT")?> (<span id="backupPercent">1</span>%)</div>
	</div>
</div>

<div style="text-align: center; margin-top: 10px; margin-bottom: 25px;">
	<span id="backupButton" class="webform-button webform-button-create" onclick="BX.Intranet.Backup.makeBackup();"><?=GetMessage("BACKUP_BUTTON")?></span>
	<span id="cancelButton" class="webform-button" style="display: none;" onclick="BX.Intranet.Backup.stopBackup(this);"><?=GetMessage("BACKUP_STOP_BUTTON")?></span>
</div>

<?
$successUrl = $APPLICATION->GetCurPageParam("success=Y");
$currentUrl = $APPLICATION->GetCurPage();

$jsParams = array(
	"ajaxPath" => POST_FORM_ACTION_URI,
	"currentUrl" => $currentUrl,
	"successUrl" => $successUrl
);
?>
<script>
	BX.message({
		"BACKUP_RESTORE_CONFIRM" : "<?=GetMessageJS("BACKUP_RESTORE_CONFIRM")?>",
		"BACKUP_DELETE_CONFIRM" : "<?=GetMessageJS("BACKUP_DELETE_CONFIRM")?>",
		"BACKUP_SYSTEM_ERROR" : "<?=GetMessageJS("BACKUP_SYSTEM_ERROR")?>",
		"BACKUP_ERROR" : "<?=GetMessageJS("BACKUP_ERROR")?>",
		"BACKUP_RENAME_TITLE" : "<?=GetMessageJS("BACKUP_RENAME_TITLE")?>",
		"BACKUP_SAVE_BUTTON" : "<?=GetMessageJS("BACKUP_SAVE_BUTTON")?>",
		"BACKUP_CANCEL_BUTTON" : "<?=GetMessageJS("BACKUP_CANCEL_BUTTON")?>"
	});

	BX.Intranet.Backup.init(<?=CUtil::PhpToJSObject($jsParams)?>);
</script>
