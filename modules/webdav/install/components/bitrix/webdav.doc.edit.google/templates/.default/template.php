<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>

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
	});
</script>
