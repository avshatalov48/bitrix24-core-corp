<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!empty($arResult["ERROR_MESSAGE"]))
	ShowError($arResult["ERROR_MESSAGE"]);
if (!empty($arResult["OK_MESSAGE"]))
	ShowNote($arResult["OK_MESSAGE"]);

CJSCore::Init(array('access', 'dd'));
$GLOBALS['APPLICATION']->AddHeadScript($this->GetFolder().'/script_deferred.js');
$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/webdav/quickedit.js');
$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/main.interface.form/templates/.default/style.css');


$arPerms =& $arResult['PERMISSIONS'];
$arSubjs =& $arResult['SUBJECTS'];
$arData = array();
$UID = randString(4);
$disableGroup = false;
if(IsModuleInstalled('bitrix24') && defined('BX24_HOST_NAME'))
{
	$disableGroup = true;
}

if (!function_exists('__wd_perms_select'))
{
	function __wd_perms_select($id, $groupID, $arRight, &$arPerms)
	{
		$perm = $arRight['TASK_ID'];
		$inherited = ($arRight['IS_INHERITED']==='Y');
		ob_start();
?>
		<div class="wd_right_set">
			<input type='hidden' name='RIGHTS[][RIGHT_ID]' value="<?=htmlspecialcharsbx($id)?>" />
			<input type='hidden' name='RIGHTS[][GROUP_CODE]' value="<?=htmlspecialcharsbx($groupID)?>" />
			<div class="<?=(!$inherited ? 'wd-toggle-edit quick-view' : 'wd-inherited')?> wd-section">
				<?=$arPerms[$perm]?>
				<?if ($inherited && ($arRight['ENTITY_TYPE'] == 'iblock' || $arRight['ENTITY_TYPE'] == 'section')) { ?>
					<? if (! $arRight['ENTITY_SELF']) { ?>
						<a target="<?=$arRight['ENTITY_SOURCE_URL']?>" <?/*ondblclick="return BX.WebDavRightsDialog.ShowDialog(this);"*/?> class="wd-inherited-source">(<?=GetMessage('WD_INHERITED_FROM_'.strtoupper($arRight['ENTITY_TYPE']), array('#NAME#' => htmlspecialcharsEx($arRight['ENTITY_SOURCE_NAME'])))?>)</a>
					<? } else { ?>
						<span></span>
					<? } ?>
				<? } ?>
			</div>
			<select class="<?=(!$inherited ? "quick-edit" : "wd_hidden")?>" style="float: left;" name="RIGHTS[][TASK_ID]">
<?				foreach ($arPerms as $permID => $permTitle) { ?>
					<option value="<?=$permID?>" <?=($permID == $perm ? ' selected=selected class="selected"' : '')?>><?=$permTitle?></option>
<?			} ?>
			</select>
<? if (!$inherited) { ?>
			<div class="wd-rights-delete quick-edit"></div>
<? } ?>
		</div>
<?	return ob_get_clean();
	}
}

$bOkCancel ="";
if(!isset($arParams['POPUP_DIALOG']))
{
	$bOkCancel = "
<div class=\"wd_edit_buttons\">
	<input type=\"button\" class=\"button-edit wd_commit\" value=\"".htmlspecialcharsbx(GetMessage("WD_SAVE"))."\" /> 
	<input type=\"button\" class=\"button-edit wd_rollback wd_hidden\" value=\"".htmlspecialcharsbx(GetMessage("WD_CANCEL"))."\" />
</div>
";
}

