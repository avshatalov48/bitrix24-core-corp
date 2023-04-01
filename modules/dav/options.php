<?
if(!$USER->IsAdmin())
	return;

CModule::IncludeModule("dav");

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/options.php");
IncludeModuleLangFile(__FILE__);

$arAllOptions2 = array(
	array("agent_calendar", GetMessage("DAV_AGENT_CALENDAR"), "N", array("checkbox")),
	array("agent_contacts", GetMessage("DAV_AGENT_CONTACTS"), "N", array("checkbox")),
	array("agent_tasks", GetMessage("DAV_AGENT_TASKS"), "N", array("checkbox")),
	array("agent_mail", GetMessage("DAV_AGENT_MAIL"), "N", array("checkbox")),
	array("exchange_scheme", GetMessage("DAV_EXCHANGE_SCHEME"), "http", array("selectbox", array("http" => "HTTP", "https" => "HTTPS"))),
	array("exchange_server", GetMessage("DAV_EXCHANGE_SERVER"), "", array("text")),
	array("exchange_port", GetMessage("DAV_EXCHANGE_PORT"), "80", array("text")),
	array("exchange_username", GetMessage("DAV_EXCHANGE_USERNAME"), "", array("text")),
	array("exchange_password", GetMessage("DAV_EXCHANGE_PASSWORD"), "", array("password")),
	array("exchange_mailbox", GetMessage("DAV_EXCHANGE_MAILBOX"), "@test.local", array("text")),
	array("exchange_use_login", GetMessage("DAV_EXCHANGE_USE_LOGIN"), "Y", array("checkbox")),
	array("exchange_mailbox_path", GetMessage("DAV_EXCHANGE_MAILBOX_PATH"), "", array("text")),
);

$arAllOptions3 = array(
	array("agent_calendar_caldav", GetMessage("DAV_AGENT_CALENDAR_CALDAV"), "N", array("checkbox")),
);

$arAllOptions4 = array(
	array("use_proxy", GetMessage("DAV_USE_PROXY"), "N", array("checkbox")),
	array("proxy_scheme", GetMessage("DAV_PROXY_SCHEME"), "http", array("selectbox", array("http" => "HTTP", "https" => "HTTPS"))),
	array("proxy_host", GetMessage("DAV_PROXY_SERVER"), "", array("text")),
	array("proxy_port", GetMessage("DAV_PROXY_PORT"), "80", array("text")),
	array("proxy_username", GetMessage("DAV_PROXY_USERNAME"), "", array("text")),
	array("proxy_password", GetMessage("DAV_PROXY_PASSWORD"), "", array("password")),
);

$arAllOptions = array_merge($arAllOptions2, $arAllOptions3, $arAllOptions4);

