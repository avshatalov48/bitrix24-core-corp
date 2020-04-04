<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/js/main/amcharts/3.3/amcharts.js");
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/js/main/amcharts/3.3/pie.js");
global $USER;

CJSCore::Init(array("fx", "date", 'disk_desktop', 'disk_information_popups'));

$diskSpace = isset($arResult["diskSpace"]) && strlen($arResult["diskSpace"]) > 0 ? doubleval($arResult["diskSpace"]) : 0;
$diskSpace = $diskSpace < 0 ? 0 : $diskSpace;
$freeSpace = isset($arResult["quota"]) && strlen($arResult["quota"]) > 0 ? doubleval($arResult["quota"]) : 0;
$freeSpace = $freeSpace < 0 ? 0 : $freeSpace;
$personalLibIndex = $arResult['personalLibIndex'];

$isInstalledPull = $arResult["isInstalledPull"];

$currenUserId = $USER->getId();
$isMac = false;
$request = Bitrix\Main\Context::getCurrent()->getRequest();
if (stripos($request->getUserAgent(), "Macintosh") !== false)
{
	$isMac = true;
}
$diskEnabled =
	\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) &&
	CModule::includeModule('disk');

$isFirstRunAfterConvert =
	$diskEnabled &&
	!\CUserOptions::getOption('disk', 'DesktopDiskInstall') &&
	!\CUserOptions::getOption('disk', 'DesktopDiskReInstall') &&
	\CUserOptions::getOption('webdav', 'DesktopDiskInstall')
