<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CUserTypeManager $USER_FIELD_MANAGER */
use \Bitrix\Main\Type;
if (!$USER->CanDoOperation("controller_member_view") || !CModule::IncludeModule("controller"))
{
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/controller/prolog.php");

IncludeModuleLangFile(__FILE__);

$message = false;
$strError = "";
$ID = intval($_REQUEST['ID']);
$ENTITY_ID = "CONTROLLER_MEMBER";

$aTabs = array(
	array(
		"DIV" => "edit1",
		"TAB" => GetMessage("CTRLR_MEM_EDIT_TAB1"),
		"ICON" => "controller_member_edit",
		"TITLE" => "",
	),
);

if ($ID > 0)
{
	$aTabs[] = array(
		"DIV" => "edit2",
		"TAB" => GetMessage("CTRLR_MEM_EDIT_COUNTER_TAB"),
		"ICON" => "controller_member_edit",
		"TITLE" => GetMessage("CTRLR_MEM_EDIT_COUNTER_TAB"),
	);
}

if (
	(count($USER_FIELD_MANAGER->GetUserFields($ENTITY_ID)) > 0)
	|| ($USER_FIELD_MANAGER->GetRights($ENTITY_ID) >= "W")
)
{
	$aTabs[] = $USER_FIELD_MANAGER->EditFormTab($ENTITY_ID);
}

$currentUserAuthGrant = false;
if ($ID > 0)
{
	$currentUserAuthGrantScopes = array();
	foreach (\Bitrix\Controller\AuthGrantTable::getControllerMemberScopes($ID, $USER->GetID(), $USER->GetUserGroupArray()) as $currentUserAuthGrant)
	{
		$currentUserAuthGrantScopes[$currentUserAuthGrant["SCOPE"]] = true;
	}
	if ($USER->CanDoOperation("controller_member_grant_auth") || $currentUserAuthGrantScopes)
	{
		$aTabs[] = array(
			"DIV" => "edit_auth",
			"TAB" => GetMessage("CTRLR_MEM_EDIT_AUTH_TAB"),
			"ICON" => "controller_member_edit",
			"TITLE" => GetMessage("CTRLR_MEM_EDIT_AUTH_TAB_TITLE"),
		);
	}
}

$tabControl = new CAdminTabControl("tabControl", $aTabs);

if ($ID <= 0 && strlen($_REQUEST['member_id']) > 0)
{
	$dbr_member = CControllerMember::GetByGuid($_REQUEST['member_id']);
	if ($ar_member = $dbr_member->Fetch())
		$ID = $ar_member["ID"];
	else
	{
		$e = new CApplicationException(GetMessage("CTRLR_MEM_EDIT_ERR"));
		$message = new CAdminMessage(GetMessage("CTRLR_MEM_EDIT_ERROR"), $e);
	}
}

$bUnregisterError = false;
if (
	$ID > 0
	&& $_REQUEST['unregister'] == 'Y'
	&& $USER->CanDoOperation("controller_member_disconnect")
	&& check_bitrix_sessid()
)
{
	$result = CControllerMember::UnRegister($ID);

	if ($_REQUEST["anywhere"] == 'Y' && $result === false)
	{
		CControllerMember::Update($ID, Array('DISCONNECTED' => 'Y'));
		$result = true;
	}

	if ($result === false)
	{
		if ($e = $APPLICATION->GetException())
			$message = new CAdminMessage(GetMessage("CTRLR_MEM_EDIT_ERR2"), $e);
		$bUnregisterError = true;
	}
	else
	{
		if (strlen($_REQUEST['back_url']) > 0)
			LocalRedirect($_REQUEST['back_url']);
		else
			LocalRedirect($APPLICATION->GetCurPage()."?lang=".LANG."&ID=".$ID."&".$tabControl->ActiveTabParam());
	}
}

$sRegistrationMode = "";
if ($ID <= 0)
{
	if (strlen($_REQUEST['TICKET_ID']) > 0 && strlen($_REQUEST['SECRET_ID']) > 0)
		$sRegistrationMode = "ticket";
	else
		$sRegistrationMode = "password";
}

$bRegistrationByTicketError = false;

if (
	$_SERVER["REQUEST_METHOD"] == "POST"
	&& !$message
	&& $_REQUEST['unregister'] != 'Y'
	&& (strlen($_POST['save']) > 0 || strlen($_POST['save_ext']) > 0 || strlen($_POST['apply']) > 0)
	&& (
		$USER->CanDoOperation("controller_member_edit")
		|| $USER->CanDoOperation("controller_member_add")
	)
)
{
	if (!check_bitrix_sessid())
	{
		$message = new CAdminMessage(GetMessage("CTRLR_MEM_EDIT_ERR3"));
	}
	else
	{
		$arFields = Array(
			"ACTIVE" => $_REQUEST["ACTIVE"],
			"NAME" => $_REQUEST["NAME"],
			"URL" => $_REQUEST["PROTOCOL"].$_REQUEST["URL"],
			"CONTACT_PERSON" => $_REQUEST["CONTACT_PERSON"],
			"EMAIL" => $_REQUEST["EMAIL"],
			"DATE_ACTIVE_FROM" => $_REQUEST["DATE_ACTIVE_FROM"],
			"DATE_ACTIVE_TO" => $_REQUEST["DATE_ACTIVE_TO"],
			"MEMBER_ID" => $_REQUEST["MEMBER_ID"],
			"NOTES" => $_REQUEST["NOTES"],
			"CONTROLLER_GROUP_ID" => $_REQUEST["CONTROLLER_GROUP_ID"],
		);

		if (COption::GetOptionString("controller", "show_hostname") == "Y")
			$arFields["HOSTNAME"] = $_REQUEST["HOSTNAME"];

		if (ControllerIsSharedMode())
			$arFields["SHARED_KERNEL"] = $_REQUEST["SHARED_KERNEL"];

		$USER_FIELD_MANAGER->EditFormAddFields($ENTITY_ID, $arFields);

		if ($ID > 0)
		{
			if ($USER->CanDoOperation("controller_member_edit"))
				$res = CControllerMember::Update($ID, $arFields);
			else
				$res = false;

			if (
				isset($_REQUEST['AUTH'])
				&& is_array($_REQUEST['AUTH'])
				&& $_REQUEST['AUTH']['GRANTEE_USER_ID'] > 0
				&& $USER->CanDoOperation('controller_member_grant_auth')
				&& array_key_exists($_REQUEST['AUTH']['GRANTEE_USER_ID'], \Bitrix\Controller\AuthGrantTable::getGranteeUserList($USER->GetID()))
			)
			{
				\Bitrix\Controller\AuthGrantTable::add(array(
					"TIMESTAMP_X" => new Type\DateTime(),
					"GRANTED_BY" => $USER->GetID(),
					"CONTROLLER_MEMBER_ID" => $ID,
					"GRANTEE_USER_ID" => $_REQUEST['AUTH']['GRANTEE_USER_ID'],
					"ACTIVE" => "Y",
					"SCOPE" => $_REQUEST['AUTH']['SCOPE'],
					"DATE_START" => Type\DateTime::isCorrect($_REQUEST['AUTH']['DATE_START'])? Type\DateTime::createFromUserTime($_REQUEST['AUTH']['DATE_START']): false,
					"DATE_END" => Type\DateTime::isCorrect($_REQUEST['AUTH']['DATE_START'])? Type\DateTime::createFromUserTime($_REQUEST['AUTH']['DATE_END']): false,
				));
			}
			if (
				isset($_REQUEST['AUTH_DELETE'])
				&& is_array($_REQUEST['AUTH_DELETE'])
				&& $USER->CanDoOperation('controller_member_grant_auth')
			)
			{
				foreach ($_REQUEST['AUTH_DELETE'] as $id)
				{
					\Bitrix\Controller\AuthGrantTable::update($id, array("ACTIVE" => "N"));
				}
			}
		}
		elseif ($_REQUEST["reconnect_id"] > 0)
		{
			$arFields["SECRET_ID"] = $_REQUEST["SECRET_ID"];
			$arFields["DISCONNECTED"] = "N";
			if (strlen($_REQUEST["ADMIN_LOGIN"]) < 0 || strlen($_REQUEST["ADMIN_PASSWORD"]) <= 0)
			{
				$e = new CApplicationException(GetMessage("CTRLR_MEM_EDIT_ERR5"));
				$message = new CAdminMessage(GetMessage("CTRLR_MEM_EDIT_ERR4"), $e);
			}
			elseif (!($ID = CControllerMember::ReconnectMemberByPassword($_REQUEST["reconnect_id"], $arFields, $_REQUEST["ADMIN_LOGIN"], $_REQUEST["ADMIN_PASSWORD"])))
			{
				if ($e = $APPLICATION->GetException())
					$message = new CAdminMessage(GetMessage("CTRLR_MEM_EDIT_ERR4"), $e);
				$bRegistrationByTicketError = true;
			}
			$res = ($ID > 0);
		}
		else
		{
			if ($sRegistrationMode == "ticket")
			{
				$arFields["SECRET_ID"] = $_REQUEST["SECRET_ID"];
				if (!($ID = CControllerMember::RegisterMemberByTicket($arFields, $_REQUEST["TICKET_ID"], $_REQUEST["PROTOCOL"].$_REQUEST["URL"])))
				{
					if ($e = $APPLICATION->GetException())
						$message = new CAdminMessage(GetMessage("CTRLR_MEM_EDIT_ERR4"), $e);
					$bRegistrationByTicketError = true;
				}
			}
			elseif ($sRegistrationMode == "password")
			{
				if (strlen($_REQUEST["ADMIN_LOGIN"]) < 0 || strlen($_REQUEST["ADMIN_PASSWORD"]) <= 0)
				{
					$e = new CApplicationException(GetMessage("CTRLR_MEM_EDIT_ERR5"));
					$message = new CAdminMessage(GetMessage("CTRLR_MEM_EDIT_ERR4"), $e);
				}
				elseif (!($ID = CControllerMember::RegisterMemberByPassword($arFields, $_REQUEST["ADMIN_LOGIN"], $_REQUEST["ADMIN_PASSWORD"])))
				{
					if ($e = $APPLICATION->GetException())
						$message = new CAdminMessage(GetMessage("CTRLR_MEM_EDIT_ERR4"), $e);
					$bRegistrationByTicketError = true;
				}
			}
			else
			{
				$ID = CControllerMember::Add($arFields);
			}

			$res = ($ID > 0);
		}

		if (!$res)
		{
			if ($e = $APPLICATION->GetException())
				$message = new CAdminMessage(GetMessage("CTRLR_MEM_EDIT_ERR6"), $e);
		}
		else
		{
			if (strlen($save) > 0)
			{
				if ($back_url == '')
					LocalRedirect("controller_member_admin.php?lang=".LANG);
				else
					LocalRedirect($back_url);
			}
			else
				LocalRedirect($APPLICATION->GetCurPage()."?lang=".LANG."&ID=".$ID."&".$tabControl->ActiveTabParam());
		}
	}
}

ClearVars();
$str_ACTIVE = "Y";
if (strlen($_REQUEST['MEMBER_ID']) > 0)
	$str_MEMBER_ID = htmlspecialcharsbx(substr($_REQUEST['MEMBER_ID'], 0, 32));
else
	$str_MEMBER_ID = "m".\Bitrix\Main\Security\Random::getString(31);

$mb = CControllerMember::GetByID($_REQUEST["reconnect_id"] > 0? $_REQUEST["reconnect_id"]: $ID);
if (!$USER->CanDoOperation("controller_member_view") || (!$mb->ExtractFields("str_")))
	$ID = 0;

if (
	$_REQUEST["countersupdate"] == "Y"
	&& $ID > 0
	&& check_bitrix_sessid()
	&& $USER->CanDoOperation("controller_member_counters_update")
)
{
	$result = array();
	if (CControllerMember::UpdateCounters($ID))
	{
		$rsMember = CControllerMember::GetByID($ID);
		$arMember = $rsMember->Fetch();
		$mb = CControllerGroup::GetByID($arMember["CONTROLLER_GROUP_ID"]);
		$arGroup = $mb->Fetch();

		$result["COUNTERS_UPDATED"] = $arMember["COUNTERS_UPDATED"];
		if ($arGroup["CHECK_COUNTER_FREE_SPACE"] == "Y")
			$result["COUNTER_FREE_SPACE"] = $arMember["COUNTER_FREE_SPACE"];
		if ($arGroup["CHECK_COUNTER_SITES"] == "Y")
			$result["COUNTER_SITES"] = $arMember["COUNTER_SITES"];
		if ($arGroup["CHECK_COUNTER_USERS"] == "Y")
			$result["COUNTER_USERS"] = $arMember["COUNTER_USERS"];
		if ($arGroup["CHECK_COUNTER_LAST_AUTH"] == "Y")
			$result["COUNTER_LAST_AUTH"] = $arMember["COUNTER_LAST_AUTH"];

		$rsCounters = CControllerCounter::GetMemberValues($ID);
		while ($arCounter = $rsCounters->Fetch())
		{
			$result["COUNTER_".$arCounter["ID"]] = $arCounter["DISPLAY_VALUE"];
		}
	}
	else
	{
		$e = $APPLICATION->GetException();
		if ($e)
			$result["error"] = GetMessage("CTRLR_MEM_EDIT_COUNTER_ERR")." ".$e->GetString();
		else
			$result["error"] = GetMessage("CTRLR_MEM_EDIT_COUNTER_ERR")." unknown";
	}
	echo CUtil::PhpToJSObject($result, true);
	die();
}

if (($message && !$bUnregisterError) || ($ID == 0 && $_REQUEST["reconnect_id"] == 0))
{
	$URL = $PROTOCOL.$URL;
	$DB->InitTableVarsForEdit("b_controller_member", "", "str_");
}

$sDocTitle = ($ID > 0)? preg_replace("'#ID#'i", $ID, GetMessage("CTRLR_MEM_EDIT_TITLE")): GetMessage("CTRLR_MEM_EDIT_TITLE_NEW");
$APPLICATION->SetTitle($sDocTitle);

/***************************************************************************
 * HTML form
 ****************************************************************************/

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
CJSCore::Init(array("ajax"));
$aMenu = array(
	array(
		"ICON" => "btn_list",
		"TEXT" => GetMessage("CTRLR_MEM_EDIT_TOOLBAR_BACK_TEXT"),
		"TITLE" => GetMessage("CTRLR_MEM_EDIT_TOOLBAR_BACK"),
		"LINK" => "controller_member_admin.php?lang=".LANG,
	),
);

if ($ID > 0)
{
	if ($USER->CanDoOperation("controller_member_history_view"))
	{
		$aMenu[] = array(
			"TEXT" => GetMessage("CTRLR_MEM_EDIT_TOOLBAR_HISTORY_TEXT"),
			"TITLE" => GetMessage("CTRLR_MEM_EDIT_TOOLBAR_HISTORY"),
			"LINK" => "controller_member_history.php?find_id=".$ID."&set_filter=Y&lang=".LANG,
		);
	}

	if ($USER->CanDoOperation("controller_member_add"))
	{
		$aMenu[] = array(
			"ICON" => "btn_new",
			"TEXT" => GetMessage("CTRLR_MEM_EDIT_TOOLBAR_NEW_TEXT"),
			"TITLE" => GetMessage("CTRLR_MEM_EDIT_TOOLBAR_NEW"),
			"LINK" => "controller_member_edit.php?lang=".LANG,
		);
	}

	if ($str_DISCONNECTED != "Y")
	{
		if ($USER->CanDoOperation("controller_member_disconnect"))
		{
			$aMenu[] = array(
				"TEXT" => GetMessage("CTRLR_MEM_EDIT_TOOLBAR_DISCN_TEXT"),
				"TITLE" => GetMessage("CTRLR_MEM_EDIT_TOOLBAR_DISCN"),
				"LINK" => "javascript:if(confirm('".GetMessage("CTRLR_MEM_EDIT_TOOLBAR_DISCN_CONFIRM")."'))window.location='controller_member_edit.php?unregister=Y&ID=".$ID."&lang=".LANG."&".bitrix_sessid_get()."';",
			);
		}
	}

	if ($USER->CanDoOperation("controller_member_delete"))
	{
		$aMenu[] = array(
			"TEXT" => GetMessage("CTRLR_MEM_EDIT_TOOLBAR_DELETE_TEXT"),
			"TITLE" => GetMessage("CTRLR_MEM_EDIT_TOOLBAR_DELETE"),
			"ICON" => "btn_delete",
			"LINK" => "javascript:if(confirm('".GetMessage("CTRLR_MEM_EDIT_TOOLBAR_DELETE_CONFIRM")."'))window.location='controller_member_admin.php?action=delete&ID=".$ID."&lang=".LANG."&".bitrix_sessid_get()."';",
		);
	}
}

$context = new CAdminContextMenu($aMenu);
$context->Show();

if ($message)
	echo $message->Show();

if ($bUnregisterError):
	?>
	<input type="button" value="<? echo GetMessage("CTRLR_MEM_EDIT_MARK_DISCN") ?>" onclick="window.location='controller_member_edit.php?unregister=Y&anywhere=Y&ID=<?=$ID?>&lang=<?=LANG?>&<?=bitrix_sessid_get()?>'">
	<?
endif;

if ($_REQUEST['act'] == 'unregister' && $ID > 0):
	?>
	<script>
		setTimeout('_TryDelete()', 1);
		function _TryDelete()
		{
			if (confirm('<?=CUtil::JSEscape(GetMessage("CTRLR_MEM_EDIT_MARK_DISCN_CONFIRM", array("#MEMBER_NAME#" => $str_NAME." (".$str_URL.")")))?>'))
			{
				document.getElementById('unregister').value = 'Y';
				document.getElementById('form1').submit();
			}
		}
	</script>
<? endif ?>

<?
if (method_exists($USER_FIELD_MANAGER, 'showscript'))
	echo $USER_FIELD_MANAGER->ShowScript();
?>

<form method="POST" action="<? echo $APPLICATION->GetCurPage() ?>?lang=<?=LANG?><? echo $ID > 0? '&amp;ID='.$ID: '' ?><? echo $_REQUEST["reconnect_id"] > 0? '&amp;reconnect_id='.intval($_REQUEST["reconnect_id"]): '' ?>" name="form1" id="form1">
	<?=bitrix_sessid_post()?>
	<? echo GetFilterHiddens("find_"); ?>

	<? if ($ID == 0 && (strlen($_REQUEST['SECRET_ID']) > 0 || $_REQUEST["reconnect_id"] > 0)): ?>
		<input type="hidden" name="SECRET_ID" value="<?=$str_SECRET_ID?>">
	<? endif ?>

	<? if ($ID == 0 && strlen($_REQUEST['TICKET_ID']) > 0): ?>
		<input type="hidden" name="TICKET_ID" value="<?=htmlspecialcharsbx($_REQUEST["TICKET_ID"])?>">
	<? endif ?>
	<? if ($back_url != ''): ?>
		<input type="hidden" name="back_url" value="<?=htmlspecialcharsbx($back_url)?>">
	<? endif ?>

	<input type="hidden" id="unregister" name="unregister" value="">
	<?

	$tabControl->Begin();
	$tabControl->BeginNextTab();
	?>
	<? if ($ID > 0): ?>
		<tr>
			<td align="right" width="40%">ID:</td>
			<td><? echo $str_ID ?></td>
		</tr>
		<? if ($str_DISCONNECTED == 'Y'): ?>
			<tr>
				<td align="right" width="40%"><? echo GetMessage("CTRLR_MEM_EDIT_DISCN") ?></td>
				<td><font color="red"><b><? echo GetMessage("CTRLR_MEM_EDIT_DISCN_YES") ?></b></font>
				</td>
			</tr>
		<? endif ?>
		<? if ($str_DISCONNECTED == 'I'): ?>
			<tr>
				<td align="right" width="40%"><? echo GetMessage("CTRLR_MEM_EDIT_DISCN") ?></td>
				<td><? echo GetMessage("CTRLR_MEM_EDIT_DISCN_INIT") ?></td>
			</tr>
		<? endif ?>
	<? endif ?>
	<? if (strlen($str_DATE_CREATE) > 0): ?>
		<tr>
			<td align="right" width="40%"><? echo GetMessage("CTRLR_MEM_EDIT_CREATED") ?></td>
			<td><? echo $str_DATE_CREATE, " ", $str_CREATED_BY_USER ?></td>
		</tr>
	<? endif; ?>
	<? if (strlen($str_TIMESTAMP_X) > 0): ?>
		<tr>
			<td align="right" width="40%"><? echo GetMessage("CTRLR_MEM_EDIT_MODIFIED") ?></td>
			<td><? echo $str_TIMESTAMP_X, " ", $str_MODIFIED_BY_USER ?></td>
		</tr>
	<? endif; ?>
	<tr class="adm-detail-required-field">
		<td align="right" width="40%"><? echo GetMessage("CTRLR_MEM_EDIT_NAME") ?></td>
		<td><input type="text" name="NAME" size="53" maxlength="255" value="<?=$str_NAME?>"></td>
	</tr>
	<tr class="adm-detail-required-field">
		<td align="right" width="40%"><? echo GetMessage("CTRLR_MEM_EDIT_UID") ?></td>
		<td>
			<input type="text" name="MEMBER_ID" size="53" maxlength="255" <? if ($ID > 0) echo "readonly"; ?> value="<?=$str_MEMBER_ID?>">
		</td>
	</tr>
	<tr class="adm-detail-required-field">
		<td align="right" width="40%"><? echo GetMessage("CTRLR_MEM_EDIT_URL") ?></td>
		<td>
			<select name="PROTOCOL">
				<option value="http://">http://</option>
				<option value="https://"<? if (strtolower(substr($str_URL, 0, 8)) == "https://") echo " selected" ?>>
					https://
				</option>
			</select>
			<?
			if (strpos($str_URL, "://") > 0)
				$str_URL = substr($str_URL, strpos($str_URL, "://") + 3);
			?>
			<input type="text" name="URL" size="42" maxlength="255" value="<?=$str_URL?>">
		</td>
	</tr>
	<? if (COption::GetOptionString("controller", "show_hostname") == "Y"): ?>
		<tr>
			<td align="right" width="40%"><? echo GetMessage("CTRLR_MEM_EDIT_HOSTNAME") ?></td>
			<td><input type="text" name="HOSTNAME" size=43 maxlength="255" value="<?=$str_HOSTNAME?>"></td>
		</tr>
	<? endif ?>
	<tr class="adm-detail-required-field">
		<td align="right" width="40%"><? echo GetMessage("CTRLR_MEM_EDIT_GROUP") ?></td>
		<td><select name="CONTROLLER_GROUP_ID">
				<?
				$iTrialPeriod = 0;
				$dbr_group = CControllerGroup::GetList(Array("SORT" => "ASC", "NAME" => "ASC", "ID" => "ASC"));
				while ($ar_group = $dbr_group->GetNext()):
					if ($str_CONTROLLER_GROUP_ID == $ar_group["ID"] && $ar_group["TRIAL_PERIOD"] > 0)
						$iTrialPeriod = $ar_group["TRIAL_PERIOD"];
					?>
					<option value="<?=$ar_group["ID"]?>"<? if ($str_CONTROLLER_GROUP_ID == $ar_group["ID"]) echo " selected" ?>><?=$ar_group["NAME"]?></option>
				<? endwhile; ?>
			</select>
		</td>
	</tr>
	<? if ($sRegistrationMode == "password"): ?>
		<tr class="adm-detail-required-field">
			<td align="right" width="40%"><? echo GetMessage("CTRLR_MEM_EDIT_MEMB_LOGIN") ?></td>
			<td>
				<input type="text" name="ADMIN_LOGIN" size="53" maxlength="255" value="<?=htmlspecialcharsbx($ADMIN_LOGIN)?>">
			</td>
		</tr>
		<tr class="adm-detail-required-field">
			<td align="right" width="40%"><? echo GetMessage("CTRLR_MEM_EDIT_MEMB_PASSW") ?></td>
			<td>
				<input type="password" name="ADMIN_PASSWORD" size="53" maxlength="255" value="<?=htmlspecialcharsbx($ADMIN_PASSWORD)?>">
			</td>
		</tr>
	<? endif ?>
	<? if (ControllerIsSharedMode()): ?>
		<tr>
			<td align="right" width="40%"><? echo GetMessage("CTRLR_MEM_EDIT_SHARED_KERNEL") ?></td>
			<td>
				<input type="checkbox" name="SHARED_KERNEL" value="Y"<? if ($str_SHARED_KERNEL == "Y") echo " checked" ?>>
			</td>
		</tr>
	<? endif ?>
	<tr class="heading">
		<td colspan="2"><? echo GetMessage("CTRLR_MEM_EDIT_AVAIL") ?></td>
	</tr>
	<tr>
		<td align="right" width="40%"><? echo GetMessage("CTRLR_MEM_EDIT_AVAIL_CUR") ?></td>
		<td>
			<? if ($str_SITE_ACTIVE == 'N'): ?>
				<? echo GetMessage("CTRLR_MEM_EDIT_AVAIL_CLOSED") ?>
			<? else: ?>
				<? echo GetMessage("CTRLR_MEM_EDIT_AVAIL_OPENED") ?>
			<? endif ?>
		</td>
	</tr>
	<? if ($ID > 0 && $str_DISCONNECTED == "N" && $iTrialPeriod > 0 && $str_IN_GROUP_FROM != ''): ?>
		<tr>
			<td align="right" width="40%"><? echo GetMessage("CTRLR_MEM_EDIT_AVAIL_TRIAL") ?></td>
			<td>
				<?
				$tFrom = MakeTimeStamp($str_IN_GROUP_FROM, FORMAT_DATE);
				$tTo = $tFrom + $iTrialPeriod * 24 * 60 * 60 - 1;
				$iDays = (($tTo - time()) / 60 / 60 / 24);
				if ($iDays < 0)
					$iDays = $iDays - 0.99999;
				$iDays = intval($iDays);
				?>
				<? if ($iDays > 0): ?>
					<? echo GetMessage("CTRLR_MEM_EDIT_AVAIL_TRIAL_1") ?> <?=$iDays?><? echo GetMessage("CTRLR_MEM_EDIT_AVAIL_TRIAL_1_D") ?>
				<? elseif ($iDays == 0): ?>
					<? echo GetMessage("CTRLR_MEM_EDIT_AVAIL_TRIAL_2") ?>
				<? else: ?>
					<? echo GetMessage("CTRLR_MEM_EDIT_AVAIL_TRIAL_3") ?> <?=(-$iDays)?><? echo GetMessage("CTRLR_MEM_EDIT_AVAIL_TRIAL_3_D") ?>
				<? endif ?>
				(<?=ConvertTimeStamp($tTo)?>)
			</td>
		</tr>
	<? endif; ?>

	<tr>
		<td align="right" width="40%">
			<label for="ACTIVEX"><? echo GetMessage("CTRLR_MEM_EDIT_ACTIVE") ?></label></td>
		<td>
			<script>
				function __ActiveOnClick(ob)
				{
					if (!ob.checked)
						return confirm("<?echo GetMessage("CTRLR_MEM_EDIT_ACTIVE_CONFIRM")?>");
				}
			</script>
			<input type="checkbox" name="ACTIVE" id="ACTIVE" value="Y"<? if ($str_ACTIVE == "Y") echo " checked" ?> onclick="return __ActiveOnClick(this);">
		</td>
	</tr>
	<tr>
		<td align="right" width="40%"><? echo GetMessage("CTRLR_MEM_EDIT_ACTIVE_PERIOD") ?></td>
		<td><? echo CalendarPeriod("DATE_ACTIVE_FROM", $str_DATE_ACTIVE_FROM, "DATE_ACTIVE_TO", $str_DATE_ACTIVE_TO, "form1") ?></td>
	</tr>
	<tr class="heading">
		<td colspan="2"><? echo GetMessage("CTRLR_MEM_EDIT_ADD") ?></td>
	</tr>
	<tr>
		<td align="right" width="40%"><? echo GetMessage("CTRLR_MEM_EDIT_CONTACT_PERSON") ?></td>
		<td><input type="text" name="CONTACT_PERSON" size="53" maxlength="255" value="<?=$str_CONTACT_PERSON?>">
		</td>
	</tr>
	<tr>
		<td align="right" width="40%"><? echo GetMessage("CTRLR_MEM_EDIT_EMAIL") ?></td>
		<td><input type="text" name="EMAIL" size="53" maxlength="255" value="<?=$str_EMAIL?>"></td>
	</tr>
	<tr class="adm-detail-valign-top">
		<td align="right" width="40%"><? echo GetMessage("CTRLR_MEM_EDIT_DESCR") ?></td>
		<td><textarea name="NOTES" cols="40" rows="5"><? echo $str_NOTES ?></textarea>
		</td>
	</tr>
	<? if ($ID > 0): ?>
		<? $tabControl->BeginNextTab(); ?>
		<script>
			function UpdateCounters()
			{
				BX.showWait();
				BX.ajax.loadJSON(
					'/bitrix/admin/controller_member_edit.php?lang=<?=LANGUAGE_ID?>&ID=<?=$ID?>',
					{
						countersupdate: 'Y',
						sessid: BX.bitrix_sessid()
					},
					function (result)
					{
						BX.closeWait();

						for (var id in result)
						{
							if (result.hasOwnProperty(id))
							{
								var textControl = BX(id);
								if (textControl)
								{
									textControl.innerHTML = result[id];
								}
							}
						}

						if (result.hasOwnProperty('error'))
						{
							alert(result['error']);
						}
					}
				);
			}
		</script>
		<tr class="adm-detail-valign-top">
			<td align="right" width="40%"><? echo GetMessage("CTRLR_MEM_EDIT_COUNTERS") ?></td>
			<td><span id="COUNTERS_UPDATED"><? echo $str_COUNTERS_UPDATED ?></span>
				<?if ($USER->CanDoOperation("controller_member_counters_update")):?>
				[<a href="javascript:void(0)" onclick="UpdateCounters(); return false;"><? echo GetMessage("CTRLR_MEM_EDIT_COUNTERS_REFRESH") ?></a>]
				<?endif?>
			</td>
		</tr>
	<?
	$mb = CControllerGroup::GetByID($str_CONTROLLER_GROUP_ID);
	$arGroup = $mb->Fetch();
	if ($arGroup["CHECK_COUNTER_FREE_SPACE"] == "Y"): ?>
		<tr>
			<td align="right" width="40%"><? echo GetMessage("CTRLR_MEM_EDIT_COUNTERS_FREE") ?></td>
			<td>
				<span id="COUNTER_FREE_SPACE"><? echo $str_COUNTER_FREE_SPACE ?></span><? echo GetMessage("CTRLR_MEM_EDIT_COUNTERS_FREE_Kb") ?>
			</td>
		</tr>
	<? endif; ?>
	<? if ($arGroup["CHECK_COUNTER_SITES"] == "Y"): ?>
		<tr>
			<td align="right" width="40%"><? echo GetMessage("CTRLR_MEM_EDIT_COUNTERS_SITES") ?></td>
			<td><span id="COUNTER_SITES"><? echo $str_COUNTER_SITES ?></span></td>
		</tr>
	<? endif; ?>
	<? if ($arGroup["CHECK_COUNTER_USERS"] == "Y"): ?>
		<tr>
			<td align="right" width="40%"><? echo GetMessage("CTRLR_MEM_EDIT_COUNTERS_USERS") ?></td>
			<td><span id="COUNTER_USERS"><? echo $str_COUNTER_USERS ?></span></td>
		</tr>
	<? endif; ?>
	<? if ($arGroup["CHECK_COUNTER_LAST_AUTH"] == "Y"): ?>
		<tr>
			<td align="right" width="40%"><? echo GetMessage("CTRLR_MEM_EDIT_COUNTERS_LAST_AU") ?></td>
			<td><span id="COUNTER_LAST_AUTH"><? echo $str_COUNTER_LAST_AUTH ?></span></td>
		</tr>
	<? endif; ?>
	<?
	$rsCounters = CControllerCounter::GetMemberValues($ID);
	while ($arCounter = $rsCounters->Fetch())
	{
	?>
		<tr>
			<td align="right" width="40%"><? echo htmlspecialcharsEx($arCounter["NAME"]) ?>:</td>
			<td>
				<span id="COUNTER_<? echo $arCounter["ID"] ?>"><? echo htmlspecialcharsEx($arCounter["DISPLAY_VALUE"]) ?></span>
			</td>
		</tr>
		<?
	}
		?>
	<?endif;

	if (
		(count($USER_FIELD_MANAGER->GetUserFields($ENTITY_ID)) > 0) ||
		($USER_FIELD_MANAGER->GetRights($ENTITY_ID) >= "W")
	)
	{
		$tabControl->BeginNextTab();
		if ($USER_FIELD_MANAGER->GetRights($ENTITY_ID) >= "W")
		{
			echo "<tr><td colspan=\"2\" align=\"left\"><a href=\"/bitrix/admin/userfield_edit.php?lang=".LANG."&ENTITY_ID=".urlencode($ENTITY_ID)."&back_url=".urlencode($APPLICATION->GetCurPageParam("", array("bxpublic"))."&tabControl_active_tab=user_fields_tab")."\">".GetMessage("USER_TYPE_EDIT_TAB_HREF")."</a></td></tr>";
		}

		$arUserFields = $USER_FIELD_MANAGER->GetUserFields($ENTITY_ID, ($_REQUEST["reconnect_id"] > 0)? $_REQUEST["reconnect_id"]: $ID, LANGUAGE_ID);
		if (count($arUserFields) > 0)
		{
			foreach ($arUserFields as $FIELD_NAME => $arUserField)
			{
				$arUserField["VALUE_ID"] = intval($ID);
				echo $USER_FIELD_MANAGER->GetEditFormHTML(is_object($message), $GLOBALS[$FIELD_NAME], $arUserField);
			}
		}
	}

	if (
		($ID > 0 && $USER->CanDoOperation("controller_member_grant_auth"))
		|| ($ID > 0 && $currentUserAuthGrantScopes)
	)
	{
		$tabControl->BeginNextTab();

		if ($ID > 0 && $currentUserAuthGrantScopes)
		{
			if (array_key_exists("user", $currentUserAuthGrantScopes) || array_key_exists("admin", $currentUserAuthGrantScopes))
			{
				?>
				<tr>
					<td colspan="2"><a href="controller_goto.php?lang=<? LANGUAGE_ID ?>&amp;member=<? echo $ID ?>"><? echo GetMessage("CTRLR_MEM_EDIT_AUTH_GO") ?></a>
					</td>
				</tr>
				<?
			}
			if (array_key_exists("php", $currentUserAuthGrantScopes))
			{
				?>
				<tr>
					<td colspan="2"><a href="controller_run_command.php?lang=<? LANGUAGE_ID ?>&amp;member=<? echo $ID ?>"><? echo GetMessage("CTRLR_MEM_EDIT_AUTH_RUN_COMMAND") ?></a>
					</td>
				</tr>
				<?
			}
		}

		if ($ID > 0 && $USER->CanDoOperation("controller_member_grant_auth"))
		{
			?>
			<tr>
				<td colspan="2"><a href="controller_auth_log.php?lang=<?LANGUAGE_ID?>&amp;find_to_controller_member=<?echo $ID?>&amp;set_filter=Y"><? echo GetMessage("CTRLR_MEM_EDIT_AUTH_LOG") ?></a></td>
			</tr>
			<tr class="heading">
				<td colspan="2"><? echo GetMessage("CTRLR_MEM_EDIT_AUTH_ADD") ?></td>
			</tr>
			<tr>
				<td align="right" width="40%"><? echo GetMessage("CTRLR_MEM_EDIT_AUTH_GRANTEE") ?></td>
				<td><select name="AUTH[GRANTEE_USER_ID]">
						<option value=""><?=GetMessage("MAIN_NONE")?></option>
						<?
						foreach (\Bitrix\Controller\AuthGrantTable::getGranteeUserList($USER->GetID()) as $userId => $userTitle):
							?>
							<option value="<?=$userId?>"><?=htmlspecialcharsEx($userTitle)?></option>
						<? endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<td align="right" width="40%"><? echo GetMessage("CTRLR_MEM_EDIT_AUTH_SCOPE") ?></td>
				<td><select name="AUTH[SCOPE]">
						<option value="user"><?=GetMessage("CTRLR_MEM_EDIT_AUTH_SCOPE_USER")?></option>
						<option value="admin"><?=GetMessage("CTRLR_MEM_EDIT_AUTH_SCOPE_ADMIN")?></option>
						<option value="php"><?=GetMessage("CTRLR_MEM_EDIT_AUTH_SCOPE_PHP")?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td align="right" width="40%"><? echo GetMessage("CTRLR_MEM_EDIT_AUTH_TIME_INTERVAL") ?></td>
				<td>
					<?
					echo CAdminCalendar::CalendarPeriod("AUTH[DATE_START]", "AUTH[DATE_END]", '', '', "Y", 10, true);
					?>
				</td>
			</tr>
			<?
			$grantList = \Bitrix\Controller\AuthGrantTable::getList(array(
				"select" => array(
					"ID",
					"TIMESTAMP_X",
					"GRANTED_BY",
					"GRANTED_NAME",
					"GRANTEE_USER_ID",
					"GRANTEE_USER_NAME",
					"GRANTEE_GROUP_ID",
					"GRANTEE_GROUP_NAME",
					"SCOPE",
					"DATE_START",
					"DATE_END",
					"NOTE",
				),
				"filter" => array(
					"=CONTROLLER_MEMBER_ID" => $ID,
					"=ACTIVE" => "Y",
					array(
						"LOGIC" => "OR",
						"=DATE_END" => false,
						">=DATE_END" => new \Bitrix\Main\Type\DateTime(),
					),
				),
				"order" => array("ID" => "desc"),
			));
			$grant = $grantList->fetch();
			if ($grant)
			{
				?>
				<tr class="heading">
					<td colspan="2"><? echo GetMessage("CTRLR_MEM_EDIT_AUTH_ACTIVE") ?></td>
				</tr>
				<tr>
					<td colspan="2" class="adm-detail-valign-top" align="center">
						<table class="internal">
							<tr class="heading">
								<td><?echo GetMessage("CTRLR_MEM_EDIT_AUTH_TIMESTAMP_X")?></td>
								<td><?echo GetMessage("CTRLR_MEM_EDIT_AUTH_GRANTED_BY")?></td>
								<td><?echo GetMessage("CTRLR_MEM_EDIT_AUTH_GRANTEE")?></td>
								<td><?echo GetMessage("CTRLR_MEM_EDIT_AUTH_SCOPE")?></td>
								<td><?echo GetMessage("CTRLR_MEM_EDIT_AUTH_DATE_START")?></td>
								<td><?echo GetMessage("CTRLR_MEM_EDIT_AUTH_DATE_END")?></td>
								<td><?echo GetMessage("CTRLR_MEM_EDIT_AUTH_DELETE")?></td>
								<td>&nbsp;</td>
							</tr>
					<?
					do
					{
						?>
						<tr>
							<td><?echo htmlspecialcharsEx($grant["TIMESTAMP_X"])?></td>
							<td><a href="<?echo htmlspecialcharsbx('user_edit.php?lang='.LANGUAGE_ID.'&ID='.$grant['GRANTED_BY'])?>"><?echo htmlspecialcharsEx($grant["GRANTED_NAME"])?></a></td>
							<?if ($grant['GRANTEE_USER_ID']):?>
							<td><a href="<?echo htmlspecialcharsbx('user_edit.php?lang='.LANGUAGE_ID.'&ID='.$grant['GRANTEE_USER_ID'])?>"><?echo htmlspecialcharsEx($grant["GRANTEE_USER_NAME"])?></a></td>
							<?else:?>
							<td><a href="<?echo htmlspecialcharsbx('group_edit.php?lang='.LANGUAGE_ID.'&ID='.$grant['GRANTEE_GROUP_ID'])?>"><?echo htmlspecialcharsEx($grant["GRANTEE_GROUP_NAME"])?></a></td>
							<?endif;?>
							<td><?echo htmlspecialcharsEx($grant["SCOPE"])?></td>
							<td><?echo htmlspecialcharsEx($grant["DATE_START"])?></td>
							<td><?echo htmlspecialcharsEx($grant["DATE_END"])?></td>
							<td align="center"><input type="checkbox" name="AUTH_DELETE[]" value="<?echo htmlspecialcharsbx($grant["ID"])?>"></td>
							<td><?echo htmlspecialcharsEx($grant["NOTE"])?></td>
						</tr>
						<?
					} while ($grant = $grantList->fetch());
					?>
						</table>
					</td>
				</tr>
				<?
			}
		}
	}

	$tabControl->EndTab();
	$tabControl->Buttons(array(
		"back_url" => ($back_url != ''? $back_url: "controller_member_admin.php?lang=".LANGUAGE_ID),
		"disabled" => !($ID > 0 && $USER->CanDoOperation("controller_member_edit")) && !($ID <= 0 && $USER->CanDoOperation("controller_member_add")),
	)); ?>
	<? $tabControl->End(); ?>
	<input type="hidden" value="Y" name="apply">
</form>

<? require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php"); ?>