$aTabs = array(
	array("DIV" => "exchange1", "TAB" => GetMessage("EXCHANGE_TAB_SET"), "ICON" => "ib_settings", "TITLE" => GetMessage("EXCHANGE_TAB_TITLE_SET")),
	array("DIV" => "dav1", "TAB" => GetMessage("DAV_TAB_SET"), "ICON" => "ib_settings", "TITLE" => GetMessage("DAV_TAB_TITLE_SET")),
	array("DIV" => "proxy1", "TAB" => GetMessage("PROXY_TAB_SET"), "ICON" => "ib_settings", "TITLE" => GetMessage("PROXY_TAB_TITLE_SET")),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

if(
	($REQUEST_METHOD ?? null) === "POST" &&
	(!empty($Update) || !empty($Apply) || !empty($RestoreDefaults))
	&& check_bitrix_sessid()
)
{
	if(!empty($RestoreDefaults))
	{
		COption::RemoveOption("dav");

		CAgent::RemoveAgent("CDavExchangeCalendar::DataSync();", "dav");
		CAgent::RemoveAgent("CDavGroupdavClientCalendar::DataSync();", "dav");
		CAgent::RemoveAgent("CDavExchangeContacts::DataSync();", "dav");
		CAgent::RemoveAgent("CDavExchangeTasks::DataSync();", "dav");
		CAgent::RemoveAgent("CDavExchangeMail::DataSync();", "dav");
	}
	else
	{
		foreach($arAllOptions as $arOption)
		{
			$name = $arOption[0] ?? null;
			$val = $_REQUEST[$name] ?? null;
			if($arOption[3][0]=="checkbox" && $val!="Y")
				$val="N";

			if (in_array($name, array("agent_calendar", "agent_calendar_caldav", "agent_contacts", "agent_tasks", "agent_mail")))
				$oldVal = COption::GetOptionString("dav", $name, "N");
			elseif ($name == "exchange_mailbox")
				$val = (((mb_substr($val, 0, 1) == "@" || $val === '') ? "" : "@").$val);

			COption::SetOptionString("dav", $name, $val, $arOption[1]);

			if (in_array($name, array("agent_calendar", "agent_calendar_caldav", "agent_contacts", "agent_tasks", "agent_mail")) && ($val != $oldVal))
			{
				if ($name == "agent_calendar")
					$s = "CDavExchangeCalendar";
				elseif ($name == "agent_calendar_caldav")
					$s = "CDavGroupdavClientCalendar";
				elseif ($name == "agent_contacts")
					$s = "CDavExchangeContacts";
				elseif ($name == "agent_mail")
					$s = "CDavExchangeMail";
				else
					$s = "CDavExchangeTasks";

				if ($val == "Y")
					CAgent::AddAgent($s."::DataSync();", "dav", "N", 60);
				else
					CAgent::RemoveAgent($s."::DataSync();", "dav");
			}

			if (($name == "exchange_use_login") && ($val == "N"))
			{
				$bFound1 = false;
				$arUserCustomFields1 = $USER_FIELD_MANAGER->GetUserFields("USER");
				foreach ($arUserCustomFields1 as $key1 => $value1)
				{
					if ($key1 == "UF_BXDAVEX_MAILBOX")
						$bFound1 = true;
				}
				if (!$bFound1)
				{
					if (!function_exists("__load_exchange_use_login_messages"))
					{
						function __load_exchange_use_login_messages()
						{
							$arEditFormLabel1 = array();

							$dbLang1 = CLanguage::GetList();
							while ($arLang1 = $dbLang1->Fetch())
							{
								$MESS = array();

								$fn1 = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/dav/lang/".preg_replace("/[^a-z0-9]/i", "", $arLang1["LID"])."/options.php";
								$fnDef1 = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/dav/lang/en/options.php";
								if (file_exists($fn1))
									include($fn1);
								elseif (file_exists($fnDef1))
									include($fnDef1);

								if (isset($MESS["DAV_EXCHANGE_USER_FIELD"]))
									$arEditFormLabel1[$arLang1["LID"]] = $MESS["DAV_EXCHANGE_USER_FIELD"];
								else
									$arEditFormLabel1[$arLang1["LID"]] = "Exchange mail box";
							}

							return $arEditFormLabel1;
						}
					}

					$arFields1 = array(
						"ENTITY_ID" => "USER",
						"FIELD_NAME" => "UF_BXDAVEX_MAILBOX",
						"USER_TYPE_ID" => "string",
						"SORT" => 100,
						"SHOW_FILTER" => "N",
						"EDIT_FORM_LABEL" => __load_exchange_use_login_messages(),
					);
					$obUserField1 = new CUserTypeEntity;
					$obUserField1->Add($arFields1);
				}
			}
		}
	}
	if(!empty($Update) && !empty($_REQUEST["back_url_settings"]))
		LocalRedirect($_REQUEST["back_url_settings"]);
	else
		LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($mid)."&lang=".urlencode(LANGUAGE_ID)."&back_url_settings=".urlencode($_REQUEST["back_url_settings"])."&".$tabControl->ActiveTabParam());
}

