<?
if(!$USER->IsAdmin())
	return;

IncludeModuleLangFile(__FILE__);

$module_id = 'timeman';
CModule::IncludeModule($module_id);

$arAllModuleOptions = array(
	'edit_wd' => array(
		'workday_start' => array(1 => 'time', 32400 /*9*3600*/, 'clock'),
		'workday_finish' => array(1 => 'time', 64800 /*18*3600*/, 'clock'),
		'workday_close_undo' => array(1 => 'checkbox', 'Y', 'checkbox'),
		'workday_max_start' => array(1 => 'time', 33300 /*9*3600 + 15*60*/, 'clock'),
		'workday_min_finish' => array(1 => 'time', 63900 /*18*3600 - 15*60*/, 'clock'),
		'workday_min_duration' => array(1 => 'time', 28800 /*8*3600*/, 'text'),
		'workday_report_required' => array(1 => 'string', 'A', 'select', array(
			'A' => GetMessage('TM_FIELD_UF_TM_REPORT_REQ_A'),
			'Y' => GetMessage('TM_FIELD_UF_TM_REPORT_REQ_Y'),
			'N' => GetMessage('TM_FIELD_UF_TM_REPORT_REQ_N'),
		)),
		'workday_allowed_delta' => array(1 => 'string', 900, 'text'),
	),
	'edit_wr'=>array(
		"report_forum_id"=>array(1=>'int',0,'selectbox'),
		"WORK_REPORT_PATH"=>array(1=>'string',"/timeman/work_report.php","string"),
		"TIMEMAN_REPORT_PATH"=>array(1=>'string',"/timeman/timeman.php","string"),
	)
);

