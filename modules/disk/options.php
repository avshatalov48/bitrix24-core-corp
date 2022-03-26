<?php

use Bitrix\Disk\Configuration;
use Bitrix\Disk\Document\OnlyOffice;
use Bitrix\Disk\Document\OnlyOffice\OnlyOfficeHandler;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Localization\Loc;
use Bitrix\Disk\ZipNginx;
use Bitrix\Main\ModuleManager;

if(!$USER->IsAdmin())
	return;

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/options.php");
IncludeModuleLangFile(__FILE__);

\Bitrix\Main\UI\Extension::load(["popup", "loader", "disk.b24-documents-client-registration"]);

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
		$optionList[$handler::getCode()] = $handler::getName();
	}

	$currentHandler = $documentHandlersManager->getDefaultHandlerForView();
	if($currentHandler && !$currentHandler->checkAccessibleTokenService())
	{
		$notices['default_viewer_service'] = Loc::getMessage('DISK_DEFAULT_VIEWER_SERVICE_NOTICE_SOC_SERVICE', array(
			'#NAME#' => $currentHandler::getName(),
			'#LANG#' => LANGUAGE_ID,
		));
	}

	$arDefaultValues['default']['default_viewer_service'] = Configuration::getDefaultViewerServiceCode();
	$noticeBlock['default_viewer_service'] = Loc::getMessage("DISK_TRANSFORM_FILES_EXTERNAL_SERVICES_NOTICE");

	if (!OnlyOfficeHandler::isEnabled())
	{
		$labelButton = Loc::getMessage('DISK_SETTINGS_B24_DOCS_REGISTER_BUTTON', ['#NAME#' => OnlyOfficeHandler::getName()]);
		$notices['default_viewer_service'] = '<input type="button" id="registerBitrix24Docs" name="registerBitrix24Docs" value="' . $labelButton . '" class="adm-btn-save">';
	}
	else
	{
		$limitValue = null;
		$cloudConfiguration = new OnlyOffice\Configuration();
		$cloudRegistrationData = $cloudConfiguration->getCloudRegistrationData();

		if ($cloudRegistrationData)
		{
			$labelButton = Loc::getMessage('DISK_SETTINGS_B24_DOCS_UNREGISTER_BUTTON', ['#NAME#' => OnlyOfficeHandler::getName()]);
			$notices['default_viewer_service'] = '<input type="button" id="unregisterBitrix24Docs" name="unregisterBitrix24Docs" value="' . $labelButton . '" class="adm-btn">';
			$limitValueResult = (new OnlyOffice\Cloud\LimitInfo($cloudRegistrationData['serverHost']))->getClientLimit();
			if ($limitValueResult->isSuccess())
			{
				$limitValue = $limitValueResult->getData()['limit'] ?? null;
			}
		}

		if ($limitValue)
		{
			$noticeBlock['default_viewer_service'] = Loc::getMessage('DISK_SETTINGS_B24_DOCS_LIMIT_INFO', ['#limit#' => $limitValue]) . "<br> " . $noticeBlock['default_viewer_service'];
		}
	}

	if(ZipNginx\Configuration::isEnabled() && !ZipNginx\Configuration::isModInstalled())
	{
		$notices['disk_nginx_mod_zip_enabled'] = Loc::getMessage('DISK_ENABLE_NGINX_MOD_ZIP_SUPPORT_NOTICE', array(
			'#LINK#' => 'https://www.nginx.com/resources/wiki/modules/zip/',
		));
	}

	if(OnlyOfficeHandler::isEnabled())
	{
		$secretKey = ServiceLocator::getInstance()->get('disk.onlyofficeConfiguration')->getSecretKey();
		$isValidToken = OnlyOfficeHandler::isValidToken($secretKey);
		if (!$isValidToken->isSuccess())
		{
            $notices['disk_onlyoffice_secret_key'] = $isValidToken->getErrors()[0]->getMessage();
		}
		else
        {
            $notices['disk_onlyoffice_server'] = $isValidToken->getData()['version'];
        }
	}
}

$onlyOfficeEnabledOnBitrix24 = Configuration::isEnabledDocuments() && ModuleManager::isModuleInstalled('bitrix24');

