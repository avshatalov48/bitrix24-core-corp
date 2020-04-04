<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
	return;

if (!CModule::IncludeModule('sale'))
	return;

global $USER;

$CCrmPerms = new CCrmPerms($USER->GetID());
if ($CCrmPerms->HavePerm('CONFIG', BX_CRM_PERM_NONE, 'WRITE'))
{
	ShowError(GetMessage('CRM_LOC_IMP_ERROR_ACCESS_DENIED'));
	return;
}

CUtil::InitJSCore();

$arResult["TMP_PATH"] = CTempFile::GetDirectoryName(12, 'crm');
CheckDirPath($arResult["TMP_PATH"]);

if ($_SERVER['REQUEST_METHOD'] == 'POST') // process data from popup dialog
{
	if (check_bitrix_sessid())
	{
		$arResult['BACK_URL'] = isset($_POST['BACK_URL']) ? $_POST['BACK_URL'] : '';

		$locations_csv = $_POST['locations_csv'];
		$load_zip = isset($_POST['load_zip']) && $_POST['load_zip'] == 'Y' ? 'Y' : 'N';
		$errorMsg = '';

		if (strlen($locations_csv) <= 0 && $load_zip != 'Y')
			$errorMsg .= GetMessage('CRM_LOC_IMP_GFILE_ERROR');

		if($locations_csv == "locations.csv")
		{
			if (!is_uploaded_file($_FILES["FILE_IMPORT_UPLOAD"]["tmp_name"])
				|| !file_exists($_FILES["FILE_IMPORT_UPLOAD"]["tmp_name"]))
			{
				$errorMsg .= GetMessage("CRM_LOC_IMP_NO_LOC_FILE");
			}
			else
			{
				$fp = fopen($_FILES["FILE_IMPORT_UPLOAD"]["tmp_name"], 'r');
				$contents = fread($fp, filesize($_FILES["FILE_IMPORT_UPLOAD"]["tmp_name"]));
				fclose($fp);

				$contents = $GLOBALS["APPLICATION"]->ConvertCharset($contents, 'windows-1251', LANG_CHARSET);

				$fp = fopen($arResult["TMP_PATH"].'/locations.csv', 'w+');
				fwrite($fp, $contents);
				fclose($fp);
			}
		}

		$step_length = intval($_POST['step_length']);

		if ($step_length <= 0)
			$errorMsg .= GetMessage('CRM_LOC_IMP_STEP_LENGTH_ERROR');

		if(strlen($errorMsg) > 0)
			$arResult['ERROR_MSG'] = $errorMsg;

	}
}

$this->IncludeComponentTemplate();
?>
