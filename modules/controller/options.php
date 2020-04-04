<?
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @global CUser $USER */

$module_id = "controller";
if (!$USER->CanDoOperation("controller_settings_view") || !CModule::IncludeModule("controller"))
{
	return;
}

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");
IncludeModuleLangFile(__FILE__);

$arGroups = array();
$dbr_groups = CControllerGroup::GetList(Array("SORT" => "ASC", "ID" => "ASC"));
while ($ar_groups = $dbr_groups->GetNext())
{
	$arGroups[$ar_groups["ID"]] = $ar_groups["NAME"]." [".$ar_groups["ID"]."]";
}

$arOptions = array(
	array("default_group", GetMessage("CTRLR_OPTIONS_DEF_GROUP"), 1, array("selectbox", $arGroups)),
	array("group_update_time", GetMessage("CTRLR_OPTIONS_TIME_AUTOUPDATE"), 0, array("text", 5)),
	array("show_hostname", GetMessage("CTRLR_OPTIONS_SHOW_HOSTNAME"), 0, array("checkbox")),
);
if (ControllerIsSharedMode())
{
	$arOptions[] = array("shared_kernel_path", GetMessage("CTRLR_OPTIONS_SHARED_KERNEL_PATH"), "", array("text", 50));
}
$arOptions[] = array("auth_log_days", GetMessage("CTRLR_OPTIONS_AUTH_LOG_DAYS"), 0, array("text", 6));

$aTabs = array();
$aTabs[] = array(
	"DIV" => "edit1",
	"TAB" => GetMessage("MAIN_TAB_SET"),
	"ICON" => "main_settings",
	"TITLE" => GetMessage("MAIN_TAB_TITLE_SET"),
);
if ($USER->IsAdmin())
{
	$aTabs[] = array(
		"DIV" => "edit3",
		"TAB" => GetMessage("MAIN_TAB_RIGHTS"),
		"ICON" => "main_settings",
		"TITLE" => GetMessage("MAIN_TAB_TITLE_RIGHTS"),
	);
}

$tabControl = new CAdminTabControl("tabControl", $aTabs);

if (
	$_SERVER['REQUEST_METHOD'] == "POST"
	&& (strlen($_REQUEST['Update']) > 0 || strlen($_REQUEST['Apply']) > 0 || strlen($_REQUEST['RestoreDefaults']) > 0)
	&& $USER->CanDoOperation("controller_settings_change")
	&& check_bitrix_sessid()
)
{
	if (strlen($_REQUEST['RestoreDefaults']) > 0)
	{
		COption::RemoveOption("controller");
	}

	$prev_group_update_time = COption::GetOptionInt("controller", "group_update_time");
	$prev_auth_log_days = COption::GetOptionInt("controller", "auth_log_days");

	__AdmSettingsSaveOptions("controller", $arOptions);

	if ($prev_group_update_time != COption::GetOptionInt("controller", "group_update_time"))
	{
		CAgent::RemoveAgent("CControllerGroup::CheckDefaultUpdate();", "controller");
		if (COption::GetOptionInt("controller", "group_update_time") > 0)
			CAgent::AddAgent("CControllerGroup::CheckDefaultUpdate();", "controller", "N", COption::GetOptionInt("controller", "group_update_time") * 60);
	}

	if ($prev_auth_log_days != COption::GetOptionInt("controller", "auth_log_days"))
	{
		\Bitrix\Controller\AuthLogTable::setupAgent(COption::GetOptionInt("controller", "auth_log_days"));
	}

	if ($USER->IsAdmin())
	{
		$Update = $_REQUEST['Update'].$_REQUEST['Apply'];
		ob_start();
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights2.php");
		ob_end_clean();
	}

	if (strlen($_REQUEST["back_url_settings"]) > 0)
	{
		if ((strlen($_REQUEST['Apply']) > 0) || (strlen($_REQUEST['RestoreDefaults']) > 0))
			LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($module_id)."&lang=".urlencode(LANGUAGE_ID)."&back_url_settings=".urlencode($_REQUEST["back_url_settings"])."&".$tabControl->ActiveTabParam());
		else
			LocalRedirect($_REQUEST["back_url_settings"]);
	}
	else
	{
		LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($module_id)."&lang=".urlencode(LANGUAGE_ID)."&".$tabControl->ActiveTabParam());
	}
}

?>
	<form method="POST" action="<? echo $APPLICATION->GetCurPage() ?>?mid=<?=htmlspecialcharsbx($module_id)?>&amp;lang=<? echo LANGUAGE_ID ?>">
		<?=bitrix_sessid_post()?>
		<?
		$tabControl->Begin();

		$tabControl->BeginNextTab();
		__AdmSettingsDrawList("controller", $arOptions);

		if ($USER->IsAdmin())
		{
			$tabControl->BeginNextTab();
			require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights2.php");
		}

		$tabControl->Buttons(); ?>
		<input <? if (!$USER->CanDoOperation("controller_settings_change")) echo "disabled" ?> type="submit" name="Update" value="<?=GetMessage("MAIN_SAVE")?>" title="<?=GetMessage("MAIN_OPT_SAVE_TITLE")?>" class="adm-btn-save">
		<input <? if (!$USER->CanDoOperation("controller_settings_change")) echo "disabled" ?> type="submit" name="Apply" value="<?=GetMessage("MAIN_OPT_APPLY")?>" title="<?=GetMessage("MAIN_OPT_APPLY_TITLE")?>">
		<? if (strlen($_REQUEST["back_url_settings"]) > 0): ?>
			<input <? if (!$USER->CanDoOperation("controller_settings_change")) echo "disabled" ?> type="button" name="Cancel" value="<?=GetMessage("MAIN_OPT_CANCEL")?>" title="<?=GetMessage("MAIN_OPT_CANCEL_TITLE")?>" onclick="window.location='<? echo htmlspecialcharsbx(CUtil::addslashes($_REQUEST["back_url_settings"])) ?>'">
			<input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($_REQUEST["back_url_settings"])?>">
		<? endif ?>
		<input <? if (!$USER->CanDoOperation("controller_settings_change")) echo "disabled" ?> type="submit" name="RestoreDefaults" title="<? echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS") ?>" OnClick="return confirm('<? echo AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING")) ?>')" value="<? echo GetMessage("MAIN_RESTORE_DEFAULTS") ?>">
		<?=bitrix_sessid_post();?>
		<? $tabControl->End(); ?>
	</form>
<?