$arAllOptions = array_filter(array(
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
	array("disk_version_limit_per_file", GetMessage("DISK_VERSION_LIMIT_PER_FILE"), 0, Array("selectbox", array(0 => GetMessage('DISK_VERSION_LIMIT_PER_FILE_UNLIMITED'), 3  => 3, 10 => 10, 25  => 25, 50 => 50, 100 => 100, 500 => 500))),
	GetMessage("DISK_SETTINGS_SECTION_HEAD_FILE_LOCK"),
	array("disk_object_lock_enabled", GetMessage("DISK_ENABLE_OBJECT_LOCK_SUPPORT"), 'N', array("checkbox", "Y")),
	//    Configuration::isEnabledObjectLock()? array("disk_auto_lock_on_object_edit", GetMessage("DISK_SETTINGS_AUTO_LOCK_ON_OBJECT_EDIT"), 'N', array("checkbox", "Y")) : null,
	//    Configuration::isEnabledObjectLock()? array("disk_auto_release_lock_on_save", GetMessage("DISK_SETTINGS_AUTO_RELEASE_LOCK_ON_SAVE"), 'N', array("checkbox", "Y")) : null,
	//    Configuration::isEnabledObjectLock()? array("disk_time_auto_release_object_lock", GetMessage("DISK_SETTINGS_TIME_AUTO_RELEASE_OBJECT_LOCK"), 0, Array("text", "20")) : null,
	$onlyOfficeEnabledOnBitrix24 ? array("section" => GetMessage("DISK_SETTINGS_ONLYOFFICE_HEAD")) : null,
	$onlyOfficeEnabledOnBitrix24 ? array("disk_onlyoffice_server", GetMessage("DISK_SETTINGS_ONLYOFFICE_SERVER"), '', Array("text", "32")) : null,
	$onlyOfficeEnabledOnBitrix24 ? array("disk_onlyoffice_secret_key", GetMessage("DISK_SETTINGS_ONLYOFFICE_SECRET_KEY"), '', Array("text", "32")) : null,
	$onlyOfficeEnabledOnBitrix24 ? array("disk_onlyoffice_max_filesize", GetMessage("DISK_SETTINGS_ONLYOFFICE_MAX_FILESIZE"), '', Array("text", "32")) : null,
));
$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("MAIN_TAB_SET"), "ICON" => "ib_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_SET")),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

if($_SERVER["REQUEST_METHOD"]=="POST" && ($_POST['Update'] || $_POST['Apply'] || $_POST['RestoreDefaults'])>0 && check_bitrix_sessid())
{
	if($_POST['RestoreDefaults'] <> '')
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
			if (isset($arOption['section']))
			{
                continue;
			}

			$name=$arOption[0];
			$val=$_REQUEST[$name];
			if($arOption[3][0]=="checkbox" && $val!="Y")
				$val="N";
			COption::SetOptionString("disk", $name, $val, $arOption[1]);
		}
	}
	if($_POST['Update'] <> '' && $_REQUEST["back_url_settings"] <> '')
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
		if (isset($arOption['section']))
		{
		    echo <<<HTML
            <tr class="heading">
                <td colspan="2">{$arOption['section']}</td>
            </tr>
HTML;

            continue;
		}

        $val = null;
		if (!is_array($arOption))
		{
            $arOption = [null, $arOption, null, ['heading']];
		}
		else
        {
		    $val = COption::GetOptionString("disk", $arOption[0], $arOption[2]);
        }

		$type = $arOption[3];
	?>
    <?if($type[0]=="heading"):?>
    <tr class="heading">
        <td colspan="2"><?=$arOption[1]?></td>
    </tr>
    <?else:?>
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
    <?endif?>
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
	<?if($_REQUEST["back_url_settings"] <> ''):?>
		<input type="button" name="Cancel" value="<?=GetMessage("MAIN_OPT_CANCEL")?>" title="<?=GetMessage("MAIN_OPT_CANCEL_TITLE")?>" onclick="window.location='<?echo htmlspecialcharsbx(CUtil::addslashes($_REQUEST["back_url_settings"]))?>'">
		<input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($_REQUEST["back_url_settings"])?>">
	<?endif?>
	<input type="submit" name="RestoreDefaults" title="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" OnClick="return confirm('<?echo AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>')" value="<?echo GetMessage("MAIN_RESTORE_DEFAULTS")?>">
	<?=bitrix_sessid_post();?>
<?$tabControl->End();?>
</form>
<script>
	BX.ready(function(){
		BX.bind(BX('registerBitrix24Docs'), 'click', function(e){
			e.preventDefault();

			(new BX.Disk.B24Documents.ClientRegistration()).start();
		});
		BX.bind(BX('unregisterBitrix24Docs'), 'click', function(e){
			e.preventDefault();

			(new BX.Disk.B24Documents.ClientUnRegistration()).start();
		});
	});
</script>