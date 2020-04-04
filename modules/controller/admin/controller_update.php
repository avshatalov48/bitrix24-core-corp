<?
//**********************************************************************/
//**    DO NOT MODIFY THIS FILE                                       **/
//**    MODIFICATION OF THIS FILE WILL ENTAIL SITE FAILURE            **/
//**********************************************************************/
define("US_CALL_TYPE", "KERNEL");
define("US_BASE_MODULE", "controller");

$US_LICENSE_KEY = "";
if (file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/controller/license_key.php"))
	include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/controller/license_key.php");
if ($US_LICENSE_KEY == "" || strtoupper($US_LICENSE_KEY) == "DEMO")
	define("US_LICENSE_KEY", "DEMO");
else
	define("US_LICENSE_KEY", $US_LICENSE_KEY);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

define("US_SHARED_KERNEL_PATH", COption::GetOptionString("controller", "shared_kernel_path", "/bitrix/clients"));
define("US_SAVE_UPDATERS_DIR", US_SHARED_KERNEL_PATH."/updaters");

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client.php");

@set_time_limit(0);
ini_set("track_errors", "1");
ignore_user_abort(true);

IncludeModuleLangFile(__FILE__);

if (!$USER->CanDoOperation("controller_member_updates_run"))
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$errorMessage = "";

$stableVersionsOnly = COption::GetOptionString("controller", "stable_versions_only", "Y");
CModule::IncludeModule("controller");

$strTitle = GetMessage("SUP_TITLE_BASE");
$APPLICATION->SetTitle($strTitle);
$APPLICATION->SetAdditionalCSS("/bitrix/themes/".ADMIN_THEME_ID."/sysupdate.css");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$arMenu = array(
	array(
		"TEXT" => GetMessage("SUP_CHECK_UPDATES"),
		"LINK" => "/bitrix/admin/controller_update.php?refresh=Y&lang=".LANGUAGE_ID,
		"ICON"=>"btn_update",
	),
	array("SEPARATOR" => "Y"),
	array(
		"TEXT" => GetMessage("SUP_SETTINGS"),
		"LINK" => "/bitrix/admin/settings.php?lang=".LANGUAGE_ID."&mid=controller&back_url_settings=%2Fbitrix%2Fadmin%2Fcontroller_update.php%3Flang%3D".LANGUAGE_ID."",
	),
	/*
	array("SEPARATOR" => "Y"),
	array(
		"TEXT" => GetMessage("SUP_HISTORY"),
		"LINK" => "/bitrix/admin/sysupdate_log.php?lang=".LANGUAGE_ID,
		"ICON"=>"btn_update_log",
	)
	*/
);

$context = new CAdminContextMenu($arMenu);
$context->Show();

if (!$arUpdateList = CUpdateClient::GetUpdatesList($errorMessage, LANG, $stableVersionsOnly))
	$errorMessage .= "<br>".GetMessage("SUP_CANT_CONNECT").". ";

$strError_tmp = "";
$arClientModules = CUpdateClient::GetCurrentModules($strError_tmp);
if (StrLen($strError_tmp) > 0)
	$errorMessage .= $strError_tmp;

if ($arUpdateList)
{
	if (isset($arUpdateList["ERROR"]))
	{
		for ($i = 0, $cnt = count($arUpdateList["ERROR"]); $i < $cnt; $i++)
			$errorMessage .= "[".$arUpdateList["ERROR"][$i]["@"]["TYPE"]."] ".$arUpdateList["ERROR"][$i]["#"];
	}
}

if (strlen($errorMessage) > 0)
	echo CAdminMessage::ShowMessage(Array("DETAILS" => $errorMessage, "TYPE" => "ERROR", "MESSAGE" => GetMessage("SUP_ERROR"), "HTML" => true));

?>
<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?" name="form1">
<input type="hidden" name="lang" value="<?echo LANG ?>">
<?=bitrix_sessid_post()?>

<?
$arTabs = array(
	array(
		"DIV" => "tab1",
		"TAB" => GetMessage("SUP_TAB_UPDATES"),
		"ICON" => "",
		"TITLE" => GetMessage("SUP_TAB_UPDATES_ALT"),
	),
	array(
		"DIV" => "tab2",
		"TAB" => GetMessage("SUP_TAB_UPDATES_LIST"),
		"ICON" => "",
		"TITLE" => GetMessage("SUP_TAB_UPDATES_LIST_ALT"),
	),
	array(
		"DIV" => "tab3",
		"TAB" => GetMessage("SUP_TAB_SETTINGS"),
		"ICON" => "",
		"TITLE" => GetMessage("SUP_TAB_SETTINGS_ALT"),
	),
);

$tabControl = new CAdminTabControl("tabControl", $arTabs);
$tabControl->Begin();
?>

<?
$tabControl->BeginNextTab();
?>

	<tr>
		<td colspan="2">

			<?
			$countModuleUpdates = 0;
			$countLangUpdatesInst = 0;
			$countLangUpdatesOther = 0;
			$countTotalImportantUpdates = 0;
			$countHelpUpdatesInst = 0;
			$countHelpUpdatesOther = 0;
			$bLockControls = False;

			if ($arUpdateList)
			{
				if (isset($arUpdateList["MODULES"]) && is_array($arUpdateList["MODULES"]) && is_array($arUpdateList["MODULES"][0]["#"]["MODULE"]))
					$countModuleUpdates = count($arUpdateList["MODULES"][0]["#"]["MODULE"]);

				if (isset($arUpdateList["LANGS"]) && is_array($arUpdateList["LANGS"]) && is_array($arUpdateList["LANGS"][0]["#"]["INST"]) && is_array($arUpdateList["LANGS"][0]["#"]["INST"][0]["#"]["LANG"]))
					$countLangUpdatesInst = count($arUpdateList["LANGS"][0]["#"]["INST"][0]["#"]["LANG"]);

				if (isset($arUpdateList["LANGS"]) && is_array($arUpdateList["LANGS"]) && is_array($arUpdateList["LANGS"][0]["#"]["OTHER"]) && is_array($arUpdateList["LANGS"][0]["#"]["OTHER"][0]["#"]["LANG"]))
					$countLangUpdatesOther = count($arUpdateList["LANGS"][0]["#"]["OTHER"][0]["#"]["LANG"]);

				$countTotalImportantUpdates = $countLangUpdatesInst;
				if ($countModuleUpdates > 0)
				{
					for ($i = 0, $cnt = count($arUpdateList["MODULES"][0]["#"]["MODULE"]); $i < $cnt; $i++)
					{
						$countTotalImportantUpdates += count($arUpdateList["MODULES"][0]["#"]["MODULE"][$i]["#"]["VERSION"]);
						if (!array_key_exists($arUpdateList["MODULES"][0]["#"]["MODULE"][$i]["@"]["ID"], $arClientModules))
							$countTotalImportantUpdates += 1;
					}
				}

				$countHelpUpdatesInst = 0;
				if (isset($arUpdateList["HELPS"]) && is_array($arUpdateList["HELPS"]) && is_array($arUpdateList["HELPS"][0]["#"]["INST"]) && is_array($arUpdateList["HELPS"][0]["#"]["INST"][0]["#"]["HELP"]))
					$countHelpUpdatesInst = count($arUpdateList["HELPS"][0]["#"]["INST"][0]["#"]["HELP"]);

				$countHelpUpdatesOther = 0;
				if (isset($arUpdateList["HELPS"]) && is_array($arUpdateList["HELPS"]) && is_array($arUpdateList["HELPS"][0]["#"]["OTHER"]) && is_array($arUpdateList["HELPS"][0]["#"]["OTHER"][0]["#"]["HELP"]))
					$countHelpUpdatesOther = count($arUpdateList["HELPS"][0]["#"]["OTHER"][0]["#"]["HELP"]);

				$newLicenceSigned = COption::GetOptionString("main", "new_license6_sign", "N");
				if ($newLicenceSigned != "Y")
				{
					$bLockControls = True;
					?>
					<div id="upd_licence_div">
						<table border="0" cellspacing="1" cellpadding="3" width="100%" class="internal">
							<tr class="heading">
								<td><b><?= GetMessage("SUP_SUBT_LICENCE") ?></b></td>
							</tr>
							<tr>
								<td valign="top">
									<table cellpadding="0" cellspacing="0">
										<tr>
											<td class="icon-new"><div class="icon icon-main"></div></td>
											<td>
									<?= GetMessage("SUP_SUBT_LICENCE_HINT") ?><br><br>
									<input TYPE="button" NAME="agree_licence_btn" value="<?= GetMessage("SUP_SUBT_LICENCE_BUTTON") ?>" onclick="ShowLicence()">
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
						<br>
					</div>
					<SCRIPT LANGUAGE="JavaScript">
					<!--
					function ShowLicence()
					{
						if (document.getElementById("licence_float_div"))
							return;

						LockControls();

						var div = document.body.appendChild(document.createElement("DIV"));

						div.id = "licence_float_div";
						div.className = "settings-float-form";
						div.style.position = 'absolute';

						var txt = '<div class="title">';
						txt += '<table cellspacing="0" width="100%">';
						txt += '<tr>';
						txt += '<td width="100%" class="title-text" onmousedown="jsFloatDiv.StartDrag(arguments[0], document.getElementById(\'licence_float_div\'));"><?= GetMessage("SUP_SUBT_LICENCE") ?></td>';
						txt += '<td width="0%"><a class="close" href="javascript:CloseLicenceTextWindow();" title="<?= GetMessage("SUP_SULD_CLOSE") ?>"></a></td>';
						txt += '</tr>';
						txt += '</table>';
						txt += '</div>';
						txt += '<div class="content">';
						txt += '<form name="license_form">';
						txt += '<h2><?= GetMessage("SUP_SUBT_LICENCE") ?></h2>';
						txt += '<table cellspacing="0"><tr><td>';
						txt += '<iframe name="license_text" src="http://www.bitrixsoft.ru/license-<?= ((LANGUAGE_ID == "ru") ? "ru" : "en") ?>.htm" style="width:450px; height:250px; display:block;"></iframe>';
						txt += '</td></tr><tr><td>';
						txt += '<input name="agree_license" type="checkbox" value="Y" id="agree_license_id" onclick="AgreeLicenceCheckbox(this)">';
						txt += '<label for="agree_license_id"><?= GetMessage("SUP_SUBT_AGREE") ?></label>';
						txt += '</td></tr></table>';
						txt += '</form>';
						txt += '</div>';
						txt += '<div class="buttons">';
						txt += '<input type="button" value="<?= GetMessage("SUP_APPLY") ?>" disabled id="licence_agree_button" onclick="AgreeLicence()" title="<?= GetMessage("SUP_APPLY") ?>">';
						txt += '</div>';

						div.innerHTML = txt;

						var left = parseInt(document.body.scrollLeft + document.body.clientWidth/2 - div.offsetWidth/2);
						var top = parseInt(document.body.scrollTop + document.body.clientHeight/2 - div.offsetHeight/2);

						jsFloatDiv.Show(div, left, top);

						jsUtils.addEvent(document, "keypress", LicenceTextOnKeyPress);
					}

					function LicenceTextOnKeyPress(e)
					{
						if (!e)
							e = window.event;
						if (!e)
							return;
						if (e.keyCode == 27)
							CloseLicenceTextWindow();
					}

					function CloseLicenceTextWindow()
					{
						jsUtils.removeEvent(document, "keypress", LicenceTextOnKeyPress);
						var div = document.getElementById("licence_float_div");
						jsFloatDiv.Close(div);
						div.parentNode.removeChild(div);
					}

					function AgreeLicenceCheckbox(checkbox)
					{
						var lab = document.getElementById("licence_agree_button");
						lab.disabled=<?if(!$USER->CanDoOperation("controller_member_updates_run")):?>true<?else:?>!checkbox.checked<?endif;?>;
					}

					function AgreeLicence()
					{
						ShowWaitWindow();

						CHttpRequest.Action = function(result)
						{
							result = result.replace(/^\s+|\s+$/, '');
							CloseWaitWindow();

							if (result == "Y")
							{
								CloseLicence();
								var udl = document.getElementById("upd_licence_div");
								udl.style["display"] = "none";
								UnLockControls();
							}
							else
							{
								alert("<?= GetMessage("SUP_SUBT_ERROR_LICENCE") ?>");
							}
						}

						CHttpRequest.Send('/bitrix/admin/controller_update_act.php?query_type=licence&<?= bitrix_sessid_get() ?>');
					}

					function CloseLicence()
					{
						var div = document.getElementById("licence_float_div");
						jsFloatDiv.Close(div);
						div.parentNode.removeChild(div);
					}
					//-->
					</SCRIPT>
					<?
				}

				$bLicenseNotFound = False;
				if ($arUpdateList !== false
					&& isset($arUpdateList["ERROR"])
					&& count($arUpdateList["ERROR"]) > 0)
				{
					for ($i = 0; $i < count($arUpdateList["ERROR"]); $i++)
					{
						if ($arUpdateList["ERROR"][$i]["@"]["TYPE"] == "LICENSE_NOT_FOUND")
						{
							$bLicenseNotFound = True;
							break;
						}
					}
				}
				$strLicenseKeyTmp = CUpdateClient::GetLicenseKey();
				if (strlen($strLicenseKeyTmp) <= 0 || strtolower($strLicenseKeyTmp) == "demo" || $bLicenseNotFound)
				{
					$bLockControls = True;
					?>
					<div id="upd_key_div">
						<table border="0" cellspacing="1" cellpadding="3" width="100%" class="internal">
							<tr class="heading">
								<td><b><?= GetMessage("SUP_SUBK_KEY") ?></b></td>
							</tr>
							<tr>
								<td valign="top">
									<table cellpadding="0" cellspacing="0">
										<tr>
											<td class="icon-new"><div class="icon icon-licence"></div></td>
											<td>
									<?= GetMessage("SUP_SUBK_HINT") ?><br><br>
									<input TYPE="button" NAME="licence_key_btn" value="<?= GetMessage("SUP_SUBK_BUTTON") ?>" onclick="ShowLicenceKeyForm()"><br><br>
									<a href="http://<?= ((LANGUAGE_ID == "ru") ? "www.bitrixsoft.ru" : "www.bitrixsoft.com") ?>/bsm_register.php" target="_blank"><?= GetMessage("SUP_SUBK_GET_KEY") ?></a>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
						<br>
					</div>
					<SCRIPT LANGUAGE="JavaScript">
					<!--
					function ShowLicenceKeyForm()
					{

						if (document.getElementById("key_float_div"))
							return;

						LockControls();

						var div = document.body.appendChild(document.createElement("DIV"));

						div.id = "key_float_div";
						div.className = "settings-float-form";
						div.style.position = 'absolute';

						var txt = '<div class="title">';
						txt += '<table cellspacing="0" width="100%">';
						txt += '<tr>';
						txt += '<td width="100%" class="title-text" onmousedown="jsFloatDiv.StartDrag(arguments[0], document.getElementById(\'key_float_div\'));"><?= GetMessage("SUP_SUBK_KEY") ?></td>';
						txt += '<td width="0%"><a class="close" href="javascript:CloseLicenceWindow();" title="<?= GetMessage("SUP_SULD_CLOSE") ?>"></a></td>';
						txt += '</tr>';
						txt += '</table>';
						txt += '</div>';
						txt += '<div class="content">';
						txt += '<form name="licence_key_form">';
						txt += '<h2><?= GetMessage("SUP_SUBK_KEY") ?></h2>';
						txt += '<table cellspacing="0">';
						txt += '<tr>';
						txt += '	<td width="50%"><span class="required">*</span><?= GetMessage("SUP_SUBK_PROMT") ?>:</td>';
						txt += '	<td width="50%"><input type="text" id="id_new_license_key" name="NEW_LICENSE_KEY" value="" size="30"></td>';
						txt += '</tr>';
						txt += '</table>';
						txt += '</form>';
						txt += '</div>';
						txt += '<div class="buttons">';
						txt += '<input type="button" id="id_licence_key_form_button" value="<?= GetMessage("SUP_SUBK_SAVE") ?>" onclick="LicenceKeyFormSubmit()" title="<?= GetMessage("SUP_SUBK_SAVE") ?>">';
						txt += '</div>';

						div.innerHTML = txt;

						var left = parseInt(document.body.scrollLeft + document.body.clientWidth/2 - div.offsetWidth/2);
						var top = parseInt(document.body.scrollTop + document.body.clientHeight/2 - div.offsetHeight/2);

						jsFloatDiv.Show(div, left, top);

						jsUtils.addEvent(document, "keypress", LicenceOnKeyPress);

						document.getElementById("id_new_license_key").focus();
					}

					function LicenceOnKeyPress(e)
					{
						if (!e)
							e = window.event;
						if (!e)
							return;
						if (e.keyCode == 27)
							CloseLicenceWindow();
					}

					function CloseLicenceWindow()
					{
						jsUtils.removeEvent(document, "keypress", LicenceOnKeyPress);
						var div = document.getElementById("key_float_div");
						jsFloatDiv.Close(div);
						div.parentNode.removeChild(div);
					}

					function LicenceKeyFormSubmit()
					{
						document.getElementById("id_licence_key_form_button").disabled = true;
						ShowWaitWindow();

						var error = "";
						if (document.licence_key_form.NEW_LICENSE_KEY.value.length <= 0)
							error += "<?= GetMessage("SUP_SUBK_NO_KEY") ?>";

						if (error.length > 0)
						{
							CloseWaitWindow();
							document.getElementById("id_licence_key_form_button").disabled = false;
							alert(error);
							return false;
						}

						CHttpRequest.Action = function(result)
						{
							CloseWaitWindow();
							result = result.replace(/^\s+|\s+$/, '');
							if (result == "Y")
							{
								window.location.href = "controller_update.php?lang=<?= LANG ?>";
								//var udl = document.getElementById("upd_activate_div");
								//udl.style["display"] = "none";
								//UnLockControls();
								//CloseActivateForm();
							}
							else
							{
								document.getElementById("id_licence_key_form_button").disabled = false;
								alert("<?= GetMessage("SUP_SUBK_ERROR") ?>: " + result);
							}
						}

						CHttpRequest.Send('/bitrix/admin/controller_update_act.php?query_type=key&<?= bitrix_sessid_get() ?>&NEW_LICENSE_KEY=' + escape(document.licence_key_form.NEW_LICENSE_KEY.value));
					}
					//-->
					</SCRIPT>
					<?
				}
				else
				{
					if (isset($arUpdateList["CLIENT"]) && !isset($arUpdateList["UPDATE_SYSTEM"]) && count($arUpdateList["CLIENT"]) > 0 && $arUpdateList["CLIENT"][0]["@"]["RESERVED"] == "Y")
					{
						$bLockControls = True;
						?>
						<div id="upd_activate_div">
							<table border="0" cellspacing="1" cellpadding="3" width="100%" class="internal">
								<tr class="heading">
									<td><b><?= GetMessage("SUP_SUBA_ACTIVATE") ?></b></td>
								</tr>
								<tr>
									<td valign="top">
									<table cellpadding="0" cellspacing="0">
										<tr>
											<td class="icon-new"><div class="icon icon-licence"></div></td>
											<td>
										<?= GetMessage("SUP_SUBA_ACTIVATE_HINT") ?><br><br>
										<input TYPE="button" NAME="activate_key_btn" value="<?= GetMessage("SUP_SUBA_ACTIVATE_BUTTON") ?>" onclick="ShowActivateForm()">
											</td>
										</tr>
									</table>
									</td>
								</tr>
							</table>
							<br>
						</div>
						<SCRIPT LANGUAGE="JavaScript">
						<!--
						function ShowActivateForm()
						{
							if (document.getElementById("activate_float_div"))
								return;

							LockControls();

							var div = document.body.appendChild(document.createElement("DIV"));

							div.id = "activate_float_div";
							div.className = "settings-float-form";
							div.style.position = 'absolute';

							var txt = '<div class="title">';
							txt += '<table cellspacing="0" width="100%">';
							txt += '<tr>';
							txt += '<td width="100%" class="title-text" onmousedown="jsFloatDiv.StartDrag(arguments[0], document.getElementById(\'activate_float_div\'));"><?= GetMessage("SUP_SUBA_ACTIVATE") ?></td>';
							txt += '<td width="0%"><a class="close" href="javascript:CloseActivateWindow();" title="<?= GetMessage("SUP_SULD_CLOSE") ?>"></a></td>';
							txt += '</tr>';
							txt += '</table>';
							txt += '</div>';
							txt += '<div class="content" style="overflow:auto;overflow-y:auto;height:400px;">';
							txt += '<form name="activate_form">';
							txt += '<h2><?= GetMessage("SUP_SUBA_ACTIVATE") ?></h2>';
							txt += '<table cellspacing="0">';
							txt += '<tr class="heading"><td colspan="2"><b><?= GetMessage("SUP_SUBA_REGINFO") ?></b></td></tr>';
							txt += '<tr>';
							txt += '	<td width="50%"><span class="required">*</span><?= GetMessage("SUP_SUBA_RI_NAME") ?>:</td>';
							txt += '	<td width="50%"><input type="text" id="id_activate_name" name="NAME" value="" size="30"></td>';
							txt += '</tr>';
							//txt += '<tr>';
							//txt += '	<td colspan="2"><small><?= GetMessage("SUP_SUBA_RI_NAME1") ?></small></td>';
							//txt += '</tr>';
							txt += '<tr>';
							txt += '	<td width="50%"><span class="required">*</span><?= GetMessage("SUP_SUBA_RI_URI") ?>:</td>';
							txt += '	<td width="50%"><input type="text" name="SITE_URL" value="" size="30"></td>';
							txt += '</tr>';
							//txt += '<tr>';
							//txt += '	<td colspan="2"><small><?= GetMessage("SUP_SUBA_RI_URI1") ?></small></td>';
							//txt += '</tr>';
							txt += '<tr>';
							txt += '	<td width="50%"><span class="required">*</span><?= GetMessage("SUP_SUBA_RI_PHONE") ?>:</td>';
							txt += '	<td width="50%"><input type="text" name="PHONE" value="" size="30"></td>';
							txt += '</tr>';
							//txt += '<tr>';
							//txt += '	<td colspan="2"><small><?= GetMessage("SUP_SUBA_RI_PHONE1") ?></small></td>';
							//txt += '</tr>';
							txt += '<tr>';
							txt += '	<td width="50%"><span class="required">*</span><?= GetMessage("SUP_SUBA_RI_EMAIL") ?>:</td>';
							txt += '	<td width="50%"><input type="text" name="EMAIL" value="" size="30"></td>';
							txt += '</tr>';
							//txt += '<tr>';
							//txt += '	<td colspan="2"><small><?= GetMessage("SUP_SUBA_RI_EMAIL1") ?></small></td>';
							//txt += '</tr>';
							txt += '<tr>';
							txt += '	<td width="50%"><span class="required">*</span><?= GetMessage("SUP_SUBA_RI_CONTACT_PERSON") ?>:</td>';
							txt += '	<td width="50%"><input type="text" name="CONTACT_PERSON" value="" size="30"></td>';
							txt += '</tr>';
							//txt += '<tr>';
							//txt += '	<td colspan="2"><small><?= GetMessage("SUP_SUBA_RI_CONTACT_PERSON1") ?></small></td>';
							//txt += '</tr>';
							txt += '<tr>';
							txt += '	<td width="50%"><span class="required">*</span><?= GetMessage("SUP_SUBA_RI_CONTACT_EMAIL") ?>:</td>';
							txt += '	<td width="50%"><input type="text" name="CONTACT_EMAIL" value="" size="30"></td>';
							txt += '</tr>';
							//txt += '<tr>';
							//txt += '	<td colspan="2"><small><?= GetMessage("SUP_SUBA_RI_CONTACT_EMAIL1") ?></small></td>';
							//txt += '</tr>';
							txt += '<tr>';
							txt += '	<td width="50%"><span class="required">*</span><?= GetMessage("SUP_SUBA_RI_CONTACT_PHONE") ?>:</td>';
							txt += '	<td width="50%"><input type="text" name="CONTACT_PHONE" value="" size="30"></td>';
							txt += '</tr>';
							//txt += '<tr>';
							//txt += '	<td colspan="2"><small><?= GetMessage("SUP_SUBA_RI_CONTACT_PHONE1") ?></small></td>';
							//txt += '</tr>';
							txt += '<tr>';
							txt += '	<td width="50%"><?= GetMessage("SUP_SUBA_RI_CONTACT") ?>:</td>';
							txt += '	<td width="50%"><input type="text" name="CONTACT_INFO" value="" size="30"></td>';
							txt += '</tr>';
							//txt += '<tr>';
							//txt += '	<td colspan="2"><small><?= GetMessage("SUP_SUBA_RI_CONTACT1") ?></small></td>';
							//txt += '</tr>';
							txt += '<tr class="heading"><td colspan="2"><b><?= GetMessage("SUP_SUBA_USERINFO") ?></b></td></tr>';
							txt += '<tr><td colspan="2"><?= GetMessage("SUP_SUBA_UI_HINT") ?></td></tr>';
							txt += '<tr>';
							txt += '	<td width="50%"><?= GetMessage("SUP_SUBA_UI_CREATE") ?>:</td>';
							txt += '	<td width="50%"><input name="GENERATE_USER" type="checkbox" onclick="ActivateEnableDisableUser(this)" value="Y" checked></td>';
							txt += '</tr>';
							txt += '<tr id="tr_USER_NAME">';
							txt += '	<td width="50%"><span class="required">*</span><?= GetMessage("SUP_SUBA__UI_NAME") ?>:</td>';
							txt += '	<td width="50%"><input type="text" name="USER_NAME" value="" size="30"></td>';
							txt += '</tr>';
							txt += '<tr id="tr_USER_LAST_NAME">';
							txt += '	<td width="50%"><span class="required">*</span><?= GetMessage("SUP_SUBA_UI_LASTNAME") ?>:</td>';
							txt += '	<td width="50%"><input type="text" name="USER_LAST_NAME" value="" size="30"></td>';
							txt += '</tr>';
							txt += '<tr id="tr_USER_LOGIN">';
							txt += '	<td width="50%"><span class="required">*</span><?= GetMessage("SUP_SUBA_UI_LOGIN") ?>:</td>';
							txt += '	<td width="50%"><input type="text" name="USER_LOGIN" value="" size="30"></td>';
							txt += '</tr>';
							txt += '<tr id="tr_USER_PASSWORD">';
							txt += '	<td width="50%"><span class="required">*</span><?= GetMessage("SUP_SUBA_UI_PASSWORD") ?>:</td>';
							txt += '	<td width="50%"><input type="password" name="USER_PASSWORD" value="" size="30"></td>';
							txt += '</tr>';
							txt += '<tr id="tr_USER_PASSWORD_CONFIRM">';
							txt += '	<td width="50%"><span class="required">*</span><?= GetMessage("SUP_SUBA_UI_PASSWORD_CONF") ?>:</td>';
							txt += '	<td width="50%"><input type="password" name="USER_PASSWORD_CONFIRM" value="" size="30"></td>';
							txt += '</tr>';
							txt += '</table>';
							txt += '</form>';
							txt += '</div>';
							txt += '<div class="buttons">';
							txt += '<input type="button" id="id_activate_form_button" value="<?= GetMessage("SUP_SUBA_ACTIVATE_BUTTON") ?>" onclick="ActivateFormSubmit()" title="<?= GetMessage("SUP_SUBA_ACTIVATE_BUTTON") ?>">';
							txt += '</div>';

							div.innerHTML = txt;

							var left = parseInt(document.body.scrollLeft + document.body.clientWidth/2 - div.offsetWidth/2);
							var top = parseInt(document.body.scrollTop + document.body.clientHeight/2 - div.offsetHeight/2);

							jsFloatDiv.Show(div, left, top);

							jsUtils.addEvent(document, "keypress", ActivateOnKeyPress);

							document.getElementById("id_activate_name").focus();
						}

						function ActivateOnKeyPress(e)
						{
							if (!e)
								e = window.event;
							if (!e)
								return;
							if (e.keyCode == 27)
								CloseActivateWindow();
						}

						function CloseActivateWindow()
						{
							jsUtils.removeEvent(document, "keypress", ActivateOnKeyPress);
							var div = document.getElementById("activate_float_div");
							jsFloatDiv.Close(div);
							div.parentNode.removeChild(div);
						}

						function ActivateEnableDisableUser(checkbox)
						{
							document.activate_form.USER_NAME.disabled = !checkbox.checked;
							document.activate_form.USER_LAST_NAME.disabled = !checkbox.checked;
							document.activate_form.USER_LOGIN.disabled = !checkbox.checked;
							document.activate_form.USER_PASSWORD.disabled = !checkbox.checked;
							document.activate_form.USER_PASSWORD_CONFIRM.disabled = !checkbox.checked;

							document.getElementById("tr_USER_NAME").disabled = !checkbox.checked;
							document.getElementById("tr_USER_LAST_NAME").disabled = !checkbox.checked;
							document.getElementById("tr_USER_LOGIN").disabled = !checkbox.checked;
							document.getElementById("tr_USER_PASSWORD").disabled = !checkbox.checked;
							document.getElementById("tr_USER_PASSWORD_CONFIRM").disabled = !checkbox.checked;
						}

						function ActivateFormSubmit()
						{
							document.getElementById("id_activate_form_button").disabled = true;
							ShowWaitWindow();

							var generateUser = "N";

							var error = "";
							if (document.activate_form.NAME.value.length <= 0)
								error += "<?= GetMessage("SUP_SUBA_FE_NAME") ?>, ";
							if (document.activate_form.EMAIL.value.length <= 0)
								error += "<?= GetMessage("SUP_SUBA_FE_EMAIL") ?>, ";
							if (document.activate_form.CONTACT_INFO.value.length <= 0)
								error += "<?= GetMessage("SUP_SUBA_FE_CONTACT") ?>, ";
							if (document.activate_form.SITE_URL.value.length <= 0)
								error += "<?= GetMessage("SUP_SUBA_FE_URI") ?>, ";
							if (document.activate_form.PHONE.value.length <= 0)
								error += "<?= GetMessage("SUP_SUBA_FE_PHONE") ?>, ";
							if (document.activate_form.CONTACT_PERSON.value.length <= 0)
								error += "<?= GetMessage("SUP_SUBA_FE_CONTACT_PERSON") ?>, ";
							if (document.activate_form.CONTACT_EMAIL.value.length <= 0)
								error += "<?= GetMessage("SUP_SUBA_FE_CONTACT_EMAIL") ?>, ";
							if (document.activate_form.CONTACT_PHONE.value.length <= 0)
								error += "<?= GetMessage("SUP_SUBA_FE_CONTACT_PHONE") ?>, ";
							if (document.activate_form.GENERATE_USER.checked)
							{
								generateUser = "Y";
								if (document.activate_form.USER_NAME.value.length <= 0)
									error += "<?= GetMessage("SUP_SUBA_FE_FNAME") ?>, ";
								if (document.activate_form.USER_LAST_NAME.value.length <= 0)
									error += "<?= GetMessage("SUP_SUBA_FE_LNAME") ?>, ";
								if (document.activate_form.USER_LOGIN.value.length <= 0)
									error += "<?= GetMessage("SUP_SUBA_FE_LOGIN") ?>, ";
								if (document.activate_form.USER_PASSWORD.value.length <= 0)
									error += "<?= GetMessage("SUP_SUBA_FE_PASSWORD") ?>, ";
								if (document.activate_form.USER_PASSWORD_CONFIRM.value.length <= 0)
									error += "<?= GetMessage("SUP_SUBA_FE_PASSWORD_CONF") ?>, ";
								if (document.activate_form.USER_PASSWORD.value != document.activate_form.USER_PASSWORD_CONFIRM.value)
									error += "<?= GetMessage("SUP_SUBA_FE_CONF_ERR") ?>, ";
							}

							if (error.length > 0)
							{
								document.getElementById("id_activate_form_button").disabled = false;
								CloseWaitWindow();
								alert("<?= GetMessage("SUP_SUBA_FE_PROMT") ?>: " + error.substring(0, error.length - 2));
								return false;
							}

							var param = "NAME=" + escape(document.activate_form.NAME.value)
								+ "&EMAIL=" + escape(document.activate_form.EMAIL.value)
								+ "&CONTACT_INFO=" + escape(document.activate_form.CONTACT_INFO.value)
								+ "&PHONE=" + escape(document.activate_form.PHONE.value)
								+ "&CONTACT_PERSON=" + escape(document.activate_form.CONTACT_PERSON.value)
								+ "&CONTACT_EMAIL=" + escape(document.activate_form.CONTACT_EMAIL.value)
								+ "&CONTACT_PHONE=" + escape(document.activate_form.CONTACT_PHONE.value)
								+ "&SITE_URL=" + escape(document.activate_form.SITE_URL.value)
								+ "&GENERATE_USER=" + escape(generateUser)
								+ "&USER_NAME=" + escape(document.activate_form.USER_NAME.value)
								+ "&USER_LAST_NAME=" + escape(document.activate_form.USER_LAST_NAME.value)
								+ "&USER_LOGIN=" + escape(document.activate_form.USER_LOGIN.value)
								+ "&USER_PASSWORD=" + escape(document.activate_form.USER_PASSWORD.value)
								+ "&USER_PASSWORD_CONFIRM=" + escape(document.activate_form.USER_PASSWORD_CONFIRM.value);

							CHttpRequest.Action = function(result)
							{
								CloseWaitWindow();
								result = result.replace(/^\s+|\s+$/, '');
								if (result == "Y")
								{
									window.location.href = "controller_update.php?lang=<?= LANG ?>";
									//var udl = document.getElementById("upd_activate_div");
									//udl.style["display"] = "none";
									//UnLockControls();
									//CloseActivateForm();
								}
								else
								{
									document.getElementById("id_activate_form_button").disabled = false;
									alert("<?= GetMessage("SUP_SUBA_FE_ERRGEN") ?>: " + result);
								}
							}

							CHttpRequest.Send('/bitrix/admin/controller_update_act.php?query_type=activate&<?= bitrix_sessid_get() ?>&' + param);
						}

						function CloseActivateForm()
						{
							var div = document.getElementById("activate_float_div");
							jsFloatDiv.Close(div);
							div.parentNode.removeChild(div);
						}
						//-->
						</SCRIPT>
						<?
					}
					else
					{
						if ($arUpdateList !== false && isset($arUpdateList["UPDATE_SYSTEM"]))
						{
							$bLockControls = True;
							?>
							<div id="upd_updateupdate_div">
								<table border="0" cellspacing="1" cellpadding="3" width="100%" class="internal">
									<tr class="heading">
										<td><b><?= GetMessage("SUP_SUBU_UPDATE") ?></b></td>
									</tr>
									<tr>
										<td valign="top">
									<table cellpadding="0" cellspacing="0">
										<tr>
											<td class="icon-new"><div class="icon icon-update"></div></td>
											<td>
											<?= GetMessage("SUP_SUBU_HINT") ?><br><br>
											<input TYPE="button" id="id_updateupdate_btn" NAME="updateupdate_btn" value="<?= GetMessage("SUP_SUBU_BUTTON") ?>" onclick="UpdateUpdate()">
											</td>
										</tr>
									</table>
										</td>
									</tr>
								</table>
								<br>
							</div>
							<SCRIPT LANGUAGE="JavaScript">
							<!--
							function UpdateUpdate()
							{
								document.getElementById("id_updateupdate_btn").disabled = true;
								ShowWaitWindow();

								CHttpRequest.Action = function(result)
								{
									CloseWaitWindow();
									result = result.replace(/^\s+|\s+$/, '');
									if (result == "Y")
									{
										window.location.href = "controller_update.php?lang=<?= LANG ?>";
										//var udl = document.getElementById("upd_register_div");
										//udl.style["display"] = "none";
									}
									else
									{
										alert("<?= GetMessage("SUP_SUBU_ERROR") ?>: " + result);
										document.getElementById("id_updateupdate_btn").disabled = false;
									}
								}

								CHttpRequest.Send('/bitrix/admin/controller_update_act.php?query_type=updateupdate&<?= bitrix_sessid_get() ?>');
							}
							//-->
							</SCRIPT>
							<?
						}
					}
				}

				if ($arUpdateList !== false && defined("DEMO") && DEMO == "Y" && isset($arUpdateList["CLIENT"]) && !isset($arUpdateList["UPDATE_SYSTEM"])
					&& ($arUpdateList["CLIENT"][0]["@"]["ENC_TYPE"] == "F" || $arUpdateList["CLIENT"][0]["@"]["ENC_TYPE"] == "E"))
				{
					?>
					<div id="upd_register_div">
						<table border="0" cellspacing="1" cellpadding="3" width="100%" class="internal">
							<tr class="heading">
								<td><b><?= GetMessage("SUP_SUBR_REG") ?></b></td>
							</tr>
							<tr>
								<td valign="top">
									<table cellpadding="0" cellspacing="0">
										<tr>
											<td class="icon-new"><div class="icon icon-licence"></div></td>
											<td>
									<?= GetMessage("SUP_SUBR_HINT") ?><br><br>
									<input TYPE="button"<?= ($bLockControls ? " disabled" : "")?> id="id_register_btn" NAME="register_btn" value="<?= GetMessage("SUP_SUBR_BUTTON") ?>" onclick="RegisterSystem()">
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
						<br>
					</div>
					<SCRIPT LANGUAGE="JavaScript">
					<!--
					function RegisterSystem()
					{
						ShowWaitWindow();
						document.getElementById("id_register_btn").disabled = true;

						CHttpRequest.Action = function(result)
						{
							CloseWaitWindow();
							result = result.replace(/^\s+|\s+$/, '');
							document.getElementById("id_register_btn").disabled = false;
							if (result == "Y")
							{
								var udl = document.getElementById("upd_register_div");
								udl.style["display"] = "none";
							}
							else
							{
								alert("<?= GetMessage("SUP_SUBR_ERR") ?>: " + result);
							}
						}

						CHttpRequest.Send('/bitrix/admin/controller_update_act.php?query_type=register&<?= bitrix_sessid_get() ?>');
					}
					//-->
					</SCRIPT>
					<?
				}

				if ($arUpdateList !== false && defined("ENCODE") && ENCODE=="Y" && isset($arUpdateList["CLIENT"]) && !isset($arUpdateList["UPDATE_SYSTEM"]) && ($arUpdateList["CLIENT"][0]["@"]["ENC_TYPE"] == "F"))
				{
					?>
					<div id="upd_source_div">
						<table border="0" cellspacing="1" cellpadding="3" width="100%" class="internal">
							<tr class="heading">
								<td><b><?= GetMessage("SUP_SUBS_SOURCES") ?></b></td>
							</tr>
							<tr>
								<td valign="top">
									<table cellpadding="0" cellspacing="0">
										<tr>
											<td class="icon-new"><div class="icon icon-sources"></div></td>
											<td>
									<?= GetMessage("SUP_SUBS_HINT") ?><br><br>
									<input TYPE="button" NAME="source_btn"<?= (($bLockControls || $countModuleUpdates > 0) ? " disabled" : "") ?> value="<?= GetMessage("SUP_SUBS_BUTTON") ?>" onclick="LoadSources()">
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
						<br>
					</div>
					<SCRIPT LANGUAGE="JavaScript">
					<!--
					var modulesList = new Array();
					<?
					$i = 0;
					foreach ($arClientModules as $key => $value)
						echo "modulesList[".($i++)."] = \"".$key."\";";
					?>

					function LoadSources()
					{
						globalQuantity = <?= count($arClientModules) ?>;

						SetProgressHint("<?= GetMessage("SUP_INITIAL") ?>");

						__LoadSources();
						SetProgressD();
					}

					function __LoadSources()
					{
						document.getElementById("upd_source_div").style["display"] = "none";
						updSuccessDiv.style["display"] = "none";
						updErrorDiv.style["display"] = "none";
						updInstallDiv.style["display"] = "block";

						CHttpRequest.Action = function(result)
						{
							result = result.replace(/^\s+|\s+$/, '');
							LoadSourcesResult(result);
						}

						var requestedModules = "";
						for (var i = 0; i < modulesList.length; i++)
						{
							if (i > 0)
								requestedModules += ",";
							requestedModules += modulesList[i];
						}

						if (requestedModules.length > 0)
							CHttpRequest.Send('/bitrix/admin/controller_update_act.php?query_type=sources&<?= bitrix_sessid_get() ?>&requested_modules=' + requestedModules);
						else
							LoadSourcesResult("FIN");
					}

					function LoadSourcesResult(result)
					{
						var code = result.substring(0, 3);
						var data = result.substring(3);

						if (code == "FIN")
						{
							document.getElementById("upd_source_div").style["display"] = "none";
							updErrorDiv.style["display"] = "none";
							updInstallDiv.style["display"] = "none";
							updSuccessDiv.style["display"] = "block";

							var updSuccessDivText = document.getElementById("upd_success_div_text");
							updSuccessDivText.innerHTML = "<?= GetMessage("SUP_SUBS_SUCCESS") ?>";
						}
						else
						{
							if (code == "STP")
							{
								if (data.length > 0)
								{
									arData = data.split("|");
									globalCounter += parseInt(arData[0]);
									SetProgress(globalCounter * 100 / globalQuantity);

									if (arData.length > 1)
									{
										loadedModule = arData[1];
										SetProgressHint("<?= GetMessage("SUP_SUBS_MED") ?> " + arData[1]);
									}

									var modulesListTmp = Array();
									var j = 0;
									for (var i = 0; i < modulesList.length; i++)
									{
										if (modulesList[i] != loadedModule)
										{
											modulesListTmp[j] = modulesList[i];
											j++;
										}
									}
									modulesList = modulesListTmp;
								}

								__LoadSources();
							}
							else
							{
								document.getElementById("upd_source_div").style["display"] = "none";
								updSuccessDiv.style["display"] = "none";
								updInstallDiv.style["display"] = "none";
								updErrorDiv.style["display"] = "block";

								var updErrorDivText = document.getElementById("upd_error_div_text");
								updErrorDivText.innerHTML = data;
							}
						}
					}
					//-->
					</SCRIPT>
					<?
				}
				?>

				<div id="upd_success_div" style="display:none">
					<table border="0" cellspacing="1" cellpadding="3" width="100%" class="internal">
						<tr class="heading">
							<td><B><?= GetMessage("SUP_SUB_SUCCESS") ?></B></td>
						</tr>
						<tr>
							<td valign="top"><div id="upd_success_div_text"></div></td>
						</tr>
					</table>
				</div>

				<div id="upd_error_div" style="display:none">
					<table border="0" cellspacing="1" cellpadding="3" width="100%" class="internal">
						<tr class="heading">
							<td><B><?= GetMessage("SUP_SUB_ERROR") ?></B></td>
						</tr>
						<tr>
							<td valign="top"><div id="upd_error_div_text"></td>
						</tr>
					</table>
				</div>

				<div id="upd_install_div" style="display:none">
					<table border="0" cellspacing="1" cellpadding="3" width="100%" class="internal">
						<tr class="heading">
							<td><B><?= GetMessage("SUP_SUB_PROGRESS") ?></B></td>
						</tr>
						<tr>
							<td valign="top">
								<table border="0" cellspacing="5" cellpadding="3" width="100%">
									<tr>
										<td valign="top" width="5%">
										</td>
										<td valign="top">
											<div style="top:0px; left:0px; width:300; height:15px; background-color:#365069; font-size:1px;">
											<div style="position:relative; top:1px; left:1px; width:298px; height:13px; background-color:#ffffff; font-size:1px;">
											<div id="PBdoneD" style="position:relative; top:0px; left:0px; width:0px; height:13px; background-color:#D5E7F3; font-size:1px;">
											</div></div></div>
											<br>
											<div style="top:0px; left:0px; width:300; height:15px; background-color:#365069; font-size:1px;">
											<div style="position:relative; top:1px; left:1px; width:298px; height:13px; background-color:#ffffff; font-size:1px;">
											<div id="PBdone" style="position:relative; top:0px; left:0px; width:0px; height:13px; background-color:#D5E7F3; font-size:1px;">
											</div></div></div>
											<br>
											<div id="install_progress_hint"></div>
										</td>
										<td valign="top" align="right">
											<input TYPE="button" NAME="stop_updates" id="id_stop_updates" value="<?= GetMessage("SUP_SUB_STOP") ?>" onclick="StopUpdates()">
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</div>

				<div id="upd_select_div" style="display:block">
					<table border="0" cellspacing="1" cellpadding="3" width="100%" class="internal">
						<tr class="heading">
							<td><B><?= ($countModuleUpdates > 0 || $countLangUpdatesInst > 0) ? GetMessage("SUP_SU_TITLE1") : GetMessage("SUP_SU_TITLE2") ?></B></td>
						</tr>
						<tr>
							<td valign="top">
									<table cellpadding="0" cellspacing="0">
										<tr>
											<td class="icon-new"><div class="icon icon-main"></div></td>
											<td>
								<b><?= GetMessage("SUP_SU_RECOMEND") ?>:</b>
								<?
								$bComma = False;
								if ($countModuleUpdates > 0)
								{
									echo str_replace("#NUM#", $countModuleUpdates, GetMessage("SUP_SU_RECOMEND_MOD"));
									$bComma = True;
								}
								if ($countLangUpdatesInst > 0)
								{
									if ($bComma)
										echo ", ";
									echo str_replace("#NUM#", $countLangUpdatesInst, GetMessage("SUP_SU_RECOMEND_LAN"));
									$bComma = True;
								}
								if ($countModuleUpdates <= 0 && $countLangUpdatesInst <= 0)
									echo GetMessage("SUP_SU_RECOMEND_NO");

								if ($countLangUpdatesOther > 0 || $countHelpUpdatesOther > 0 || $countHelpUpdatesInst > 0)
								{
									echo "<br>";
									echo "<b>".GetMessage("SUP_SU_OPTION").":</b> ";
									$bComma = False;
									if ($countLangUpdatesOther > 0)
									{
										echo str_replace("#NUM#", $countLangUpdatesOther, GetMessage("SUP_SU_OPTION_LAN"));
										$bComma = True;
									}
									if ($countHelpUpdatesOther > 0 || $countHelpUpdatesInst > 0)
									{
										if ($bComma)
											echo ", ";
										echo str_replace("#NUM#", $countHelpUpdatesOther + $countHelpUpdatesInst, GetMessage("SUP_SU_OPTION_HELP"));
									}
								}
								?>
								<br><br>
								<input TYPE="button" ID="install_updates_button" NAME="install_updates"<?= (($countModuleUpdates <= 0 && $countLangUpdatesInst <= 0 || $bLockControls) ? " disabled" : "") ?> value="<?= GetMessage("SUP_SU_UPD_BUTTON") ?>" onclick="InstallUpdates()">
								<br><br>
								<span id="id_view_updates_list_span"><a id="id_view_updates_list" href="javascript:tabControl.SelectTab('tab2');"><?= GetMessage("SUP_SU_UPD_VIEW") ?></a></span>
								<br><br>
								<?
								if ($stableVersionsOnly == "N")
									echo GetMessage("SUP_STABLE_OFF_PROMT");
								else
									echo GetMessage("SUP_STABLE_ON_PROMT");
								?>
								<br><br>
								<?= GetMessage("SUP_SU_UPD_HINT") ?>
											</td>
										</tr>
									</table>
							</td>
						</tr>
					</table>
				</div>

				<script language="JavaScript">
				<!--
				var updSelectDiv = document.getElementById("upd_select_div");
				var updInstallDiv = document.getElementById("upd_install_div");
				var updSuccessDiv = document.getElementById("upd_success_div");
				var updErrorDiv = document.getElementById("upd_error_div");

				var PBdone = (ns4) ? findlayer('PBdone', document) : (ie4) ? document.all['PBdone'] : document.getElementById('PBdone');
				var PBdoneD = (ns4) ? findlayer('PBdoneD', document) : (ie4) ? document.all['PBdoneD'] : document.getElementById('PBdoneD');

				var aStrParams;

				var globalQuantity = <?= $countTotalImportantUpdates ?>;
				var globalCounter = 0;
				var globalQuantityD = 100;
				var globalCounterD = 0;

				var cycleModules = <?= ($countModuleUpdates > 0) ? "true" : "false" ?>;
				var cycleLangs = <?= ($countLangUpdatesInst > 0) ? "true" : "false" ?>;
				var cycleHelps = false;

				var bStopUpdates = false;

				function findlayer(name, doc)
				{
					var i,layer;
					for (i = 0; i < doc.layers.length; i++)
					{
						layer = doc.layers[i];
						if (layer.name == name)
							return layer;
						if (layer.document.layers.length > 0)
							if ((layer = findlayer(name, layer.document)) != null)
								return layer;
					}
					return null;
				}

				function SetProgress(val)
				{
					if (ns4)
					{
						PBdone.clip.left = 0;
						PBdone.clip.top = 0;
						PBdone.clip.right = val*298/100;
						PBdone.clip.bottom = 13;
					}
					else
						PBdone.style.width = (val*298/100) + 'px';
				}

				function SetProgressD()
				{
					globalCounterD++;
					if (globalCounterD > globalQuantityD)
						globalCounterD = 0;

					var val = globalCounterD * 100 / globalQuantityD;

					if (ns4)
					{
						PBdoneD.clip.left = 0;
						PBdoneD.clip.top = 0;
						PBdoneD.clip.right = val * 298 / 100;
						PBdoneD.clip.bottom = 13;
					}
					else
						PBdoneD.style.width = (val * 298 / 100) + 'px';

					if (!bStopUpdates)
						setTimeout(SetProgressD, 1000);
				}

				function SetProgressHint(val)
				{
					var installProgressHintDiv = document.getElementById("install_progress_hint");
					installProgressHintDiv.innerHTML = val;
				}

				function InstallUpdates()
				{
					SetProgressHint("<?= GetMessage("SUP_INITIAL") ?>");

					__InstallUpdates();
					SetProgressD();
				}

				function __InstallUpdates()
				{
					updSelectDiv.style["display"] = "none";
					updSuccessDiv.style["display"] = "none";
					updErrorDiv.style["display"] = "none";
					updInstallDiv.style["display"] = "block";

					CHttpRequest.Action = function(result)
					{
						InstallUpdatesAction(result);
					}

					var param;
					if (cycleModules)
					{
						param = "M";
					}
					else
					{
						if (cycleLangs)
						{
							param = "L";
						}
						else
						{
							if (cycleHelps)
								param = "H";
						}
					}

					CHttpRequest.Send('/bitrix/admin/controller_update_call.php?' + aStrParams + "&<?= bitrix_sessid_get() ?>&query_type=" + param);
				}

				function InstallUpdatesDoStep(data)
				{
					if (data.length > 0)
					{
						arData = data.split("|");
						globalCounter += parseInt(arData[0]);
						if (arData.length > 1)
							SetProgressHint("<?= GetMessage("SUP_SU_UPD_INSMED") ?> " + arData[1]);
						if (globalCounter > globalQuantity)
							globalCounter = 0;
						SetProgress(globalCounter * 100 / globalQuantity);
					}

					__InstallUpdates();
				}

				function InstallUpdatesAction(result)
				{
					result = result.replace(/^\s+|\s+$/, '');;
					var code = result.substring(0, 3);
					var data = result.substring(3);

					if (bStopUpdates)
					{
						CloseWaitWindow();
						code = "FIN";
						cycleModules = false;
						cycleLangs = false;
						cycleHelps = false;
					}

					if (code == "FIN")
					{
						if (cycleModules)
						{
							cycleModules = false;
						}
						else
						{
							if (cycleLangs)
							{
								cycleLangs = false;
							}
							else
							{
								if (cycleHelps)
									cycleHelps = false;
							}
						}

						if (cycleModules || cycleLangs || cycleHelps)
						{
							InstallUpdatesDoStep(data);
						}
						else
						{
							updSelectDiv.style["display"] = "none";
							updErrorDiv.style["display"] = "none";
							updInstallDiv.style["display"] = "none";
							updSuccessDiv.style["display"] = "block";
							DisableUpdatesTable();

							var updSuccessDivText = document.getElementById("upd_success_div_text");
							updSuccessDivText.innerHTML = "<?= GetMessage("SUP_SU_UPD_INSSUC") ?>: " + globalCounter;
						}
					}
					else
					{
						if (code == "STP")
						{
							InstallUpdatesDoStep(data);
						}
						else
						{
							updSelectDiv.style["display"] = "none";
							updSuccessDiv.style["display"] = "none";
							updInstallDiv.style["display"] = "none";
							updErrorDiv.style["display"] = "block";

							var updErrorDivText = document.getElementById("upd_error_div_text");
							updErrorDivText.innerHTML = data;
						}
					}
				}

				function StopUpdates()
				{
					bStopUpdates = true;
					document.getElementById("id_stop_updates").disabled = true;
					ShowWaitWindow();
				}
				//-->
				</script>
				<?
			}
			?>

		</td>
	</tr>
	<tr>
		<td colspan="2">
			<br>
					<table border="0" cellspacing="1" cellpadding="3" width="100%" class="internal">
						<tr class="heading">
							<td><b><?echo GetMessage("SUP_SERVER_ANSWER")?></b></td>
						</tr>
						<tr>
							<td valign="top">
									<table cellpadding="0" cellspacing="0">
										<tr>
											<td class="icon-new"><div class="icon icon-update"></div></td>
											<td>

			<table border="0" cellspacing="1" cellpadding="3">
				<tr>
					<td valign="top">
						<?= GetMessage("SUP_SUBI_CHECK") ?>:&nbsp;&nbsp;
					</td>
					<td valign="top">
						<?= COption::GetOptionString(US_BASE_MODULE, "update_system_check", "-") ?>
					</td>
				</tr>
				<tr>
					<td valign="top">
						<?= GetMessage("SUP_SUBI_UPD") ?>:&nbsp;&nbsp;
					</td>
					<td valign="top">
						<?= COption::GetOptionString(US_BASE_MODULE, "update_system_update", "-") ?>
					</td>
				</tr>
				<?if (is_array($arUpdateList) && array_key_exists("CLIENT", $arUpdateList)):?>
					<tr>
						<td><?echo GetMessage("SUP_REGISTERED")?>&nbsp;&nbsp;</td>
						<td><?echo $arUpdateList["CLIENT"][0]["@"]["NAME"]?></td>
					</tr>
				<?endif;?>
				<tr>
					<td><?= GetMessage("SUP_LICENSE_KEY") ?>:&nbsp;&nbsp;</td>
					<td><?echo ($USER->CanDoOperation("controller_member_updates_run")? CUpdateClient::GetLicenseKey() : "XXX-XX-XXXXXXXXXXX")?></td>
				</tr>
				<?if (is_array($arUpdateList) && array_key_exists("CLIENT", $arUpdateList)):?>
					<tr>
						<td><?echo GetMessage("SUP_EDITION")?>&nbsp;&nbsp;</td>
						<td><?echo $arUpdateList["CLIENT"][0]["@"]["LICENSE"]?></td>
					</tr>
					<tr>
						<td><?echo GetMessage("SUP_ACTIVE")?>&nbsp;&nbsp;</td>
						<td><?echo GetMessage("SUP_ACTIVE_PERIOD", array("#DATE_TO#"=>((strlen($arUpdateList["CLIENT"][0]["@"]["DATE_TO"]) > 0) ? $arUpdateList["CLIENT"][0]["@"]["DATE_TO"] : "<i>N/A</i>"), "#DATE_FROM#" => ((strlen($arUpdateList["CLIENT"]["@"]["DATE_FROM"]) > 0) ? $arUpdateList["CLIENT"][0]["@"]["DATE_FROM"] : "<i>N/A</i>")));?></td>
					</tr>
					<tr>
						<td><?echo GetMessage("SUP_SERVER")?>&nbsp;&nbsp;</td>
						<td><?echo $arUpdateList["CLIENT"][0]["@"]["HTTP_HOST"]?></td>
					</tr>
				<?else:?>
					<tr>
						<td><?echo GetMessage("SUP_SERVER")?>&nbsp;&nbsp;</td>
						<td><?echo (($s=COption::GetOptionString("main", "update_site"))==""? "-":$s)?></td>
					</tr>
				<?endif;?>
			</table>

											</td>
										</tr>
									</table>
							</td>
						</tr>
					</table>

		</td>
	</tr>

<?
$tabControl->EndTab();
$tabControl->BeginNextTab();
?>

	<tr>
		<td colspan="2">

			<table border="0" cellspacing="1" cellpadding="3" width="100%">
				<tr>
					<td>
						<?= GetMessage("SUP_SULL_CNT") ?>: <?= $countModuleUpdates + $countLangUpdatesInst + $countLangUpdatesOther + $countHelpUpdatesOther + $countHelpUpdatesInst ?><BR><BR>
						<input TYPE="button" ID="install_updates_sel_button" NAME="install_updates"<?= (($countModuleUpdates <= 0 && $countLangUpdatesInst <= 0) ? " disabled" : "") ?> value="<?= GetMessage("SUP_SULL_BUTTON") ?>" onclick="InstallUpdatesSel()">
					</td>
				</tr>
			</table>
			<br>

			<?
			if ($arUpdateList)
			{
				?>
				<table border="0" cellspacing="1" cellpadding="3" width="100%" class="internal" id="table_updates_sel_list">
					<tr>
						<td class="heading"><INPUT TYPE="checkbox" NAME="select_all" id="id_select_all" title="<?= GetMessage("SUP_SULL_CBT") ?>" onClick="SelectAllRows(this);"></td>
						<td class="heading"><B><?= GetMessage("SUP_SULL_NAME") ?></B></td>
						<td class="heading"><B><?= GetMessage("SUP_SULL_TYPE") ?></B></td>
						<td class="heading"><B><?= GetMessage("SUP_SULL_REL") ?></B></td>
						<td class="heading"><B><?= GetMessage("SUP_SULL_NOTE") ?></B></td>
					</tr>
					<?
					if (isset($arUpdateList["MODULES"][0]["#"]["MODULE"]) || isset($arUpdateList["LANGS"][0]["#"]["INST"]))
					{
						?>
						<tr>
							<td colspan="5"><?= GetMessage("SUP_SU_RECOMEND") ?></td>
						</tr>
						<?
					}
					if (isset($arUpdateList["MODULES"][0]["#"]["MODULE"]))
					{
						for ($i = 0, $cnt = count($arUpdateList["MODULES"][0]["#"]["MODULE"]); $i < $cnt; $i++)
						{
							$arModuleTmp = $arUpdateList["MODULES"][0]["#"]["MODULE"][$i];

							$strTitleTmp = $arModuleTmp["@"]["NAME"]." (".$arModuleTmp["@"]["ID"].")\n".$arModuleTmp["@"]["DESCRIPTION"]."\n";
							for ($j = 0, $cntj = count($arModuleTmp["#"]["VERSION"]); $j < $cntj; $j++)
								$strTitleTmp .= str_replace("#VER#", $arModuleTmp["#"]["VERSION"][$j]["@"]["ID"], GetMessage("SUP_SULL_VERSION"))."\n".$arModuleTmp["#"]["VERSION"][$j]["#"]["DESCRIPTION"][0]["#"]."\n";
							$strTitleTmp = htmlspecialcharsbx(preg_replace("/<.+?>/i" . BX_UTF_PCRE_MODIFIER, "", $strTitleTmp));
							?>
							<tr title="<?= $strTitleTmp ?>" ondblclick="ShowDescription('<?= htmlspecialcharsbx($arModuleTmp["@"]["ID"]) ?>')">
								<td><INPUT TYPE="checkbox" NAME="select_module_<?= htmlspecialcharsbx($arModuleTmp["@"]["ID"]) ?>" value="Y" onClick="EnableInstallButton(this);" checked></td>
								<td><?= str_replace("#NAME#", htmlspecialcharsbx($arModuleTmp["@"]["NAME"]), GetMessage("SUP_SULL_MODULE")) ?></td>
								<td><?= (array_key_exists($arUpdateList["MODULES"][0]["#"]["MODULE"][$i]["@"]["ID"], $arClientModules) ? GetMessage("SUP_SULL_REF_O") : GetMessage("SUP_SULL_REF_N")) ?></td>
								<td><?= (isset($arModuleTmp["#"]["VERSION"]) ? $arModuleTmp["#"]["VERSION"][count($arModuleTmp["#"]["VERSION"]) - 1]["@"]["ID"] : "") ?></td>
								<td><a href="javascript:ShowDescription('<?= htmlspecialcharsbx($arModuleTmp["@"]["ID"]) ?>')"><?= GetMessage("SUP_SULL_NOTE_D") ?></a></td>
							</tr>
							<?
						}
					}
					if (isset($arUpdateList["LANGS"][0]["#"]["INST"]))
					{
						for ($i = 0, $cnt = count($arUpdateList["LANGS"][0]["#"]["INST"][0]["#"]["LANG"]); $i < $cnt; $i++)
						{
							$arLangTmp = $arUpdateList["LANGS"][0]["#"]["INST"][0]["#"]["LANG"][$i];
							?>
							<tr>
								<td><INPUT TYPE="checkbox" NAME="select_lang_<?= htmlspecialcharsbx($arLangTmp["@"]["ID"]) ?>" value="Y" onClick="EnableInstallButton(this);" checked></td>
								<td><?= str_replace("#NAME#", htmlspecialcharsbx($arLangTmp["@"]["NAME"]), GetMessage("SUP_SULL_LANG")) ?></td>
								<td><?= GetMessage("SUP_SULL_REF_O") ?></td>
								<td><?= $arLangTmp["@"]["DATE"] ?></td>
								<td>&nbsp;</td>
							</tr>
							<?
						}
					}
					if (isset($arUpdateList["LANGS"][0]["#"]["OTHER"]) || isset($arUpdateList["HELPS"][0]["#"]["OTHER"]) || isset($arUpdateList["HELPS"][0]["#"]["INST"]))
					{
						?>
						<tr>
							<td colspan="5"><?= GetMessage("SUP_SU_OPTION") ?></td>
						</tr>
						<?
					}
					if (isset($arUpdateList["HELPS"][0]["#"]["INST"]))
					{
						for ($i = 0, $cnt = count($arUpdateList["HELPS"][0]["#"]["INST"][0]["#"]["HELP"]); $i < $cnt; $i++)
						{
							$arHelpTmp = $arUpdateList["HELPS"][0]["#"]["INST"][0]["#"]["HELP"][$i];
							?>
							<tr>
								<td><INPUT TYPE="checkbox" NAME="select_help_<?= htmlspecialcharsbx($arHelpTmp["@"]["ID"]) ?>" value="Y" onClick="EnableInstallButton(this);"></td>
								<td><?= str_replace("#NAME#", htmlspecialcharsbx($arHelpTmp["@"]["NAME"]), GetMessage("SUP_SULL_HELP")) ?></td>
								<td><?= GetMessage("SUP_SULL_REF_O") ?></td>
								<td><?= $arHelpTmp["@"]["DATE"] ?></td>
								<td>&nbsp;</td>
							</tr>
							<?
						}
					}
					if (isset($arUpdateList["LANGS"][0]["#"]["OTHER"]))
					{
						for ($i = 0, $cnt = count($arUpdateList["LANGS"][0]["#"]["OTHER"][0]["#"]["LANG"]); $i < $cnt; $i++)
						{
							$arLangTmp = $arUpdateList["LANGS"][0]["#"]["OTHER"][0]["#"]["LANG"][$i];
							?>
							<tr>
								<td><INPUT TYPE="checkbox" NAME="select_lang_<?= htmlspecialcharsbx($arLangTmp["@"]["ID"]) ?>" value="Y" onClick="EnableInstallButton(this);"></td>
								<td><?= str_replace("#NAME#", htmlspecialcharsbx($arLangTmp["@"]["NAME"]), GetMessage("SUP_SULL_LANG")) ?></td>
								<td><?= GetMessage("SUP_SULL_ADD") ?></td>
								<td><?= $arLangTmp["@"]["DATE"] ?></td>
								<td>&nbsp;</td>
							</tr>
							<?
						}
					}
					if (isset($arUpdateList["HELPS"][0]["#"]["OTHER"]))
					{
						for ($i = 0, $cnt = count($arUpdateList["HELPS"][0]["#"]["OTHER"][0]["#"]["HELP"]); $i < $cnt; $i++)
						{
							$arHelpTmp = $arUpdateList["HELPS"][0]["#"]["OTHER"][0]["#"]["HELP"][$i];
							?>
							<tr>
								<td><INPUT TYPE="checkbox" NAME="select_help_<?= htmlspecialcharsbx($arHelpTmp["@"]["ID"]) ?>" value="Y" onClick="EnableInstallButton(this);"></td>
								<td><?= str_replace("#NAME#", htmlspecialcharsbx($arHelpTmp["@"]["NAME"]), GetMessage("SUP_SULL_HELP")) ?></td>
								<td><?= GetMessage("SUP_SULL_ADD1") ?></td>
								<td><?= $arHelpTmp["@"]["DATE"] ?></td>
								<td>&nbsp;</td>
							</tr>
							<?
						}
					}
					?>
				</table>
				<SCRIPT LANGUAGE="JavaScript">
				<!--
					var arModuleUpdatesDescr = {<?
					if (isset($arUpdateList["MODULES"][0]["#"]["MODULE"]))
					{
						for ($i = 0, $cnt = count($arUpdateList["MODULES"][0]["#"]["MODULE"]); $i < $cnt; $i++)
						{
							$arModuleTmp = $arUpdateList["MODULES"][0]["#"]["MODULE"][$i];

							$strTitleTmp  = '<div class="title"><table cellspacing="0" width="100%"><tr>';
							$strTitleTmp .= '<td width="100%" class="title-text" onmousedown="jsFloatDiv.StartDrag(arguments[0], document.getElementById(\'updates_float_div\'));">'.GetMessage("SUP_SULD_DESC").'</td>';
							$strTitleTmp .= '<td width="0%"><a class="close" href="javascript:CloseDescription();" title="'.GetMessage("SUP_SULD_CLOSE").'"></a></td>';
							$strTitleTmp .= '</tr></table></div>';
							$strTitleTmp .= '<div class="content" style="overflow:auto;overflow-y:auto;height:400px;">';
							$strTitleTmp .= '<h2>'.$arModuleTmp["@"]["NAME"].' ('.$arModuleTmp["@"]["ID"].')'.'</h2>';
							$strTitleTmp .= '<table cellspacing="0"><tr><td>'.$arModuleTmp["@"]["DESCRIPTION"].'</td></tr></table><br>';

							if (isset($arModuleTmp["#"]["VERSION"]))
							{
								$strTitleTmp .= '<table cellspacing="0">';
								for ($j = count($arModuleTmp["#"]["VERSION"]) - 1; $j >= 0; $j--)
								{
									$strTitleTmp .= '<tr><td><b>';
									$strTitleTmp .= str_replace("#VER#", $arModuleTmp["#"]["VERSION"][$j]["@"]["ID"], GetMessage("SUP_SULL_VERSION"));
									$strTitleTmp .= '</b></td></tr>';
									$strTitleTmp .= '<tr><td>';
									$strTitleTmp .= $arModuleTmp["#"]["VERSION"][$j]["#"]["DESCRIPTION"][0]["#"];
									$strTitleTmp .= '</td></tr>';
								}
								$strTitleTmp .= '</table>';
							}

							$strTitleTmp = addslashes(preg_replace("/\r?\n/i", "<br>", $strTitleTmp));
							if ($i > 0)
								echo ",\n";
							echo "\"".htmlspecialcharsbx($arModuleTmp["@"]["ID"])."\" : \"".$strTitleTmp."\"";
						}
					}
					?>};

					var arModuleUpdatesCnt = {<?
					if ($countModuleUpdates > 0)
					{
						for ($i = 0, $cnt = count($arUpdateList["MODULES"][0]["#"]["MODULE"]); $i < $cnt; $i++)
						{
							if ($i > 0)
								echo ", ";
							echo "\"".$arUpdateList["MODULES"][0]["#"]["MODULE"][$i]["@"]["ID"]."\" : ";
							if (!array_key_exists($arUpdateList["MODULES"][0]["#"]["MODULE"][$i]["@"]["ID"], $arClientModules))
								echo count($arUpdateList["MODULES"][0]["#"]["MODULE"][$i]["#"]["VERSION"]) + 1;
							else
								echo count($arUpdateList["MODULES"][0]["#"]["MODULE"][$i]["#"]["VERSION"]);
						}
					}
					?>};


					function ShowDescription(module)
					{
						if (document.getElementById("updates_float_div"))
							CloseDescription();

						var div = document.body.appendChild(document.createElement("DIV"));
						div.id = "updates_float_div";
						div.className = "settings-float-form";
						div.style.position = 'absolute';
						div.innerHTML = arModuleUpdatesDescr[module];

						var left = parseInt(document.body.scrollLeft + document.body.clientWidth/2 - div.offsetWidth/2);
						var top = parseInt(document.body.scrollTop + document.body.clientHeight/2 - div.offsetHeight/2);

						jsFloatDiv.Show(div, left, top);

						jsUtils.addEvent(document, "keypress", DescriptionOnKeyPress);
					}

					function DescriptionOnKeyPress(e)
					{
						if (!e)
							e = window.event;
						if (!e)
							return;
						if (e.keyCode == 27)
							CloseDescription();
					}

					function CloseDescription()
					{
						jsUtils.removeEvent(document, "keypress", DescriptionOnKeyPress);
						var div = document.getElementById("updates_float_div");
						jsFloatDiv.Close(div);
						div.parentNode.removeChild(div);
					}

					function DisableUpdatesTable()
					{
						document.getElementById("install_updates_sel_button").disabled = true;

						var tableUpdatesSelList = document.getElementById("table_updates_sel_list");
						var i;
						var n = tableUpdatesSelList.rows.length;
						for (i = 0; i < n; i++)
						{
							var box = tableUpdatesSelList.rows[i].cells[0].childNodes[0];
							if (box && box.tagName && box.tagName.toUpperCase() == 'INPUT' && box.type.toUpperCase() == "CHECKBOX")
							{
								box.disabled = true;
							}
						}
					}

					function InstallUpdatesSel()
					{
						SetProgressHint("<?= GetMessage("SUP_INITIAL") ?>");

						var moduleList = "";
						var langList = "";
						var helpList = "";

						globalQuantity = 0;

						var tableUpdatesSelList = document.getElementById("table_updates_sel_list");
						var i;
						var n = tableUpdatesSelList.rows.length;
						for (i = 1; i < n; i++)
						{
							var box = tableUpdatesSelList.rows[i].cells[0].childNodes[0];
							if (box && box.tagName && box.tagName.toUpperCase() == 'INPUT' && box.type.toUpperCase() == "CHECKBOX")
							{
								if (box.checked)
								{
									if (box.name.substring(0, 14) == "select_module_")
									{
										if (moduleList.length > 0)
											moduleList += ",";
										moduleList += box.name.substring(14);
										globalQuantity += arModuleUpdatesCnt[box.name.substring(14)];
									}
									else
									{
										if (box.name.substring(0, 12) == "select_lang_")
										{
											if (langList.length > 0)
												langList += ",";
											langList += box.name.substring(12);
											globalQuantity += 1;
										}
										else
										{
											if (box.name.substring(0, 12) == "select_help_")
											{
												if (helpList.length > 0)
													helpList += ",";
												helpList += box.name.substring(12);
												globalQuantity += 1;
											}
										}
									}
								}
							}
						}

						var additionalParams = "";
						cycleModules = false;
						cycleLangs = false;
						cycleHelps = false;
						if (moduleList.length > 0)
						{
							cycleModules = true;
							if (additionalParams.length > 0)
								additionalParams += "&";
							additionalParams += "requested_modules=" + moduleList;
						}
						if (langList.length > 0)
						{
							cycleLangs = true;
							if (additionalParams.length > 0)
								additionalParams += "&";
							additionalParams += "requested_langs=" + langList;
						}
						if (helpList.length > 0)
						{
							cycleHelps = true;
							if (additionalParams.length > 0)
								additionalParams += "&";
							additionalParams += "requested_helps=" + helpList;
						}

						aStrParams = additionalParams;

						tabControl.SelectTab('tab1');
						__InstallUpdates();
						SetProgressD();
					}

					function EnableInstallButton(checkbox)
					{
						var tbl = checkbox.parentNode.parentNode.parentNode.parentNode;
						var bEnable = false;
						var i;
						var n = tbl.rows.length;
						for (i = 1; i < n; i++)
						{
							var box = tbl.rows[i].cells[0].childNodes[0];
							if (box && box.tagName && box.tagName.toUpperCase() == 'INPUT' && box.type.toUpperCase() == "CHECKBOX")
							{
								if (box.checked && !box.disabled)
								{
									bEnable = true;
									break;
								}
							}
						}
						var installUpdatesSelButton = document.getElementById("install_updates_sel_button");
						installUpdatesSelButton.disabled = !bEnable;
					}

					function SelectAllRows(checkbox)
					{
						var tbl = checkbox.parentNode.parentNode.parentNode.parentNode;
						var bChecked = checkbox.checked;
						var i;
						var n = tbl.rows.length;
						for (i = 1; i < n; i++)
						{
							var box = tbl.rows[i].cells[0].childNodes[0];
							if (box && box.tagName && box.tagName.toUpperCase() == 'INPUT' && box.type.toUpperCase() == "CHECKBOX")
							{
								if (box.checked != bChecked && !box.disabled)
									box.checked = bChecked;
							}
						}
						var installUpdatesSelButton = document.getElementById("install_updates_sel_button");
						installUpdatesSelButton.disabled = !bChecked;
					}

					function LockControls()
					{
						tabControl.SelectTab('tab1');
						//tabControl.DisableTab('tab1');
						tabControl.DisableTab('tab2');
						tabControl.DisableTab('tab3');
						document.getElementById("install_updates_button").disabled = true;
						document.getElementById("id_view_updates_list_span").innerHTML = "<u><?= GetMessage("SUP_SU_UPD_VIEW") ?></u>";
						document.getElementById("id_view_updates_list_span").disabled = true;
					}

					function UnLockControls()
					{
						tabControl.EnableTab('tab1');
						tabControl.EnableTab('tab2');
						tabControl.EnableTab('tab3');
						document.getElementById("install_updates_button").disabled = <?= (($countModuleUpdates <= 0 && $countLangUpdatesInst <= 0) ? "true" : "false") ?>;
						document.getElementById("id_view_updates_list_span").disabled = false;
						document.getElementById("id_view_updates_list_span").innerHTML = '<a id="id_view_updates_list" href="javascript:tabControl.SelectTab(\'tab2\');"><?= GetMessage("SUP_SU_UPD_VIEW") ?></a>';

						var cnt = document.getElementById("id_register_btn");
						if (cnt != null)
							cnt.disabled = false;
					}
				//-->
				</SCRIPT>
				<?
			}
			?>
		</td>
	</tr>

<?
$tabControl->EndTab();
$tabControl->BeginNextTab();
?>

	<tr>
		<td colspan="2">

			<div id="upd_add_coupon_div">
				<table border="0" cellspacing="1" cellpadding="3" width="100%" class="internal">
					<tr class="heading">
						<td><B><?= GetMessage("SUP_SUAC_COUP") ?></B></td>
					</tr>
					<tr>
						<td>
									<table cellpadding="0" cellspacing="0">
										<tr>
											<td class="icon-new"><div class="icon icon-licence"></div></td>
											<td>
							<?= str_replace("#NUM#", $arUpdateList["CLIENT"][0]["@"]["MAX_SITES"], GetMessage("SUP_SUAC_LIMIT")) ?>
							<br><br>
							<?= GetMessage("SUP_SUAC_HINT") ?>
							<br><br>
							<?= GetMessage("SUP_SUAC_PROMT") ?>:<br>
							<INPUT TYPE="text" ID="id_coupon" NAME="COUPON" value="" size="35">
							<input TYPE="button" ID="id_coupon_btn" NAME="coupon_btn" value="<?= GetMessage("SUP_SUAC_BUTTON") ?>" onclick="ActivateCoupon()">
											</td>
										</tr>
									</table>
						</td>
					</tr>
				</table>
			</div>
			<SCRIPT LANGUAGE="JavaScript">
			<!--
			function ActivateCoupon()
			{
				document.getElementById("id_coupon_btn").disabled = true;
				ShowWaitWindow();

				CHttpRequest.Action = function(result)
				{
					CloseWaitWindow();
					result = result.replace(/^\s+|\s+$/, '');
					if (result == "Y")
					{
						alert("<?= GetMessage("SUP_SUAC_SUCCESS") ?>");
						window.location.href = "controller_update.php?lang=<?= LANG ?>";
					}
					else
					{
						alert("<?= GetMessage("SUP_SUAC_ERROR") ?>: " + result);
						document.getElementById("id_coupon_btn").disabled = false;
					}
				}

				var param = document.getElementById("id_coupon").value;

				if (param.length > 0)
				{
					CHttpRequest.Send('/bitrix/admin/controller_update_act.php?query_type=coupon&<?= bitrix_sessid_get() ?>&COUPON=' + escape(param));
				}
				else
				{
					document.getElementById("id_coupon_btn").disabled = false;
					CloseWaitWindow();
					alert("<?= GetMessage("SUP_SUAC_NO_COUP") ?>");
				}
			}
			//-->
			</SCRIPT>

			<BR>

			<div id="upd_stability_div">
				<table border="0" cellspacing="1" cellpadding="3" width="100%" class="internal">
					<tr class="heading">
						<td><B><?= GetMessage("SUP_SUBV_BETA") ?></B></td>
					</tr>
					<tr>
						<td>
									<table cellpadding="0" cellspacing="0">
										<tr>
											<td class="icon-new"><div class="icon icon-beta"></div></td>
											<td>
							<?
							if ($stableVersionsOnly == "N")
								echo GetMessage("SUP_STABLE_OFF_PROMT");
							else
								echo GetMessage("SUP_STABLE_ON_PROMT");
							?>
							<br><br>
							<?= GetMessage("SUP_SUBV_HINT") ?><br><br>
							<input TYPE="button" ID="id_stable_btn" NAME="stable_btn" value="<?= (($stableVersionsOnly == "N") ? GetMessage("SUP_SUBV_STABB") : GetMessage("SUP_SUBV_BETB")) ?>" onclick="SwithStability()">
											</td>
										</tr>
									</table>
						</td>
					</tr>
				</table>
			</div>
			<SCRIPT LANGUAGE="JavaScript">
			<!--
			function SwithStability()
			{
				document.getElementById("id_stable_btn").disabled = true;
				ShowWaitWindow();

				CHttpRequest.Action = function(result)
				{
					result = result.replace(/^\s+|\s+$/, '');
					if (result == "Y")
					{
						window.location.href = "controller_update.php?lang=<?= LANG ?>";
					}
					else
					{
						CloseWaitWindow();
						alert("<?= GetMessage("SUP_SUBV_ERROR") ?>: " + result);
						document.getElementById("id_stable_btn").disabled = false;
					}
				}

				CHttpRequest.Send('/bitrix/admin/controller_update_act.php?query_type=stability&<?= bitrix_sessid_get() ?>&STABILITY=' + escape("<?= $stableVersionsOnly ?>"));
			}
			//-->
			</SCRIPT>

			<BR>

			<div id="upd_mail_div">
				<table border="0" cellspacing="1" cellpadding="3" width="100%" class="internal">
					<tr class="heading">
						<td><B><?= GetMessage("SUP_SUSU_TITLE") ?></B></td>
					</tr>
					<tr>
						<td>
									<table cellpadding="0" cellspacing="0">
										<tr>
											<td class="icon-new"><div class="icon icon-subscribe"></div></td>
											<td>
							<?= GetMessage("SUP_SUSU_HINT") ?>
							<br><br>
							<?= GetMessage("SUP_SUSU_EMAIL") ?>: <br>
							<INPUT TYPE="text" ID="id_email" NAME="EMAIL" value="" size="35">
							<input TYPE="button" ID="id_email_btn" NAME="email_btn" value="<?= GetMessage("SUP_SUSU_BUTTON") ?>" onclick="SubscribeMail()">
											</td>
										</tr>
									</table>
						</td>
					</tr>
				</table>
			</div>
			<SCRIPT LANGUAGE="JavaScript">
			<!--
			function SubscribeMail()
			{
				document.getElementById("id_email_btn").disabled = true;
				ShowWaitWindow();

				CHttpRequest.Action = function(result)
				{
					CloseWaitWindow();
					result = result.replace(/^\s+|\s+$/, '');
					document.getElementById("id_email_btn").disabled = false;
					if (result == "Y")
					{
						alert("<?= GetMessage("SUP_SUSU_SUCCESS") ?>");
					}
					else
					{
						alert("<?= GetMessage("SUP_SUSU_ERROR") ?>: " + result);
					}
				}

				var param = document.getElementById("id_email").value;

				if (param.length > 0)
				{
					CHttpRequest.Send('/bitrix/admin/controller_update_act.php?query_type=mail&<?= bitrix_sessid_get() ?>&EMAIL=' + escape(param));
				}
				else
				{
					CloseWaitWindow();
					document.getElementById("id_email_btn").disabled = false;
					alert("<?= GetMessage("SUP_SUSU_NO_EMAIL") ?>");
				}
			}
			//-->
			</SCRIPT>

		</td>
	</tr>

<?
$tabControl->EndTab();
$tabControl->End();
?>

<SCRIPT LANGUAGE="JavaScript">
<!--
	<?
	if ($bLockControls)
		echo "LockControls();";
	?>
//-->
</SCRIPT>

</form>

<?echo BeginNote();?>
<?= GetMessage("SUP_SUG_NOTES") ?><br><br>
<?= GetMessage("SUP_SUG_NOTES1") ?>
<?echo EndNote(); ?>

<?
COption::SetOptionString(US_BASE_MODULE, "update_system_check", Date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")), time()));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>
