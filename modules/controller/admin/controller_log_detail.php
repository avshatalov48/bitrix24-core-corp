<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CUserTypeManager $USER_FIELD_MANAGER */

if (!$USER->CanDoOperation("controller_log_view") || !CModule::IncludeModule("controller"))
{
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/controller/prolog.php");

IncludeModuleLangFile(__FILE__);

$ID = intval($_REQUEST['ID']);
$rsLog = CControllerLog::GetList(array(), array("=ID" => $ID));
$arLog = $rsLog->GetNext();

$APPLICATION->SetTitle(GetMessage("CTRL_LOG_DETAIL_TITLE"));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
?>

<table class="edit-table" cellspacing="0" cellpadding="0" border="0" width="100%"><tr><td>
<table cellspacing="0" cellpadding="0" border="0" class="internal">
<?if($arLog):?>
	<?
	$arLogNames = CControllerLog::GetNameArray();
	$arTaskNames = CControllerTask::GetTaskArray();
	?>
	<tr valign="top">
		<td nowrap align="right"><?echo GetMessage("CTRL_LOG_DETAIL_ID")?>:</td>
		<td nowrap width="100%"><?echo $arLog["ID"]?></td>
	</tr>
	<tr valign="top">
		<td nowrap align="right"><?echo GetMessage("CTRL_LOG_DETAIL_TIMESTAMP_X")?>:</td>
		<td nowrap width="100%"><?echo $arLog["TIMESTAMP_X"]?></td>
	</tr>
	<tr valign="top">
		<td nowrap align="right"><?echo GetMessage("CTRL_LOG_DETAIL_NAME")?>:</td>
		<td nowrap width="100%"><?echo (isset($arLogNames[$arLog["NAME"]])? htmlspecialcharsEx($arLogNames[$arLog["NAME"]]) : $arLog["NAME"])?></td>
	</tr>
	<tr valign="top">
		<td nowrap align="right"><?echo GetMessage("CTRL_LOG_DETAIL_CONTROLLER_MEMBER")?>:</td>
		<td nowrap width="100%"><a target="_blank" href="controller_member_edit.php?lang=<?echo LANGUAGE_ID?>&ID=<?echo $arLog["CONTROLLER_MEMBER_ID"]?>"><?echo $arLog["CONTROLLER_MEMBER_NAME"].' ['.$arLog["CONTROLLER_MEMBER_ID"].']'?></a></td>
	</tr>
	<tr valign="top">
		<td nowrap align="right"><?echo GetMessage("CTRL_LOG_DETAIL_STATUS")?>:</td>
		<td nowrap width="100%"><?echo $arLog["STATUS"]=='Y'? GetMessage("CTRL_LOG_DETAIL_STATUS_OK"): GetMessage("CTRL_LOG_DETAIL_STATUS_ERR")?></td>
	</tr>
	<?if($arLog["TASK_ID"] > 0):?>
		<tr valign="top">
			<td nowrap align="right"><?echo GetMessage("CTRL_LOG_DETAIL_TASK")?>:</td>
			<td nowrap width="100%"><?echo $arTaskNames[$arLog["TASK_NAME"]].' ['.$arLog["TASK_ID"].']'?></td>
		</tr>
	<?endif;?>
	<?if($arLog["USER_ID"] > 0):?>
		<tr valign="top">
			<td nowrap align="right"><?echo GetMessage("CTRL_LOG_DETAIL_USER")?>:</td>
			<td nowrap width="100%"><a target="_blank" href="<?echo htmlspecialcharsbx('user_edit.php?ID='.$arLog["USER_ID"].'&lang='.LANGUAGE_ID)?>"><?echo $arLog["USER_NAME"].' '.$arLog["USER_LAST_NAME"].' ('.$arLog["USER_LOGIN"].')'?></a></td>
		</tr>
	<?endif;?>
	<tr valign="top">
		<td nowrap align="right"><?echo GetMessage("CTRL_LOG_DETAIL_DESCRIPTION")?>:</td>
		<td width="100%">&nbsp;<?echo $arLog["DESCRIPTION"]?></td>
	</tr>
<?else:?>
	<tr>
		<td><?echo GetMessage("CTRL_LOG_DETAIL_NOT_FOUND")?></td>
	</tr>
<?endif;?>
</table>
</td></tr></table>
<br>
<input type="button" onClick="window.close()" value="<?echo GetMessage("CTRL_LOG_DETAIL_CLOSE")?>">

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php")?>
