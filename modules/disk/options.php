<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Disk\ZipNginx;

if(!$USER->IsAdmin())
	return;

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/options.php");
IncludeModuleLangFile(__FILE__);

include_once($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/disk/default_option.php');
$arDefaultValues['default'] = $disk_default_option;

$notices = $noticeBlock = array();
$socialServiceNotice = '';
if(\Bitrix\Main\Loader::includeModule('disk'))
{
	$documentHandlersManager = \Bitrix\Disk\Driver::getInstance()->getDocumentHandlersManager();

	$optionList = array();
	foreach($documentHandlersManager->getHandlersForView() as $handler)
	{
		$optionList[$handler->getCode()] = $handler->getName();
	}
	unset($handler);

	$currentHandler = $documentHandlersManager->getDefaultHandlerForView();
	if($currentHandler && !$currentHandler->checkAccessibleTokenService())
	{
		$notices['default_viewer_service'] = Loc::getMessage('DISK_DEFAULT_VIEWER_SERVICE_NOTICE_SOC_SERVICE', array(
			'#NAME#' => $currentHandler->getName(),
			'#LANG#' => LANGUAGE_ID,
		));
	}

	$arDefaultValues['default']['default_viewer_service'] = \Bitrix\Disk\Configuration::getDefaultViewerServiceCode();
	$noticeBlock['default_viewer_service'] = Loc::getMessage("DISK_TRANSFORM_FILES_EXTERNAL_SERVICES_NOTICE");

	if(ZipNginx\Configuration::isEnabled() && !ZipNginx\Configuration::isModInstalled())
	{
		$notices['disk_nginx_mod_zip_enabled'] = Loc::getMessage('DISK_ENABLE_NGINX_MOD_ZIP_SUPPORT_NOTICE', array(
			'#LINK#' => 'https://www.nginx.com/resources/wiki/modules/zip/',
		));
	}
}

$arAllOptions = array(
	array("disk_allow_create_file_by_cloud", GetMessage("DISK_ALLOW_CREATE_FILE_BY_CLOUD"), "Y", array("checkbox", "Y")),
	array("disk_allow_autoconnect_shared_objects", GetMessage("DISK_ALLOW_AUTOCONNECT_SHARED_OBJECTS"), "N", array("checkbox", "Y")),
	array("disk_allow_edit_object_in_uf", GetMessage("DISK_ALLOW_EDIT_OBJECT_IN_UF"), "Y", array("checkbox", "Y")),
	array("disk_allow_index_files", GetMessage("DISK_ALLOW_INDEX_FILES_2"), "Y", array("checkbox", "Y")),
	array("disk_allow_use_extended_fulltext", GetMessage("DISK_ALLOW_USE_EXTENDED_FULLTEXT"), "N", array("checkbox", "Y")),
	array("disk_max_file_size_for_index", GetMessage("DISK_MAX_FILE_SIZE_FOR_INDEX"), 1024, Array("text", "20")),
	array("default_viewer_service", GetMessage("DISK_DEFAULT_VIEWER_SERVICE"), $arDefaultValues['default']['default_viewer_service'], array("selectbox", $optionList)),
	array("disk_nginx_mod_zip_enabled", GetMessage("DISK_ENABLE_NGINX_MOD_ZIP_SUPPORT"), $arDefaultValues['default']['disk_nginx_mod_zip_enabled'], array("checkbox", "Y")),
	array("disk_restriction_storage_size_enabled", GetMessage("DISK_ENABLE_RESTRICTION_STORAGE_SIZE_SUPPORT"), 'N', array("checkbox", "Y")),
	array("disk_allow_use_external_link", GetMessage("DISK_ALLOW_USE_EXTERNAL_LINK"), 'Y', array("checkbox", "Y")),
	array("disk_object_lock_enabled", GetMessage("DISK_ENABLE_OBJECT_LOCK_SUPPORT"), 'N', array("checkbox", "Y")),
	array("disk_version_limit_per_file", GetMessage("DISK_VERSION_LIMIT_PER_FILE"), 0, Array("selectbox", array(0 => GetMessage('DISK_VERSION_LIMIT_PER_FILE_UNLIMITED'), 3  => 3, 10 => 10, 25  => 25, 50 => 50, 100 => 100, 500 => 500))),
);
$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("MAIN_TAB_SET"), "ICON" => "ib_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_SET")),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

