<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @global CUser $USER */

if (!$USER->CanDoOperation("controller_auth_view") || !CModule::IncludeModule("controller"))
{
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/controller/prolog.php");

IncludeModuleLangFile(__FILE__);

$aTabs = array(
	array(
		"DIV" => "auth_cs",
		"TAB" => GetMessage("CTRLR_AUTH_TAB"),
		"ICON" => "main_user_edit",
		"TITLE" => "",
	),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs, true, true);

if (
	$_SERVER["REQUEST_METHOD"] === "POST"
	&& (
		$_REQUEST["save"] != ""
		|| $_REQUEST["apply"] != ""
	)
	&& $USER->CanDoOperation("controller_auth_manage")
	&& check_bitrix_sessid()
)
{
	COption::SetOptionString("controller", "auth_loc_enabled", $_POST["auth_cs"] === "Y"? "Y": "N");
	COption::SetOptionString("controller", "auth_trans_enabled", $_POST["auth_ss"] === "Y"? "Y": "N");
	COption::SetOptionString("controller", "auth_controller_enabled", $_POST["auth_sc"] === "Y"? "Y": "N");

	if (COption::GetOptionString("controller", "auth_controller_enabled", "N") === "Y")
		RegisterModuleDependences("main", "OnUserLoginExternal", "main", "CControllerClient", "OnExternalLogin", 1);
	else
		UnRegisterModuleDependences("main", "OnUserLoginExternal", "main", "CControllerClient", "OnExternalLogin", 1);

	if ($_REQUEST["save"] != "" && $_GET["return_url"] != "")
	{
		LocalRedirect($_GET["return_url"]);
	}
	LocalRedirect("/bitrix/admin/controller_auth.php?lang=".LANGUAGE_ID.($_GET["return_url"]? "&return_url=".urlencode($_GET["return_url"]): "")."&".$tabControl->ActiveTabParam());
}

$APPLICATION->SetTitle(GetMessage("CTRLR_AUTH_TITLE"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

?>
	<form method="POST"
		action="controller_auth.php?lang=<? echo LANGUAGE_ID ?><? echo $_GET["return_url"]? "&amp;return_url=".urlencode($_GET["return_url"]): "" ?>"
		enctype="multipart/form-data" name="editform">
		<?
		$tabControl->Begin();
		$tabControl->BeginNextTab();
		?>
		<tr class="heading">
			<td colspan="2"><? echo GetMessage("CTRLR_AUTH_CS_TAB_TITLE") ?></td>
		</tr>
		<tr>
			<td width="40%"><label for="cauth_cs"><? echo GetMessage("CTRLR_AUTH_CS_LABEL") ?></label> <span
					class="required"><sup>1</sup></span>:
			</td>
			<td><input type="hidden" name="auth_cs" value="N"><input type="checkbox" value="Y"
					name="auth_cs"
					id="cauth_cs" <? if (COption::GetOptionString("controller", "auth_loc_enabled") == "Y") echo 'checked="checked"'; ?>>
			</td>
		</tr>
		<tr>
			<td class="adm-detail-valign-top"><? echo GetMessage("CTRLR_AUTH_LOC_GROUPS") ?>:</td>
			<td>
				<a href="<?echo htmlspecialcharsbx('controller_group_map.php?lang='.urlencode(LANGUAGE_ID).'&type=loc')?>"><? echo GetMessage("CTRLR_AUTH_SETUP") ?></a>
			</td>
		</tr>
		<tr class="heading">
			<td colspan="2"><? echo GetMessage("CTRLR_AUTH_SS_TAB_TITLE") ?></td>
		</tr>
		<tr>
			<td nowrap><label for="cauth_ss"><? echo GetMessage("CTRLR_AUTH_SS_LABEL") ?></label>:</td>
			<td><input type="hidden" name="auth_ss" value="N"><input type="checkbox" value="Y"
					name="auth_ss"
					id="cauth_ss" <? if (COption::GetOptionString("controller", "auth_trans_enabled") == "Y") echo 'checked="checked"'; ?>>
			</td>
		</tr>
		<tr>
			<td class="adm-detail-valign-top"><? echo GetMessage("CTRLR_AUTH_LOC_GROUPS") ?>:</td>
			<td>
				<a href="<?echo htmlspecialcharsbx('controller_group_map.php?lang='.urlencode(LANGUAGE_ID).'&type=trans')?>"><? echo GetMessage("CTRLR_AUTH_SETUP") ?></a>
			</td>
		</tr>
		<tr class="heading">
			<td colspan="2"><? echo GetMessage("CTRLR_AUTH_SC_TAB_TITLE") ?></td>
		</tr>
		<tr>
			<td nowrap><label for="cauth_sc"><? echo GetMessage("CTRLR_AUTH_SC_LABEL") ?></label>:</td>
			<td><input type="hidden" name="auth_sc" value="N"><input type="checkbox" value="Y"
					name="auth_sc"
					id="cauth_sc" <? if (COption::GetOptionString("controller", "auth_controller_enabled") == "Y") echo 'checked="checked"'; ?>>
			</td>
		</tr>
		<tr>
			<td class="adm-detail-valign-top"><? echo GetMessage("CTRLR_AUTH_LOC_GROUPS") ?>:</td>
			<td>
				<a href="<?echo htmlspecialcharsbx('controller_group_map.php?lang='.urlencode(LANGUAGE_ID).'&type=')?>"><? echo GetMessage("CTRLR_AUTH_SETUP") ?></a>
			</td>
		</tr>
		<?
		$tabControl->Buttons(
			array(
				"disabled" => !$USER->CanDoOperation("controller_auth_manage"),
				"back_url" => $_GET["return_url"]? $_GET["return_url"]: "controller_auth.php?lang=".LANGUAGE_ID,
			)
		);
		?>
		<? echo bitrix_sessid_post(); ?>
		<input type="hidden" name="lang" value="<? echo LANGUAGE_ID ?>">
		<?
		$tabControl->End();
		?>
	</form>
<? echo BeginNote(); ?>
	<span class="required"><sup>1</sup></span><? echo GetMessage("CTRLR_AUTH_NOTE") ?>
<? echo EndNote(); ?>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>