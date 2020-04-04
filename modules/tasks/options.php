<?php
if(!$USER->IsAdmin())
	return;

IncludeModuleLangFile(__FILE__);

\Bitrix\Main\Loader::includeModule('tasks');

$arDefaultValues = array(
	'paths_task_user' => '/company/personal/user/#user_id#/tasks/',
	'paths_task_user_entry' => '/company/personal/user/#user_id#/tasks/task/view/#task_id#/',
	'paths_task_user_edit' => '/company/personal/user/#user_id#/tasks/task/edit/#task_id#/',
	'paths_task_user_action' => '/company/personal/user/#user_id#/tasks/task/#action#/#task_id#/',
	'paths_task_group' => '/workgroups/group/#group_id#/tasks/',
	'paths_task_group_entry' => '/workgroups/group/#group_id#/tasks/task/view/#task_id#/',
	'paths_task_group_edit' => '/workgroups/group/#group_id#/tasks/task/edit/#task_id#/',
	'paths_task_group_action' => '/workgroups/group/#group_id#/tasks/task/#action#/#task_id#/'
);

$arForums = array();
if (CModule::IncludeModule("forum"))
{
	$db = CForumNew::GetListEx();
	while ($ar = $db->GetNext())
		$arForums[$ar["ID"]] = "[".$ar["ID"]."] ".$ar["NAME"];
}

$dbSites = CSite::GetList(($b = ""), ($o = ""), Array("ACTIVE" => "Y"));
$arSites = array();
$aSubTabs = array();
while ($site = $dbSites->Fetch())
{
	$arSites[] = $site;
	$aSubTabs[] = array(
		"DIV" => "opt_site_".$site["ID"], 
		"TAB" => "(".htmlspecialcharsbx($site["ID"]).") ". htmlspecialcharsbx($site["NAME"]), 
		'TITLE' => GetMessage('TASKS_SUBTAB_TITLE_SITE').' '.htmlspecialcharsbx($site["ID"]));
}
$subTabControl = new CAdminViewTabControl("subTabControl", $aSubTabs);

$aTabs = array(
	array(
		"DIV" => "edit1", "TAB" => GetMessage("TASKS_SETTINGS"), "ICON" => "tasks_settings", "TITLE" => GetMessage("TASKS_SETTINGS_TITLE"),
	),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

if($REQUEST_METHOD=="POST" && strlen($Update.$Apply.$RestoreDefaults)>0 && check_bitrix_sessid())
{
	if(strlen($RestoreDefaults)>0)
	{
		COption::RemoveOption("tasks");
	}
	else
	{
		COption::SetOptionString("tasks", "task_forum_id", intval($_POST["task_forum_id"]));
		COption::SetOptionString("tasks", "task_comment_allow_edit", $_POST["task_comment_allow_edit"] == 'Y' ? 'Y' : 'N', false, '');
		COption::SetOptionString("tasks", "task_list_uf_sort_filter", $_POST["task_list_uf_sort_filter"] == 'Y' ? 'Y' : 'N', false, '');
		foreach($arSites as $site)
		{
			foreach($arDefaultValues as $key=>$value)
			{
				if (isset($_POST[$key."_".$site["LID"]]))
				{
					COption::SetOptionString("tasks", $key, $_POST[$key."_".$site["LID"]], false, $site["LID"]);
				}
			}
		}

		if(strlen($Update)>0 && strlen($_REQUEST["back_url_settings"])>0)
		{
			LocalRedirect($_REQUEST["back_url_settings"]);
		}
		else
		{
			LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($mid)."&lang=".urlencode(LANGUAGE_ID)."&back_url_settings=".urlencode($_REQUEST["back_url_settings"])."&".$tabControl->ActiveTabParam());
		}
	}
}

// check and restore CTaskCountersProcessor::agent();, if there is no one
//CTaskCountersProcessor::ensureAgentExists();
?>
<form method="post" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&amp;lang=<?echo LANG?>">
<?php echo bitrix_sessid_post()?>
<?php
$tabControl->Begin();
$tabControl->BeginNextTab();
?>
	<tr>
		<td align="right"><?php echo GetMessage("TASKS_COMMENTS_FORUM")?>:</td>
		<td>
			<select name="task_forum_id" id="id_task_forum_id">
				<option value="0">&nbsp;</option>
				<?php foreach ($arForums as $key => $value):?>
					<option value="<?php echo $key ?>"<?php echo COption::GetOptionString("tasks", "task_forum_id") == $key ? " selected" : "" ?>><?php echo  $value?></option>
				<?php endforeach?>
			</select>

		</td>
	</tr>
	<tr>
		<td align="right"><?php echo GetMessage("TASKS_COMMENTS_ALLOW_EDIT_REMOVE")?>:</td>
		<td>
			<?$value = COption::GetOptionString("tasks", "task_comment_allow_edit");?>
			<input type="checkbox" name="task_comment_allow_edit" value="Y"<?=($value == 'Y' || $value == '1' ? " checked" : "" )?> />
		</td>
	</tr>
	<tr>
		<td align="right"><?php echo GetMessage("TASKS_LIST_ALLOW_SORT_FILTER_UF")?>:</td>
		<td>
			<input type="checkbox" name="task_list_uf_sort_filter" value="N"<?php echo COption::GetOptionString("tasks", "task_list_uf_sort_filter") ? " checked" : "" ?> />
		</td>
	</tr>

	<tr class="heading">
		<td colspan="2"><?php echo GetMessage("TASKS_OPTION_PATHS")?></td>
	</tr>
	<tr>
		<td colspan="2">
<?php
$dbSites = CSite::GetList(($b = ""), ($o = ""), Array("ACTIVE" => "Y"));
$subTabControl->Begin();
foreach ($arSites as $site)
{
	$subTabControl->BeginNextTab();
?>
	<table width="75%" align="center">
<?php
	foreach($arDefaultValues as $key=>$value)
	{
?>
		<tr>
			<td align="right"><?php echo GetMessage("TASKS_".strtoupper($key))?>:</td>
			<td><input type="text" size="40" value="<?=htmlspecialcharsbx(COption::GetOptionString("tasks", $key, $value, $site["LID"]))?>" name="<?php echo $key?>_<?php echo htmlspecialcharsbx($site["LID"]); ?>"></td>
		</tr>
<?php
	}
?>
	</table>
<?php
}
$subTabControl->End();
?>
		</td>
	</tr>
<?php $tabControl->Buttons();?>
	<input type="submit" name="Update" value="<?php echo GetMessage("MAIN_SAVE")?>" title="<?php echo GetMessage("MAIN_OPT_SAVE_TITLE")?>" />
	<input type="submit" name="Apply" value="<?php echo GetMessage("MAIN_APPLY")?>" title="<?php echo GetMessage("MAIN_OPT_APPLY_TITLE")?>">
	<?php if(strlen($_REQUEST["back_url_settings"])>0):?>
		<input type="button" name="Cancel" value="<?php echo GetMessage("MAIN_OPT_CANCEL")?>" title="<?php echo GetMessage("MAIN_OPT_CANCEL_TITLE")?>" onclick="window.location='<?php echo htmlspecialcharsbx(CUtil::addslashes($_REQUEST["back_url_settings"]))?>'">
		<input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($_REQUEST["back_url_settings"])?>">
	<?php endif?>
	<input type="submit" name="RestoreDefaults" title="<?php echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" onclick="return confirm('<?php echo AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>')" value="<?php echo GetMessage("TASKS_RESTORE_DEFAULTS")?>">
<?php $tabControl->End();?>
</form>