;

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/file.php");
?>
<script type="text/javascript">
	BX.message({
		'disk_name': "<?= GetMessageJS('WD_DISK_NAME') ?>",
		'disk_default': "<?= GetMessageJS('WD_DISK_JS_ERROR_DEFAULT') ?>",
		'disk_already_attached': "<?= GetMessageJS('WD_DISK_JS_ERROR_ALREADY_ATTACHED') ?>",
		'disk_error_offline': "<?= GetMessageJS('WD_DISK_JS_ERROR_OFFLINE') ?>",
		'disk_unknown': "<?= GetMessageJS('WD_DISK_JS_ERROR_UNKNOWN') ?>",
		'disk_not_empty': "<?= GetMessageJS('WD_DISK_JS_ERROR_NOT_EMPTY') ?>",
		'disk_attach_directory_is_not_empty': "<?= GetMessageJS('WD_DISK_JS_ERROR_ATTACH_DIRECTORY_IS_NOT_EMPTY') ?>",
		'disk_attach_initialize_error': "<?= GetMessageJS('WD_DISK_JS_ERROR_ATTACH_INITIALIZE_ERROR') ?>",
		'disk_attach_another_user': "<?= GetMessageJS('WD_DISK_JS_ERROR_ATTACH_ANOTHER_USER') ?>",
		'disk_login_failed': "<?= GetMessageJS('WD_DISK_JS_ERROR_LOGIN_FAILED') ?>",
		'disk_change_dir': "<?= GetMessageJS('WD_DISK_CHANGE_DIR') ?>",
		'disk_not_installed_pull': "<?= GetMessageJS('WD_DISK_ERROR_NOT_INSTALLED_PULL') ?>",
		'disk_notify_action_add_f_d': "<?= GetMessageJS('WD_DISK_NOTIFY_ACTION_ADD_F_D') ?>",
		'disk_notify_action_add_d': "<?= GetMessageJS('WD_DISK_NOTIFY_ACTION_ADD_D') ?>",
		'disk_notify_action_add_f': "<?= GetMessageJS('WD_DISK_NOTIFY_ACTION_ADD_F') ?>",
		'disk_notify_action_update_f_d': "<?= GetMessageJS('WD_DISK_NOTIFY_ACTION_UPDATE_F_D') ?>",
		'disk_notify_action_update_d': "<?= GetMessageJS('WD_DISK_NOTIFY_ACTION_UPDATE_D') ?>",
		'disk_notify_action_update_f': "<?= GetMessageJS('WD_DISK_NOTIFY_ACTION_UPDATE_F') ?>",
		'disk_notify_action_delete_f_d': "<?= GetMessageJS('WD_DISK_NOTIFY_ACTION_DELETE_F_D') ?>",
		'disk_notify_action_delete_d': "<?= GetMessageJS('WD_DISK_NOTIFY_ACTION_DELETE_D') ?>",
		'disk_notify_action_delete_f': "<?= GetMessageJS('WD_DISK_NOTIFY_ACTION_DELETE_F') ?>",
		'disk_notify_file_numeral_1': "<?= GetMessageJS('WD_DISK_NOTIFY_FILE_NUMERAL_1') ?>",
		'disk_notify_file_numeral_21': "<?= GetMessageJS('WD_DISK_NOTIFY_FILE_NUMERAL_21') ?>",
		'disk_notify_file_numeral_2_4': "<?= GetMessageJS('WD_DISK_NOTIFY_FILE_NUMERAL_2_4') ?>",
		'disk_notify_file_numeral_5_20': "<?= GetMessageJS('WD_DISK_NOTIFY_FILE_NUMERAL_5_20') ?>",
		'disk_notify_dir_numeral_1': "<?= GetMessageJS('WD_DISK_NOTIFY_DIR_NUMERAL_1') ?>",
		'disk_notify_dir_numeral_21': "<?= GetMessageJS('WD_DISK_NOTIFY_DIR_NUMERAL_21') ?>",
		'disk_notify_dir_numeral_2_4': "<?= GetMessageJS('WD_DISK_NOTIFY_DIR_NUMERAL_2_4') ?>",
		'disk_notify_dir_numeral_5_20': "<?= GetMessageJS('WD_DISK_NOTIFY_DIR_NUMERAL_5_20') ?>",
		"FILE_SIZE_b" : "<?= GetMessageJS('FILE_SIZE_b') ?>",
		"FILE_SIZE_Kb" : "<?= GetMessageJS('FILE_SIZE_Kb') ?>",
		"FILE_SIZE_Mb" : "<?= GetMessageJS('FILE_SIZE_Mb') ?>",
		"FILE_SIZE_Gb" : "<?= GetMessageJS('FILE_SIZE_Gb') ?>",
		"FILE_SIZE_Tb" : "<?= GetMessageJS('FILE_SIZE_Tb') ?>",
		"disk_status_enabled" : "<?= GetMessageJS('WD_DISK_SPACE_STATUS_ON') ?>",
		"disk_status_disabled" : "<?= GetMessageJS('WD_DISK_SPACE_STATUS_OFF') ?>",
		"disk_video_window_title" : "<?= GetMessageJS('WD_DISK_VIDEO_WINDOW_TITLE') ?>",
		"disk_sync_no_date" : "<?= GetMessageJS('WD_DISK_SYNC_NO_DATE') ?>",
		"disk_file_created" : "<?= GetMessageJS('WD_DISK_FILE_CREATED') ?>",
		"disk_file_updated" : "<?= GetMessageJS('WD_DISK_FILE_UPDATED') ?>",
		"disk_file_renamed" : "<?= GetMessageJS('WD_DISK_FILE_RENAMED') ?>",
		"disk_file_moved" : "<?= GetMessageJS('WD_DISK_FILE_MOVED') ?>",
		"disk_settings_title" : "<?= GetMessageJS('WD_DISK_SETTINGS_TITLE') ?>",
		"disk_settings_label_enable" : "<?= GetMessageJS('WD_DISK_SETTINGS_LABEL_ENABLE') ?>",
		"disk_speed_seconds" : "<?= GetMessageJS('WD_DISK_SPEED_SECONDS') ?>",

		"disk_last_sync_paused_comment" : "<?= GetMessageJS('WD_DISK_LAST_SYNC_PAUSED_COMMENT') ?>",

		"disk_estimate_time_per_file" : "<?= GetMessageJS('WD_DISK_ESTIMATE_TIME_PER_FILE') ?>",
		"disk_estimate_time_hour" : "<?= GetMessageJS('WD_DISK_ESTIMATE_TIME_HOUR') ?>",
		"disk_estimate_time_minute" : "<?= GetMessageJS('WD_DISK_ESTIMATE_TIME_MINUTE') ?>",
		"disk_estimate_time_second" : "<?= GetMessageJS('WD_DISK_ESTIMATE_TIME_SECOND') ?>",

		"disk_history_error_base" : "<?= GetMessageJS('WD_DISK_HISTORY_ERROR_BASE') ?>",
		"disk_history_error_not_found" : "<?= GetMessageJS('WD_DISK_HISTORY_ERROR_NOT_FOUND') ?>",
		"disk_history_error_bad_name" : "<?= GetMessageJS('WD_DISK_HISTORY_ERROR_BAD_NAME') ?>",
		"disk_history_error_access_denied" : "<?= GetMessageJS('WD_DISK_HISTORY_ERROR_ACCESS_DENIED') ?>",
		"disk_history_error_blocked_by_unknown": "<?= GetMessageJS('WD_DISK_HISTORY_ERROR_BLOCKED_BY_UNKNOWN') ?>",
		"disk_history_error_blocked_by_program": "<?= GetMessageJS('WD_DISK_HISTORY_ERROR_BLOCKED_BY_PROGRAM') ?>",

		"disk_youtube_video_id" : "<?= GetMessageJS('WD_DISK_YOUTUBE_VIDEO_ID') ?>",
		"disk_file_deleted" : "<?= GetMessageJS('WD_DISK_FILE_DELETED') ?>",
		"disk_notify_single_file" : "<?= GetMessageJS('WD_DISK_NOTIFY_SINGLE_FILE') ?>",
		"disk_notify_single_file_operation_deleted" : "<?= GetMessageJS('WD_DISK_NOTIFY_SINGLE_FILE_OPERATION_DELETED') ?>",
		"disk_notify_single_file_operation_created" : "<?= GetMessageJS('WD_DISK_NOTIFY_SINGLE_FILE_OPERATION_CREATED') ?>",
		"disk_notify_single_file_operation_updated" : "<?= GetMessageJS('WD_DISK_NOTIFY_SINGLE_FILE_OPERATION_UPDATED') ?>",
		"disk_settings_label_file_click_action" : "<?= GetMessageJS('WD_DISK_SETTINGS_LABEL_FILE_CLICK_ACTION') ?>",
		"disk_settings_label_file_click_action_open_folder" : "<?= GetMessageJS('WD_DISK_SETTINGS_VALUE_FILE_CLICK_ACTION_OPEN_FOLDER') ?>",
		"disk_settings_label_file_click_action_open_file" : "<?= GetMessageJS('WD_DISK_SETTINGS_VALUE_FILE_CLICK_ACTION_OPEN_FILE') ?>",
		"disk_progress_start_extlink" : "<?= GetMessageJS('WD_DISK_PROGRESS_START_GETTING_EXT_LINK') ?>",
		"disk_progress_finish_extlink" : "<?= GetMessageJS('WD_DISK_PROGRESS_FINISH_GETTING_EXT_LINK') ?>",
		"disk_progress_start_launch_app" : "<?= GetMessageJS('WD_DISK_PROGRESS_START_LAUNCH_APP') ?>",
		"disk_progress_start_view" : "<?= GetMessageJS('WD_DISK_PROGRESS_START_VIEW') ?>",
		"disk_progress_start_edit" : "<?= GetMessageJS('WD_DISK_PROGRESS_START_EDIT') ?>",
		"disk_progress_start_create" : "<?= GetMessageJS('WD_DISK_PROGRESS_START_CREATE') ?>",
		"disk_bdisk_file_error_size_restriction" : "<?= GetMessageJS('DISK_BDISK_FILE_ERROR_SIZE_RESTRICTION') ?>",
		"disk_bdisk_storage_controller_document_was_locked" : "<?= GetMessageJS('DISK_BDISK_STORAGE_CONTROLLER_DOCUMENT_WAS_LOCKED') ?>",
		"disk_progress_start_edit_upload" : "<?= GetMessageJS('WD_DISK_PROGRESS_START_EDIT_UPLOAD') ?>",
		"disk_bdisk_file_conflict_between_versions_title" : "<?= GetMessageJS('DISK_BDISK_FILE_CONFLICT_BETWEEN_VERSIONS_TITLE') ?>",
		"disk_bdisk_file_conflict_between_versions" : "<?= GetMessageJS('DISK_BDISK_FILE_CONFLICT_BETWEEN_VERSIONS') ?>",
		"disk_bdisk_file_conflict_between_versions_helpdesk" : "<?= GetMessageJS('DISK_BDISK_FILE_CONFLICT_BETWEEN_VERSIONS_HELPDESK') ?>",
		"disk_bdisk_file_conflict_locked_by_app_title" : "<?= GetMessageJS('DISK_BDISK_FILE_CONFLICT_LOCKED_BY_APP_TITLE') ?>",
		"disk_bdisk_file_conflict_locked_by_app" : "<?= GetMessageJS('DISK_BDISK_FILE_CONFLICT_LOCKED_BY_APP') ?>",
		"disk_bdisk_file_conflict_locked_by_app_helpdesk" : "<?= GetMessageJS('DISK_BDISK_FILE_CONFLICT_LOCKED_BY_APP_HELPDESK') ?>",
		"disk_restore_deleted_object" : "<?= GetMessageJS('WD_DISK_RESTORE_DELETED_OBJECT') ?>"
	});

	BX.addCustomEvent(window, "onMessengerWindowInit", function (MW, BXIM)
	{
		if (typeof(BXFileStorage) == 'undefined')
			return false;

		urlToDiskAjax = "<?= CUtil::JSEscape($arResult['ajaxIndex']) ?>";
		BitrixDisk.init({
			revision: <?= (int)COption::GetOptionString("disk", "disk_revision_api", -1) ?>,
			needToReAttach: <?= (int)$isFirstRunAfterConvert ?>,
			enableShowingNotify: <?= (int)$isFirstRunAfterConvert ?>,
			enabled : BXFileStorage.GetStatus().status == "online",
			bxim : BXIM,
			lastSyncTimestamp : BXIM.getLocalConfig("lastSyncTimestamp", null),
			historyItems : BXFileStorage.GetLogLastOperations(),

			mySpace : 0,
			diskSpace : <?= $diskSpace ?>,
			freeSpace : <?= $freeSpace ?>,

			pathToImages : "/bitrix/components/bitrix/disk.bitrix24disk/templates/.default/images",
			pathTemplateToRestoreObject: "<?= $arResult['pathTemplateToRestoreObject'] ?>",
			storageCmdPath : "<?= CUtil::JSEscape($arResult["ajaxStorageIndex"]) ?>",

			currentUserId: <?= $currenUserId ?>
		});

		if(!<?= (int)$isInstalledPull ?>)
		{
			NotInstalledPushAndPull();
		}

		alreadyDiskInstall = <?= (int)$arResult['isInstalledDisk'] ?>? true : false;
	});