$aTabs = array(
	array(
		"DIV" => "edit_wd", "TAB" => GetMessage("TM_WD_SETTINGS"), "ICON" => "timeman_settings", "TITLE" => GetMessage("TM_WD_SETTINGS_TITLE"),
	),
	array(
		"DIV" => "edit_access", "TAB" => GetMessage("TM_ACCESS"), "ICON" => "timeman_settings", "TITLE" => GetMessage("TM_ACCESS_TITLE"),
	),
	array(
		"DIV" => "edit_wr", "TAB" => GetMessage("WR_WORK_REPORTS"), "ICON" => "timeman_settings", "TITLE" => GetMessage("WR_WORK_REPORTS_SETTINGS"),
	)
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

if($REQUEST_METHOD=="POST" && strlen($Update.$Apply.$RestoreDefaults)>0 && check_bitrix_sessid())
{
	if(strlen($RestoreDefaults)>0)
	{
		COption::RemoveOption($module_id);

		$z = CGroup::GetList($v1="id",$v2="asc", array("ACTIVE" => "Y", "ADMIN" => "N"));
		while($zr = $z->Fetch())
			$APPLICATION->DelGroupRight($module_id, array($zr["ID"]));
		CGroup::SetTasksForModule($module_id, array());

		COption::SetOptionString($module_id, "GROUP_DEFAULT_TASK", CTask::GetIdByLetter('N', $module_id));
		COption::SetOptionString($module_id, "GROUP_DEFAULT_RIGHT", "N");
	}
	else
	{
		foreach ($arAllModuleOptions as $tab => $arAllOptions)
		{
			foreach ($arAllOptions as $opt => $arOptDef)
			{
				// if (true isset($_REQUEST[$opt]))
				// {
					$value = trim($_REQUEST[$opt]);
					switch ($arOptDef[1])
					{
						case 'time':
							if (strlen($value) > 0)
							{
								list($hour, $min) = explode(':', $value, 2);

								if (IsAmPmMode() && preg_match('/(am|pm)/i', $min, $match))
								{
									$ampm = strtolower($match[0]);
									if ($ampm == 'pm' && $hour < 12)
										$hour += 12;
									elseif ($ampm == 'am' && $hour == 12)
										$hour = 0;
								}

								$value = abs($hour * 3600 + $min * 60);
								if ($value >= 86400)
									$value = 86399;
							}
							else
							{
								$value = 0;
							}

						case 'int':
							COption::SetOptionInt($module_id, $opt, abs(intval($value)), GetMessage('TM_OPT_'.$opt));
						break;

						case 'checkbox':
							$value = $value == 'Y' ? 'Y' : 'N';

						default:
							COption::SetOptionString($module_id, $opt, $value, GetMessage('TM_OPT_'.$opt));
						break;
					}
				// }
			}
		}

		COption::SetOptionString($module_id, 'workday_can_edit_current', $_REQUEST['workday_can_edit_current'] ? 'Y' : 'N');

		ob_start();
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights2.php");
		ob_end_clean();

		$SUBORDINATE_ACCESS = $_REQUEST['SUBORDINATE_ACCESS'];
		COption::SetOptionString($module_id, 'SUBORDINATE_ACCESS', serialize($SUBORDINATE_ACCESS));
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


foreach($arAllModuleOptions as $tab => $arTabOptions)
{
	foreach ($arTabOptions as $key => $arOptDef)
	{
		$value = '';
		switch ($arOptDef[1])
		{
			case 'time':
				$value = COption::GetOptionInt($module_id, $key, $arOptDef[2]);
				if ($arOptDef[3] == 'clock')
					$value = CTimeMan::FormatTimeOut($value);
				else
					$value = CTimeMan::FormatTime($value, true);
			break;

			case 'int': $value = COption::GetOptionInt($module_id, $key, $arOptDef[2]); break;

			default: $value = COption::GetOptionString($module_id, $key, $arOptDef[2]); break;

		}
		$arAllModuleOptions[$tab][$key][0] = $value;
	}
}

$workday_can_edit_current = COption::GetOptionString($module_id, 'workday_can_edit_current', 'Y');

// it's not a mistake!
if (!COption::GetOptionString($module_id, "GROUP_DEFAULT_TASK", ""))
	COption::SetOptionString($module_id, "GROUP_DEFAULT_RIGHT", "N");

if (!$SUBORDINATE_ACCESS = unserialize(COption::GetOptionString($module_id, 'SUBORDINATE_ACCESS', '')))
{
	$SUBORDINATE_ACCESS = array(
		'READ' => array('EMPLOYEE' => 0, 'HEAD' => 1),
		'WRITE' => array('HEAD' => 1),
	);
}

$arHints = array();
reset($arAllModuleOptions);
$tabControl->Begin();
?>
<form method="post" name="tm_opt_form" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=urlencode($mid)?>&amp;lang=<?echo LANGUAGE_ID?>">
<? echo bitrix_sessid_post();?>
<?
list($key, $arOptions) = each($arAllModuleOptions);
$tabControl->BeginNextTab();
foreach ($arOptions as $opt => $arOptDef):
?>
	<tr>
		<td valign="top" width="50%"><?=($hint_title = GetMessage('TM_OPT_'.$opt))?>:
<?
	if ($hint = GetMessage('TM_OPT_HINT_'.$opt)):
		$hint_id = 'hint_'.$opt;
		$arHints[] = array($hint_id, $hint_title, $hint);
?>
			<span id="<?=htmlspecialcharsbx($hint_id)?>"></span>
<?
	endif;
?>
		</td>
		<td valign="top" width="50%"><?
	switch($arOptDef[3])
	{
		case 'clock':
			$APPLICATION->IncludeComponent('bitrix:main.clock', '', array(
				'INPUT_NAME' => $opt,
				'INIT_TIME' => $arOptDef[0],
			), null, array('HIDE_ICONS' => 'Y'));
		break;
		case 'checkbox':
?>
		<input type="checkbox" name="<?=htmlspecialcharsbx($opt)?>" value="Y" <?=($arOptDef[0] == 'Y' ? ' checked="checked"' : '')?> />
<?
		break;
		case 'select':
?>
		<select name="<?=htmlspecialcharsbx($opt)?>">
<?
			foreach ($arOptDef[4] as $v => $t):
?>
			<option value="<?=htmlspecialcharsbx($v)?>"<?=$v==$arOptDef[0]?' selected="selected"':''?>><?=htmlspecialcharsex($t)?></option>
<?
			endforeach;
?>
		</select>
<?
		break;
		default:
?>
		<input type="text" name="<?=htmlspecialcharsbx($opt)?>" value="<?=htmlspecialcharsbx($arOptDef[0])?>" />
<?
		break;
	}
?>
		</td>
	</tr>
<?
endforeach;
?>
<?$tabControl->BeginNextTab();?>

<tr class="heading">
	<td colspan="2"><?=GetMessage('TM_ACCESS_COMMON')?></td>
</tr>

<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights2.php");?>

<tr class="heading">
	<td colspan="2"><?=GetMessage('TM_ACCESS_SUBORDINATE')?></td>
</tr>
<tr>
	<td valign="top"><?=GetMessage('TM_ACCESS_EDIT_CURRENT_DAY')?>: </td>
	<td><input type="checkbox" name="workday_can_edit_current" value="Y" <?=($workday_can_edit_current == 'Y' ? ' checked="checked"' : '')?> /></td>
</tr>
<tr>
	<td valign="top"><?=GetMessage('TM_ACCESS_SUBORDINATE_READ')?>: </td>
	<td><table class="internal" width="100%">
		<tr class="heading">
			<td width="40%"><?=GetMessage('TM_ACCESS_SUBORDINATE_CATEGORY')?></td>
			<td width="60%"><?=GetMessage('TM_ACCESS_SUBORDINATE_ACCESS')?></td>
		</tr>
		<tr>
			<td align="right"><?=GetMessage('TM_ACCESS_S_E')?>:</td>
<?
$s = 'selected="selected" ';
$v = $SUBORDINATE_ACCESS["READ"]["EMPLOYEE"];
?>
			<td><select name="SUBORDINATE_ACCESS[READ][EMPLOYEE]">
				<option <?=$v==0 ? $s : ''?>value="0"><?=GetMessage('TM_ACCESS_S_READ_E_0')?></option>
				<option <?=$v==1 ? $s : ''?>value="1"><?=GetMessage('TM_ACCESS_S_READ_E_1')?></option>
				<option <?=$v==2 ? $s : ''?>value="2"><?=GetMessage('TM_ACCESS_S_READ_E_2')?></option>
			</select></td>
		</tr>
		<tr>
			<td align="right"><?=GetMessage('TM_ACCESS_S_H')?>:</td>
<?$v = $SUBORDINATE_ACCESS["READ"]["HEAD"];?>
			<td><select name="SUBORDINATE_ACCESS[READ][HEAD]">
				<option <?=$v==0 ? $s : ''?>value="0"><?=GetMessage('TM_ACCESS_S_READ_H_0')?></option>
				<option <?=$v==1 ? $s : ''?>value="1"><?=GetMessage('TM_ACCESS_S_READ_H_1')?></option>
				<option <?=$v==2 ? $s : ''?>value="2"><?=GetMessage('TM_ACCESS_S_READ_H_2')?></option>
			</select></td>
		</tr>
	</table></td>
</tr>
<tr>
	<td valign="top">
		<?=GetMessage('TM_ACCESS_SUBORDINATE_WRITE')?>:
<?
$arHints[] = array('SUBORDINATE_WRITE', GetMessage('TM_ACCESS_SUBORDINATE_WRITE'), GetMessage('TM_ACCESS_SUBORDINATE_WRITE_HINT'));
?>
		<span id="SUBORDINATE_WRITE"></span>
	</td>
	<td><table class="internal" width="100%">
		<tr class="heading">
			<td width="40%"><?=GetMessage('TM_ACCESS_SUBORDINATE_CATEGORY')?></td>
			<td width="60%"><?=GetMessage('TM_ACCESS_SUBORDINATE_ACCESS')?></td>
		</tr>
		<tr>
			<td align="right"><?=GetMessage('TM_ACCESS_S_H')?>:</td>
<?$v = $SUBORDINATE_ACCESS["WRITE"]["HEAD"];?>
			<td><select name="SUBORDINATE_ACCESS[WRITE][HEAD]">
				<option <?=$v==0 ? $s : ''?>value="0"><?=GetMessage('TM_ACCESS_S_WRITE_H_0')?></option>
				<option <?=$v==1 ? $s : ''?>value="1"><?=GetMessage('TM_ACCESS_S_WRITE_H_1')?></option>
			</select></td>
		</tr>
	</table></td>
</tr>
<?$tabControl->BeginNextTab();?>
<tr>
<?
$forum_id = $arAllModuleOptions["edit_wr"]["report_forum_id"][0];
$page_path = $arAllModuleOptions["edit_wr"]["WORK_REPORT_PATH"][0];
$timeman_page_path = $arAllModuleOptions["edit_wr"]["TIMEMAN_REPORT_PATH"][0];
if (CModule::IncludeModule("forum"))
{
	$arOrder = array("SORT"=>"ASC", "NAME"=>"ASC");
	$db_Forum = CForumNew::GetList($arOrder, Array("ACTIVE"=>"Y"));
?>
	<td valign="top" width="50%"><?=GetMessage("WR_FORUM_COMMENT_ID")?>:</td><td valign="top" width="50%"><select name="report_forum_id">
	<option value=""><?=GetMessage("WR_WORK_FORUM_NONE");?></option>
	<?
	while ($ar_Forum = $db_Forum->Fetch()):?>
		<option value="<?=$ar_Forum["ID"];?>" <?=(($forum_id==$ar_Forum["ID"])?"selected":"");?> ><?=\Bitrix\Main\Text\HtmlFilter::encode($ar_Forum["NAME"]);?></option>
	<?endwhile;?>
	</select></td>
	<?
}
?>
</tr>
<tr>
	<td valign="top" width="50%"><?=GetMessage("WR_PAGE_PATH")?>:</td><td valign="top" width="50%"><input name="WORK_REPORT_PATH" value="<?=htmlspecialcharsbx($page_path);?>"></td>
</tr><tr>
	<td valign="top" width="50%"><?=GetMessage("TM_OPT_TIMEMAN_REPORT_PATH")?>:</td><td valign="top" width="50%"><input name="TIMEMAN_REPORT_PATH" value="<?=htmlspecialcharsbx($timeman_page_path);?>"></td>
</tr>
<?
if (count($arHints) > 0):
?>
<script type="text/javascript">
BX.ready(function() {
<?
	foreach ($arHints as $hint):
?>
	BX.hint_replace(BX('<?=CUtil::JSEscape($hint[0])?>'), '<?=CUtil::JSEscape($hint[1])?>', '<?=CUtil::JSEscape($hint[2])?>');
<?
	endforeach;
?>
});
</script>
<?
endif;
?>
<?$tabControl->Buttons();?>
	<input type="submit" name="Update" value="<?=GetMessage("MAIN_SAVE")?>" />
	<input type="hidden" name="Update" value="Y" />
	<input type="submit" name="Apply" value="<?=GetMessage("MAIN_APPLY")?>" />
	<?if(strlen($_REQUEST["back_url_settings"])>0):?>
		<input type="button" name="Cancel" value="<?=GetMessage("MAIN_OPT_CANCEL")?>" title="<?=GetMessage("MAIN_OPT_CANCEL_TITLE")?>" onclick="window.location='<?echo htmlspecialcharsbx(CUtil::addslashes($_REQUEST["back_url_settings"]))?>'">
		<input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($_REQUEST["back_url_settings"])?>">
	<?endif?>
	<input type="submit" name="RestoreDefaults" title="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" onclick="return confirm('<?echo AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>')" value="<?echo GetMessage("TM_RESTORE_DEFAULTS")?>">
<?$tabControl->End();?>
</form>