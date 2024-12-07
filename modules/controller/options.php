<?php
/** @var CMain $APPLICATION */
/** @var CDatabase $DB */
/** @var CUser $USER */

$module_id = 'controller';
if (!$USER->CanDoOperation('controller_settings_view') || !CModule::IncludeModule('controller'))
{
	return;
}

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/options.php');
IncludeModuleLangFile(__FILE__);

$arGroups = [];
$dbr_groups = CControllerGroup::GetList(['SORT' => 'ASC', 'ID' => 'ASC']);
while ($ar_groups = $dbr_groups->GetNext())
{
	$arGroups[$ar_groups['ID']] = $ar_groups['NAME'] . ' [' . $ar_groups['ID'] . ']';
}

$arOptions = [
	['default_group', GetMessage('CTRLR_OPTIONS_DEF_GROUP'), 1, ['selectbox', $arGroups]],
	['group_update_time', GetMessage('CTRLR_OPTIONS_TIME_AUTOUPDATE'), 0, ['text', 6]],
	['show_hostname', GetMessage('CTRLR_OPTIONS_SHOW_HOSTNAME'), 0, ['checkbox']],
];
if (ControllerIsSharedMode())
{
	$arOptions[] = ['shared_kernel_path', GetMessage('CTRLR_OPTIONS_SHARED_KERNEL_PATH'), '', ['text', 50]];
}
$arOptions[] = ['auth_log_days', GetMessage('CTRLR_OPTIONS_AUTH_LOG_DAYS'), 0, ['text', 6]];
$arOptions[] = ['task_retry_count', GetMessage('CTRLR_OPTIONS_TASK_RETRY_COUNT'), 0, ['text', 6]];
$arOptions[] = ['task_retry_timeout', GetMessage('CTRLR_OPTIONS_TASK_RETRY_TIMEOUT'), 0, ['text', 6]];

$aTabs = [];
$aTabs[] = [
	'DIV' => 'edit1',
	'TAB' => GetMessage('MAIN_TAB_SET'),
	'ICON' => 'main_settings',
	'TITLE' => GetMessage('MAIN_TAB_TITLE_SET'),
];
if ($USER->IsAdmin())
{
	$aTabs[] = [
		'DIV' => 'edit3',
		'TAB' => GetMessage('MAIN_TAB_RIGHTS'),
		'ICON' => 'main_settings',
		'TITLE' => GetMessage('MAIN_TAB_TITLE_RIGHTS'),
	];
}

$tabControl = new CAdminTabControl('tabControl', $aTabs);

if (
	$_SERVER['REQUEST_METHOD'] == 'POST'
	&& ($_REQUEST['Update'] <> '' || $_REQUEST['Apply'] <> '' || $_REQUEST['RestoreDefaults'] <> '')
	&& $USER->CanDoOperation('controller_settings_change')
	&& check_bitrix_sessid()
)
{
	if ($_REQUEST['RestoreDefaults'] <> '')
	{
		COption::RemoveOption('controller');
	}

	$prev_group_update_time = COption::GetOptionInt('controller', 'group_update_time');
	$prev_auth_log_days = COption::GetOptionInt('controller', 'auth_log_days');

	__AdmSettingsSaveOptions('controller', $arOptions);

	if ($prev_group_update_time != COption::GetOptionInt('controller', 'group_update_time'))
	{
		CAgent::RemoveAgent('CControllerGroup::CheckDefaultUpdate();', 'controller');
		if (COption::GetOptionInt('controller', 'group_update_time') > 0)
		{
			CAgent::AddAgent('CControllerGroup::CheckDefaultUpdate();', 'controller', 'N', COption::GetOptionInt('controller', 'group_update_time') * 60);
		}
	}

	if ($prev_auth_log_days != COption::GetOptionInt('controller', 'auth_log_days'))
	{
		\Bitrix\Controller\AuthLogTable::setupAgent(COption::GetOptionInt('controller', 'auth_log_days'));
	}

	if ($USER->IsAdmin())
	{
		$Update = $_REQUEST['Update'] . $_REQUEST['Apply'];
		ob_start();
		require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/admin/group_rights2.php';
		ob_end_clean();
	}

	if ($_REQUEST['back_url_settings'] <> '')
	{
		if (($_REQUEST['Apply'] <> '') || ($_REQUEST['RestoreDefaults'] <> ''))
		{
			LocalRedirect($APPLICATION->GetCurPage() . '?mid=' . urlencode($module_id) . '&lang=' . urlencode(LANGUAGE_ID) . '&back_url_settings=' . urlencode($_REQUEST['back_url_settings']) . '&' . $tabControl->ActiveTabParam());
		}
		else
		{
			LocalRedirect($_REQUEST['back_url_settings']);
		}
	}
	else
	{
		LocalRedirect($APPLICATION->GetCurPage() . '?mid=' . urlencode($module_id) . '&lang=' . urlencode(LANGUAGE_ID) . '&' . $tabControl->ActiveTabParam());
	}
}

?>
	<form method="POST" action="<?php echo $APPLICATION->GetCurPage() ?>?mid=<?=htmlspecialcharsbx($module_id)?>&amp;lang=<?php echo LANGUAGE_ID ?>">
		<?=bitrix_sessid_post()?>
		<?php
		$tabControl->Begin();

		$tabControl->BeginNextTab();
		__AdmSettingsDrawList('controller', $arOptions);

		if ($USER->IsAdmin())
		{
			$tabControl->BeginNextTab();
			require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/admin/group_rights2.php';
		}

		$tabControl->Buttons(); ?>
		<input <?php echo (!$USER->CanDoOperation('controller_settings_change')) ? 'disabled' : ''?> type="submit" name="Update" value="<?=GetMessage('MAIN_SAVE')?>" title="<?=GetMessage('MAIN_OPT_SAVE_TITLE')?>" class="adm-btn-save">
		<input <?php echo (!$USER->CanDoOperation('controller_settings_change')) ? 'disabled' : ''?> type="submit" name="Apply" value="<?=GetMessage('MAIN_OPT_APPLY')?>" title="<?=GetMessage('MAIN_OPT_APPLY_TITLE')?>">
		<?php if ($_REQUEST['back_url_settings'] <> ''): ?>
			<input <?php echo (!$USER->CanDoOperation('controller_settings_change')) ? 'disabled' : ''?> type="button" name="Cancel" value="<?=GetMessage('MAIN_OPT_CANCEL')?>" title="<?=GetMessage('MAIN_OPT_CANCEL_TITLE')?>" onclick="window.location='<?php echo htmlspecialcharsbx(CUtil::addslashes($_REQUEST['back_url_settings'])) ?>'">
			<input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($_REQUEST['back_url_settings'])?>">
		<?php endif ?>
		<input <?php echo (!$USER->CanDoOperation('controller_settings_change')) ? 'disabled' : ''?> type="submit" name="RestoreDefaults" title="<?php echo GetMessage('MAIN_HINT_RESTORE_DEFAULTS') ?>" OnClick="return confirm('<?php echo addslashes(GetMessage('MAIN_HINT_RESTORE_DEFAULTS_WARNING')) ?>')" value="<?php echo GetMessage('MAIN_RESTORE_DEFAULTS') ?>">
		<?=bitrix_sessid_post();?>
		<?php $tabControl->End(); ?>
	</form>
<?php
