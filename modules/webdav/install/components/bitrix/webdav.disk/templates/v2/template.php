<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?php
$showDiskQuota = $arResult['showDiskQuota'];
$diskSpace = $arResult['diskSpace'];
$quota = $arResult['quota'];
$isInstalledPull = $arResult['isInstalledPull'];
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
		'disk_notify_dir_numeral_5_20': "<?= GetMessageJS('WD_DISK_NOTIFY_DIR_NUMERAL_5_20') ?>"
	});

	BX.ready(function(){
		if(!<?= (int)$isInstalledPull ?>)
		{
			NotInstalledPushAndPull();
		}
		urlToDiskAjax = "<?= CUtil::JSEscape($arResult['ajaxIndex']) ?>";
		alreadyDiskInstall = <?= (int)$arResult['isInstalledDisk'] ?>? true : false;
	});
</script>

<div class="disk-wrap">
	<div class="disk-container">
		<div class="disk-header <?= LANGUAGE_ID; ?>"></div>
		<div class="disk-content">
			<div id="disk_main_cont" class="disk-changefolder">
				<div id="disk_already_connect" style="display: none;">
					<input type="submit" id="disk_disconnectbutton" onclick="OpenFolder(); return false;" class="disk-connectbutton" value="<?= GetMessage('WD_DISK_OPEN_FOLDER'); ?>">
					<br><a onclick="DetachDisk(); return false;" href="" ><?= GetMessage('WD_DISK_DETACH'); ?></a>
					<?php if($showDiskQuota): ?>
						<div class="disk-chart">
							<span class="disk-chart-bar-green" style="width:<?=round(($quota/$diskSpace)*100)?>%"></span>
						</div>
						<div class="disk-title-license"><strong><?=CFile::FormatSize($diskSpace)?></strong></div>
					<?php endif; ?>
				</div>
				<div id="disk_connect_cont" style="display: none;">
					<input type="submit" id="disk_connectbutton" onclick="AttachDisk(); return false;" class="disk-connectbutton" value="<?= GetMessage('WD_DISK_ATTACH'); ?>">
				</div>
			</div>
			<div id="disk_error_container" class="disk-cnt" style="padding-bottom: 5px; display: none;">
				<div class="disk-alert">
					<div class="disk-alert-head">
						<span>
							<?= GetMessage('WD_DISK_ERROR') ?>
						</span>
					</div>
					<div class="disk-alert-body">
						<span id="disk_error_text" class="disk-alert-body"></span>
					</div>
					<div class="disk-alert-footer">
<!--						<a href="" class="disk-retry"></a>-->
					</div>
				</div>
			</div>
			<div id="disk_add_info" class="disk-cnt">
				<ul>
					<li>
						<h3><?= GetMessage('WD_DISK_MSG_USABLE'); ?></h3>
						<img src="/bitrix/components/bitrix/webdav.disk/templates/.default/images/1.png" alt="">
						<p><?= GetMessage('WD_DISK_MSG_USABLE_DESCR'); ?></p>
					</li>
					<li>
						<h3><?= GetMessage('WD_DISK_MSG_QUICK'); ?></h3>
						<img src="/bitrix/components/bitrix/webdav.disk/templates/.default/images/2.png" alt="">
						<p><?= GetMessage('WD_DISK_MSG_QUICK_DESCR'); ?></p>
					</li>
					<li>
						<h3><?= GetMessage('WD_DISK_MSG_OFFLINE_ACCESS'); ?></h3>
						<img src="/bitrix/components/bitrix/webdav.disk/templates/.default/images/3.png" alt="">
						<p><?= GetMessage('WD_DISK_MSG_OFFLINE_ACCESS_DESCR'); ?></p>
					</li>
					<li>
						<h3><?= GetMessage('WD_DISK_MSG_SECURE'); ?></h3>
						<img src="/bitrix/components/bitrix/webdav.disk/templates/.default/images/4.png" alt="">
						<p><?= GetMessage('WD_DISK_MSG_SECURE_DESCR_2'); ?></p>
					</li>
				</ul>

			</div>
			<div id="disk_change_path" style="display: none;" class="disk-footer-text"><?= GetMessage('WD_DISK_MSG_SAVE_IN_FOLDER'); ?> "<span id="attach_disk_path"></span>". <a onclick="SelectDisk(); return false;" href=""><?= GetMessage('WD_DISK_CHANGE_DIR'); ?></a></div>
		</div>

	</div>
</div>