foreach($arResult['DATA'] as $id => $perm)
{
	$subj = $perm['GROUP_CODE'];

	$arFields[] = array(
		"id" => implode("_", array("PERM", $subj, $perm['TASK_ID'])),
		"name" => isset($arSubjs[$subj]) ? $arSubjs[$subj]['name'] : '',
		"type" => "custom",
		"value" =>	__wd_perms_select($id, $subj, $perm, $arPerms)
	);
}
	$arFields[] = array("id" => "BUTTONS2", "name" => "", "type" => "custom", "colspan" => true, "value" => bitrix_sessid_post()."
		<table width=\"100%\"><tr>
<td colspan=\"2\" style=\"background-image:none;padding:1px;\"><div class=\"wd_perm_buttons\">
<input type=\"hidden\" name=\"ACTION\" value=\"set_rights\" />
<input type=\"hidden\" name=\"ENTITY_ID\" value=\"".$arParams['ENTITY_ID']."\" />
<input type=\"hidden\" name=\"SOCNET_TYPE\" value=\"".$arParams['SOCNET_TYPE']."\" />
<input type=\"hidden\" name=\"SOCNET_GROUP_ID\" value=\"".$arParams['SOCNET_GROUP_ID']."\" />
<input type=\"hidden\" name=\"SOCNET_ID\" value=\"".$arParams['SOCNET_ID']."\" />
<div class=\"wd_edit\">
	<a class=\"wd_add_permission\" href=\"javascript:void(0);\">".htmlspecialcharsbx(GetMessage("WD_ADD_PERMISSION"))."</a>
</div>
" . $bOkCancel . "
</div></td></tr></table>");
$arTabs = array(
	array(
		"id" => (isset($arParams["TAB_ID"]) ? $arParams["TAB_ID"]."_".$UID : "tab_permissions_".$UID),
		"class" => "tab_permissions",
		"name" => GetMessage("WD_TAB_PERMISSIONS"), 
		"title" => str_replace('""', '', GetMessage("WD_TAB_PERMISSIONS_".$arParams["ENTITY_TYPE"], array("#NAME#" => htmlspecialcharsEx($arResult['ENTITY_NAME'])))), 
		"fields" => $arFields,
	)
);

if (($arParams["MERGE_VIEW"] == "Y") && ($this->__component->__parent))
{
	$this->__component->__parent->arResult["TABS"][] = $arTabs[0];
	if (empty($this->__component->__parent->arResult["DATA"]))
		$this->__component->__parent->arResult["DATA"] = array();
	$this->__component->__parent->arResult["DATA"] = array_merge($this->__component->__parent->arResult["DATA"], $arData);
} else {
	?><div class="wd-iblock-rights"><?
	$APPLICATION->IncludeComponent(
		"bitrix:main.interface.form",
		"",
		array(
			"FORM_ID" => $arParams["FORM_ID"],
			"TABS" => $arTabs,
			"BUTTONS" => array(
				//"back_url" => $APPLICATION->GetCurPageParam("cancel=Y&edit=Y&".bitrix_sessid_get(), array("cancel", "edit", "result")), 
				"custom_html" => '<input type="hidden" name="ENTITY_ID" value="'.$arParams["ENTITY_ID"].'" />'.
					'<input type="hidden" name="edit" value="Y" />'.
					'<input type="hidden" name="ACTION" value="set_rights" />',
				"standard_buttons" => false,
				),
			"DATA" => $arData,
			"SHOW_SETTINGS" => false,
			"SHOW_FORM_TAG" => false,
		),
		($this->__component->__parent ? $this->__component->__parent : $component)
	);
	?></div><?
}

?>
<script type="text/javascript">
BX(function() {
	if (! BX.cur_page)
	{
		BX.cur_page = "<?=CUtil::JSEscape(POST_FORM_ACTION_URI)?>";
	}
	setTimeout(function() {
		var rightsDialog = new BX.WebDavRightsDialog({
				disableGroup: <?= CUtil::PhpToJSObject($disableGroup) ?>,
				tab : BX('tab_permissions_<?=$UID?>_edit_table'),
				group_id : <?=(($arParams['SOCNET_GROUP_ID']>0)?'{"socnetgroup": {"group_id":'.$arParams['SOCNET_GROUP_ID'].'}}':'false')?>,
				extranet: <?=((CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite())?'true':'false')?>,
				cur_page : "<?=CUtil::JSEscape($APPLICATION->GetCurPageParam())?>",
				perms : <?=CUtil::PhpToJSObject($arPerms);?>
			});
	}, 150);
});
</script>