</script>
<div style="display: none">
	<div id="disk-wrap" class="wrap">
		<div class="header">
			<div class="header_section_one">
				<span class="icon"></span><h2><?=GetMessage("WD_DISK_HEADER_TITLE")?></h2>
			</div>
			<div class="header_section_two">
				<a onclick="BXFileStorage.OpenFolder();" href="javascript:void(0);" class="win"><span class="icon"></span><?=GetMessage($isMac ? "WD_DISK_OPEN_FINDER" : "WD_DISK_OPEN_EXPLORER")?></a>
				<a onclick="BitrixDisk.bxim.desktop.browse(BitrixDisk.bxim.desktop.getCurrentUrl() + '<?= $personalLibIndex; ?>');" href="javascript:void(0);" class="b24"><span class="icon"></span><?=GetMessage("WD_DISK_OPEN_BITRIX24")?></a>
			</div>
			<div style="clear: both;"></div>
		</div>
		<div class="workarea" id="disk-workarea">
			<div class="left_section">

				<div class="mrGraf">
					<div class="radial_containert" id="disk-chart" style="width: 250px; height: 250px;"></div>
					<div class="radial_containert_default" id="disk-chart-default"></div>
					<div class="legend_container">
						<strong id="disk-space-status" class="disk_space_status" style="display: none;"><?=GetMessage("WD_DISK_STATUS_USED")?> <span id="disk-used-space"></span> <?=GetMessage("WD_DISK_STATUS_OF")?> <span id="disk-full-space"></span></strong>
						<div id="disk-connect-button" style="text-align: center">
							<strong class="bad_mess"><span class="icon"></span> <?=GetMessage("WD_DISK_STATUS_DISABLED")?></strong>
							<input type="submit" id="disk_disconnectbutton" onclick="BitrixDisk.switchOn(true);" class="disk-connectbutton small" value="<?=GetMessage("WD_DISK_ATTACH")?>">
						</div>
						<div id="disk-legend" class="legend_block">
							<div class="disc_status"><?=GetMessage("WD_DISK_SPACE_STATUS")?>: <span id="disk-status"><?=GetMessage("WD_DISK_SPACE_STATUS_OFF")?></span></div>
							<div class="Graf_sigment gray"><span class="dot"></span><?=GetMessage("WD_DISK_SPACE_STATUS_FREE")?>: <span id="disk-free-space"></span></div>
							<div class="Graf_sigment green"><span class="dot"></span><?=GetMessage("WD_DISK_SPACE_STATUS_COMPANY")?>: <span id="disk-company-space"></span></div>
							<div class="Graf_sigment blue"><span class="dot"></span><?=GetMessage("WD_DISK_SPACE_STATUS_MY")?>: <span id="disk-my-space"></span></div>
						</div>
					</div>
					<div id="disk-change-target-folder" style="font-size: 13px;margin-top: 21px;padding: 14px 0 12px 39px;"><?= GetMessage('WD_DISK_CHANGE_TARGET_DIR_TEXT', array('#PATH#' => '<span id="attach_disk_path"></span>', '#LINK#' => '<a href="javascript:void(0);" onclick="BitrixDisk.changeTargetFolder(); return false;">', '#END_LINK#' => '</a>')); ?></a></div>
					<div class="whatisit two_blocl">
						<div class="right_block">
							<a title="<?= GetMessage('WD_DISK_LEARNING_VIDEO') ?>" href="https://www.youtube.com/watch?v=<?=GetMessageJS("WD_DISK_YOUTUBE_VIDEO_ID") ?>" target="_blank" class="vid_block"></a>
						</div>
						<div class="left_block">
							<h3><?=GetMessage("WD_DISK_LEARNING_TITLE")?></h3>
							<p><?=GetMessage("WD_DISK_LEARNING_TEXT_2", array('#A#' => '<a target="_blank" href="' . GetMessage("WD_DISK_LEARNING_URL_2") . '">', '#A_END#' => '</a>', ))?></p>
						</div>
					</div>

				</div>

			</div>
			<div class="right_section">
				<div class="sync_mess" id="disk-last-sync" style="display: none;"><?=GetMessage("WD_DISK_LAST_SYNC")?>: <span id="disk-last-sync-date"></span> <span id="disk-last-sync-comment"></span></div>
				<div class="sync_prigress" id="disk-loading" style="display: none;">
					<div class="sync_block_left">
						<div class="sync_block_icon"></div>
						<div class="sync_block_status"><?=GetMessage("WD_DISK_SYNC_LABEL")?> <span id="disk-number-of-files-text"></span></div>
					</div>
					<div class="sync_block_right">
						<div class="progress_bar_container">
							<div class="progress_bar_p" id="disk-progress-bar"></div>
						</div>
						<span class="progress_text"><span id="disk-current-file-nums-container"><span id="disk-current-file-num"></span> <?=GetMessage("WD_DISK_STATUS_OF")?> <span id="disk-number-of-files"></span>.</span> <span id="disk-progress-speed"></span> <span id="disk-progress-estimated-time"></span></span>
					</div>
					<div style="clear: both;"></div>
				</div>
				<div id="disk-btn-sync-container" class="sync_block_left sync_manipulate" style="display: none;">
					<div class="sync_manipulate_content">
						<div id="disk-btn-sync-stop" class="sync_block_status" style="display: inline-block"><div class="sync_stop_icon"></div><?=GetMessage("WD_DISK_STOP_SYNC")?></div>
						<div id="disk-btn-sync-start" class="sync_block_status" style="display: none"><div class="sync_start_icon"></div><?=GetMessage("WD_DISK_START_SYNC")?></div>
					</div>
				</div>
				<div class="download_history" style="display: none;" id="disk-history-container">
					<div class="download_history_title"><?=GetMessage("WD_DISK_HISTORY_TITLE")?></div>
					<ul class="download_history_file_list" id="disk-history">

					</ul>
				</div>

				<div class="empty_history" id="disk-history-empty" style="display: none;">
					<div class="empty_history_title"><?=GetMessage("WD_DISK_HISTORY_TITLE")?></div>

					<div class="empty_history_block"></div>
					<p class="p4"><?=GetMessage("WD_DISK_HISTORY_DEFAULT_TEXT")?></p>
					<div style="text-align: center;"><a class="more_link_wts" href="#" onclick="BXFileStorage.OpenFolder();"><?=GetMessage("WD_DISK_HISTORY_OPEN_DISK")?></a></div>
				</div>

				<div class="beda_message" id="disk-history-help">
					<div class="beda_message_title"><?=GetMessage("WD_DISK_HISTORY_HELP_TITLE")?></div>

					<p class="p1"><?=GetMessage("WD_DISK_HISTORY_HELP_P1")?></p>

					<p class="p2"><?=GetMessage("WD_DISK_HISTORY_HELP_P2")?></p>

					<p class="p3"><?=GetMessage("WD_DISK_HISTORY_HELP_P3")?></p>
					<div class="beda_message_title"></div>
					<p class="p4"><?=GetMessage("WD_DISK_HISTORY_HELP_P4")?></p>
				</div>
			</div>
			<div style="clear: both;"></div>
		</div>
	</div>
</div>