function ___dav_print_opt($arOption)
{
	$val = COption::GetOptionString("dav", $arOption[0], $arOption[2]);
	$type = $arOption[3];
	?>
	<tr>
		<td width="40%"><?
			if($type[0]=="checkbox")
				echo "<label for=\"".htmlspecialcharsbx($arOption[0])."\">".$arOption[1]."</label>";
			else
				echo $arOption[1];?>:</td>
		<td width="60%">
			<?if($type[0]=="checkbox"):?>
				<input type="checkbox" id="<?echo htmlspecialcharsbx($arOption[0])?>" name="<?echo htmlspecialcharsbx($arOption[0])?>" value="Y"<?if($val=="Y")echo" checked";?>>
			<?elseif($type[0]=="text"):?>
				<?if ($arOption[0] == "exchange_mailbox") {echo GetMessage("DAV_EXCHANGE_MAILBOX_NAME");}?>
				<input type="text" size="<?echo ($type[1] ?? null)?>" maxlength="255" value="<?echo htmlspecialcharsbx($val)?>" name="<?echo htmlspecialcharsbx($arOption[0])?>">
			<?elseif($type[0]=="password"):?>
				<input type="password" size="<?echo ($type[1] ?? null)?>" maxlength="255" value="<?echo htmlspecialcharsbx($val)?>" name="<?echo htmlspecialcharsbx($arOption[0])?>">
			<?elseif($type[0]=="textarea"):?>
				<textarea rows="<?echo ($type[1] ?? null)?>" cols="<?echo ($type[2] ?? null)?>" name="<?echo htmlspecialcharsbx($arOption[0])?>"><?echo htmlspecialcharsbx($val)?></textarea>
			<?elseif($type[0]=="selectbox"):?>
				<select name="<?echo htmlspecialcharsbx($arOption[0])?>">
					<?
					foreach ($type[1] as $key => $value)
					{
						?><option value="<?= $key ?>"<?= ($key == $val) ? " selected" : "" ?>><?= $value ?></option><?
					}
					?>
				</select>
			<?endif?>
		</td>
	</tr>
	<?
}

$tabControl->Begin();
?>
<form method="post" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=urlencode($mid)?>&amp;lang=<?echo LANGUAGE_ID?>" name="dav_settings">
<?
$tabControl->BeginNextTab();

foreach($arAllOptions2 as $arOption)
	___dav_print_opt($arOption);

$tabControl->BeginNextTab();

foreach($arAllOptions3 as $arOption)
	___dav_print_opt($arOption);

$tabControl->BeginNextTab();

foreach($arAllOptions4 as $arOption)
	___dav_print_opt($arOption);

$tabControl->Buttons();
?>
	<input type="submit" class="adm-btn-save" name="Update" value="<?=GetMessage("MAIN_SAVE")?>" title="<?=GetMessage("MAIN_OPT_SAVE_TITLE")?>">
	<input type="submit" name="Apply" value="<?=GetMessage("MAIN_OPT_APPLY")?>" title="<?=GetMessage("MAIN_OPT_APPLY_TITLE")?>">
	<?if($_REQUEST["back_url_settings"] <> ''):?>
		<input type="button" name="Cancel" value="<?=GetMessage("MAIN_OPT_CANCEL")?>" title="<?=GetMessage("MAIN_OPT_CANCEL_TITLE")?>" onclick="window.location='<?echo htmlspecialcharsbx(CUtil::addslashes($_REQUEST["back_url_settings"]))?>'">
		<input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($_REQUEST["back_url_settings"])?>">
	<?endif?>
	<input type="submit" name="RestoreDefaults" title="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" OnClick="return confirm('<?echo AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>')" value="<?echo GetMessage("MAIN_RESTORE_DEFAULTS")?>">
	<?=bitrix_sessid_post();?>
<?$tabControl->End();?>
</form>