if($_SERVER["REQUEST_METHOD"]=="POST" && ($_POST['Update'] || $_POST['Apply'] || $_POST['RestoreDefaults'])>0 && check_bitrix_sessid())
{
	if(strlen($_POST['RestoreDefaults'])>0)
	{
		$arDefValues = $arDefaultValues['default'];
		foreach($arDefValues as $key=>$value)
		{
			COption::RemoveOption("disk", $key);
		}
	}
	else
	{
		foreach($arAllOptions as $arOption)
		{
			$name=$arOption[0];
			$val=$_REQUEST[$name];
			if($arOption[3][0]=="checkbox" && $val!="Y")
				$val="N";
			COption::SetOptionString("disk", $name, $val, $arOption[1]);
		}
	}
	if(strlen($_POST['Update'])>0 && strlen($_REQUEST["back_url_settings"])>0)
		LocalRedirect($_REQUEST["back_url_settings"]);
	else
		LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($mid)."&lang=".urlencode(LANGUAGE_ID)."&back_url_settings=".urlencode($_REQUEST["back_url_settings"])."&".$tabControl->ActiveTabParam());
}


$tabControl->Begin();
?>
<form method="post" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=urlencode($mid)?>&amp;lang=<?echo LANGUAGE_ID?>">
<?$tabControl->BeginNextTab();?>
	<?
	foreach($arAllOptions as $arOption):
		$val = COption::GetOptionString("disk", $arOption[0], $arOption[2]);
		$type = $arOption[3];
	?>
	<tr>
		<td width="40%" nowrap <?if($type[0]=="textarea") echo 'class="adm-detail-valign-top"'?>>
			<label for="<?echo htmlspecialcharsbx($arOption[0])?>"><?echo $arOption[1]?>:</label>
		<td width="60%">
			<?if($type[0]=="checkbox"):?>
				<input type="checkbox" id="<?echo htmlspecialcharsbx($arOption[0])?>" name="<?echo htmlspecialcharsbx($arOption[0])?>" value="Y"<?if($val=="Y")echo" checked";?>>
			<?elseif($type[0]=="text"):?>
				<input type="text" size="<?echo $type[1]?>" maxlength="255" value="<?echo htmlspecialcharsbx($val)?>" name="<?echo htmlspecialcharsbx($arOption[0])?>">
			<?elseif($type[0]=="textarea"):?>
				<textarea rows="<?echo $type[1]?>" cols="<?echo $type[2]?>" name="<?echo htmlspecialcharsbx($arOption[0])?>"><?echo htmlspecialcharsbx($val)?></textarea>
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
			&nbsp;<? echo (empty($notices[$arOption[0]])? '' : $notices[$arOption[0]])  ?>
		</td>
	</tr>
	<? if($noticeBlock[$arOption[0]]): ?>
		<tr>
			<td colspan="2" align="center">
				<div class="adm-info-message-wrap" align="center">
					<div class="adm-info-message">
						<?= $noticeBlock[$arOption[0]] ?>
					</div>
				</div>
			</td>
		</tr>
	<? endif; ?>
	<?endforeach?>
<?$tabControl->Buttons();?>
	<input type="submit" name="Update" value="<?=GetMessage("MAIN_SAVE")?>" title="<?=GetMessage("MAIN_OPT_SAVE_TITLE")?>" class="adm-btn-save">
	<input type="submit" name="Apply" value="<?=GetMessage("MAIN_OPT_APPLY")?>" title="<?=GetMessage("MAIN_OPT_APPLY_TITLE")?>">
	<?if(strlen($_REQUEST["back_url_settings"])>0):?>
		<input type="button" name="Cancel" value="<?=GetMessage("MAIN_OPT_CANCEL")?>" title="<?=GetMessage("MAIN_OPT_CANCEL_TITLE")?>" onclick="window.location='<?echo htmlspecialcharsbx(CUtil::addslashes($_REQUEST["back_url_settings"]))?>'">
		<input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($_REQUEST["back_url_settings"])?>">
	<?endif?>
	<input type="submit" name="RestoreDefaults" title="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" OnClick="return confirm('<?echo AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>')" value="<?echo GetMessage("MAIN_RESTORE_DEFAULTS")?>">
	<?=bitrix_sessid_post();?>
<?$tabControl->End();?>
</form>
