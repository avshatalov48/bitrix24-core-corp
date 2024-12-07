<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
/** @var CMain $APPLICATION */
/** @var CDatabase $DB */
/** @var CUser $USER */
/** @var CUserTypeManager $USER_FIELD_MANAGER */

if (!$USER->CanDoOperation('controller_log_view') || !CModule::IncludeModule('controller'))
{
	$APPLICATION->AuthForm(GetMessage('ACCESS_DENIED'));
}
require_once $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/controller/prolog.php';

IncludeModuleLangFile(__FILE__);

$ID = intval($_REQUEST['ID']);
$rsLog = CControllerLog::GetList([], ['=ID' => $ID]);
$arLog = $rsLog->GetNext();

$APPLICATION->SetTitle(GetMessage('CTRL_LOG_DETAIL_TITLE'));
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_popup_admin.php';
?>

<table class="edit-table" cellspacing="0" cellpadding="0" border="0" width="100%"><tr><td>
<table cellspacing="0" cellpadding="0" border="0" class="internal">
<?php if ($arLog):?>
	<?php
	$arLogNames = CControllerLog::GetNameArray();
	$arTaskNames = CControllerTask::GetTaskArray();
	?>
	<tr valign="top">
		<td nowrap align="right"><?php echo GetMessage('CTRL_LOG_DETAIL_ID')?>:</td>
		<td nowrap width="100%"><?php echo $arLog['ID']?></td>
	</tr>
	<tr valign="top">
		<td nowrap align="right"><?php echo GetMessage('CTRL_LOG_DETAIL_TIMESTAMP_X')?>:</td>
		<td nowrap width="100%"><?php echo $arLog['TIMESTAMP_X']?></td>
	</tr>
	<tr valign="top">
		<td nowrap align="right"><?php echo GetMessage('CTRL_LOG_DETAIL_NAME')?>:</td>
		<td nowrap width="100%"><?php echo (isset($arLogNames[$arLog['NAME']]) ? htmlspecialcharsEx($arLogNames[$arLog['NAME']]) : $arLog['NAME'])?></td>
	</tr>
	<tr valign="top">
		<td nowrap align="right"><?php echo GetMessage('CTRL_LOG_DETAIL_CONTROLLER_MEMBER')?>:</td>
		<td nowrap width="100%"><a target="_blank" href="controller_member_edit.php?lang=<?php echo LANGUAGE_ID?>&ID=<?php echo $arLog['CONTROLLER_MEMBER_ID']?>"><?php echo $arLog['CONTROLLER_MEMBER_NAME'] . ' [' . $arLog['CONTROLLER_MEMBER_ID'] . ']'?></a></td>
	</tr>
	<tr valign="top">
		<td nowrap align="right"><?php echo GetMessage('CTRL_LOG_DETAIL_STATUS')?>:</td>
		<td nowrap width="100%"><?php echo $arLog['STATUS'] == 'Y' ? GetMessage('CTRL_LOG_DETAIL_STATUS_OK') : GetMessage('CTRL_LOG_DETAIL_STATUS_ERR')?></td>
	</tr>
	<?php if ($arLog['TASK_ID'] > 0):?>
		<tr valign="top">
			<td nowrap align="right"><?php echo GetMessage('CTRL_LOG_DETAIL_TASK')?>:</td>
			<td nowrap width="100%"><?php echo $arTaskNames[$arLog['TASK_NAME']] . ' [' . $arLog['TASK_ID'] . ']'?></td>
		</tr>
	<?php endif;?>
	<?php if ($arLog['USER_ID'] > 0):?>
		<tr valign="top">
			<td nowrap align="right"><?php echo GetMessage('CTRL_LOG_DETAIL_USER')?>:</td>
			<td nowrap width="100%"><a target="_blank" href="<?php echo htmlspecialcharsbx('user_edit.php?ID=' . $arLog['USER_ID'] . '&lang=' . LANGUAGE_ID)?>"><?php echo $arLog['USER_NAME'] . ' ' . $arLog['USER_LAST_NAME'] . ' (' . $arLog['USER_LOGIN'] . ')'?></a></td>
		</tr>
	<?php endif;?>
	<tr valign="top">
		<td nowrap align="right"><?php echo GetMessage('CTRL_LOG_DETAIL_DESCRIPTION')?>:</td>
		<td width="100%">&nbsp;<?php echo $arLog['DESCRIPTION']?></td>
	</tr>
<?php else:?>
	<tr>
		<td><?php echo GetMessage('CTRL_LOG_DETAIL_NOT_FOUND')?></td>
	</tr>
<?php endif;?>
</table>
</td></tr></table>
<br>
<input type="button" onClick="window.close()" value="<?php echo GetMessage('CTRL_LOG_DETAIL_CLOSE')?>">

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_popup_admin.php';
