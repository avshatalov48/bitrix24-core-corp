<?
if(!defined("B_PROLOG_INCLUDED")||B_PROLOG_INCLUDED!==true)die();

$arParams['MAX_FILE_SIZE'] = intval($arParams['MAX_FILE_SIZE']);
$arParams['MODULE_ID'] = $arParams['MODULE_ID'] && IsModuleInstalled($arParams['MODULE_ID']) ? $arParams['MODULE_ID'] : false;
// ALLOW_UPLOAD = 'A'll files | 'I'mages | 'F'iles with selected extensions
// ALLOW_UPLOAD_EXT = comma-separated list of allowed file extensions (ALLOW_UPLOAD='F')

$arResult["diskEnabled"] = (\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) && CModule::includeModule('disk'));
	
if (
	$arParams['ALLOW_UPLOAD'] != 'I' &&
	(
		$arParams['ALLOW_UPLOAD'] != 'F' || strlen($arParams['ALLOW_UPLOAD_EXT']) <= 0
	)
)
{
	$arParams['ALLOW_UPLOAD'] = 'A';
}

if (
	$_POST['mfu_mode']
	||
	(
		is_array($_FILES)
		&& count($_FILES) > 0
		&& array_key_exists("file", $_FILES)
	)
)
{
	$APPLICATION->RestartBuffer();
	while(ob_end_clean()); // hack!

	if (!check_bitrix_sessid())
	{
		die();
	}
		
	$arFileError = array();

	$arFileStorage = CMobileHelper::InitFileStorage();
	
	if (isset($arFileStorage["ERROR_CODE"]))
	{
		die();
	}

	header('Content-Type: text/html; charset='.LANG_CHARSET);

	if (
		is_array($_FILES)
		&& count($_FILES) > 0
		&& array_key_exists("file", $_FILES)
	)
	{
		$moduleId = $arParams['MODULE_ID'];
		$max_file_size = $arParams['MAX_FILE_SIZE'];

		if (
			!$moduleId 
			|| !IsModuleInstalled($moduleId)
		)
		{
			$moduleId = 'main';
		}

		$arFile = $_FILES["file"];
		$arFile["MODULE_ID"] = $moduleId;

		$res = '';
		if ($arParams["ALLOW_UPLOAD"] == "I")
		{
			$res = ''.CFile::CheckImageFile($arFile, $max_file_size, 0, 0);
		}
		elseif ($arParams["ALLOW_UPLOAD"] == "F")
		{
			$res = CFile::CheckFile($arFile, $max_file_size, false, $arParams["ALLOW_UPLOAD_EXT"]);
		}
		else
		{
			$res = CFile::CheckFile($arFile, $max_file_size, false, false);
		}

		$arPostResult = array();

		if ($res === '')
		{
			$arSaveResult = CMobileHelper::SaveFile($arFile, $arFileStorage);

			if (
				$arSaveResult
				&& isset($arSaveResult["ID"])
			)
			{
				$varKey = (
					isset($arParams["POST_ID"]) 
					&& intval($arParams["POST_ID"]) > 0 
						? "MFU_UPLOADED_DOCS_".$GLOBALS["USER"]->GetId()."_".intval($arParams["POST_ID"]) 
						: "MFU_UPLOADED_DOCS_".$GLOBALS["USER"]->GetId()
				);

				if (!isset($_SESSION[$varKey]))
				{
					$_SESSION[$varKey] = array($arSaveResult["ID"]);
				}
				else
				{
					$_SESSION[$varKey][] = $arSaveResult["ID"];
				}
						
				if (!empty($arFileStorage["DISC_FOLDER"]))
				{
					$str_res = "disk_id:".$arSaveResult["ID"];
				}
				elseif (
					!empty($arFileStorage["WEBDAV_DATA"])
					&& !empty($arFileStorage["WEBDAV_IBLOCK_OBJECT"])
				)
				{
					$str_res = "element_id:".$arSaveResult["ID"];
				}
				else
				{
					$str_res = "file_id:".$arSaveResult["ID"];
				}
			}
		}

		echo $str_res;		
		die();
	}
	elseif ($_POST['mfu_mode'] == 'delete')
	{
		if (
			IsModuleInstalled("webdav")
			|| IsModuleInstalled("disk")
		)
		{
			$varKey = (
				isset($arParams["POST_ID"]) 
				&& intval($arParams["POST_ID"]) > 0 
					? "MFU_UPLOADED_DOCS_".$GLOBALS["USER"]->GetId()."_".intval($arParams["POST_ID"]) 
					: "MFU_UPLOADED_DOCS_".$GLOBALS["USER"]->GetId()
			);
			$eid = intval($_POST["elementID"]);
			if (
				isset($_SESSION[$varKey]) 
				&& in_array($eid, $_SESSION[$varKey])
			)
			{
				if (
					!empty($arFileStorage["DISC_STORAGE"])
					&& !empty($arFileStorage["DISC_FOLDER"])
				)
				{
					$securityContext = $arFileStorage["DISC_STORAGE"]->getCurrentUserSecurityContext();
					$children = $arFileStorage["DISC_FOLDER"]->getChildren($securityContext, array('filter' => array("ID" => $eid)));
					foreach($children as $oDiskFile)
					{
						$res = $oDiskFile->delete($GLOBALS["USER"]->GetId());
						if (!$res)
						{
							$arFileError[] = array(
								"id" => "error_disk_file_delete",
								"text" => "ERROR_DISK_FILE_DELETE"
							);
						}
						else
						{
							$key = array_search(intval($eid), $_SESSION[$varKey]);
							unset($_SESSION[$varKey][$key]);						
						}
					}
				}
				elseif (!empty($arFileStorage["WEBDAV_IBLOCK_OBJECT"]))
				{
					$res = $arFileStorage["WEBDAV_IBLOCK_OBJECT"]->delete(array('element_id' => $eid));
					if (intval($res) != 204)
					{
						$arFileError[] = array(
							"id" => "error_delete",
							"text" => $arFileStorage["WEBDAV_IBLOCK_OBJECT"]->LAST_ERROR
						);
					}
					else
					{
						$key = array_search(intval($eid), $_SESSION[$varKey]);
						unset($_SESSION[$varKey][$key]);
					}
				}
			}
		}
		else
		{
			$fid = intval($_POST["fileID"]);
			$varKey = (
				isset($arParams["POST_ID"]) 
				&& intval($arParams["POST_ID"]) > 0 
					? "MFU_UPLOADED_FILES_".$GLOBALS["USER"]->GetId()."_".intval($arParams["POST_ID"]) 
					: "MFU_UPLOADED_FILES_".$GLOBALS["USER"]->GetId()
			);
			if (isset($_SESSION[$varKey]) && in_array($fid, $_SESSION[$varKey]))
			{
				CFile::Delete($fid);
				$key = array_search(intval($fid), $_SESSION[$varKey]);
				unset($_SESSION[$varKey][$key]);
			}
		}
	}

	die();
}

if ($arParams['SILENT'])
	return;

if (substr($arParams['INPUT_NAME'], -2) == '[]')
	$arParams['INPUT_NAME'] = substr($arParams['INPUT_NAME'], 0, -2);
if (substr($arParams['INPUT_NAME_UNSAVED'], -2) == '[]')
	$arParams['INPUT_NAME_UNSAVED'] = substr($arParams['INPUT_NAME_UNSAVED'], 0, -2);
if (!is_array($arParams['INPUT_VALUE']) && intval($arParams['INPUT_VALUE']) > 0)
	$arParams['INPUT_VALUE'] = array($arParams['INPUT_VALUE']);

$arParams['INPUT_NAME'] = preg_match('/^[a-zA-Z0-9_]+$/', $arParams['INPUT_NAME']) ? $arParams['INPUT_NAME'] : false;
$arParams['INPUT_NAME_UNSAVED'] = preg_match('/^[a-zA-Z0-9_]+$/', $arParams['INPUT_NAME_UNSAVED']) ? $arParams['INPUT_NAME_UNSAVED'] : '';
$arParams['CONTROL_ID'] = preg_match('/^[a-zA-Z0-9_]+$/', $arParams['CONTROL_ID']) ? $arParams['CONTROL_ID'] : randString();

$arParams['MULTIPLE'] = $arParams['MULTIPLE'] == 'N' ? 'N' : 'Y';

if (!$arParams['INPUT_NAME'])
{
	showError(GetMessage('MFI_ERR_NO_INPUT_NAME'));
	return false;
}

if (
	!IsModuleInstalled("webdav")
	&& !IsModuleInstalled("disk")
)
{
	$arResult['FILES'] = array();

	if (is_array($arParams['INPUT_VALUE']))
	{
		$dbRes = CFile::GetList(array(), array("@ID" => implode(",", $arParams["INPUT_VALUE"])));
		while ($arFile = $dbRes->GetNext())
		{
			$arFile['URL'] = CHTTP::URN2URI($APPLICATION->GetCurPageParam("mfi_mode=down&fileID=".$arFile['ID']."&cid=".$arResult['CONTROL_UID']."&".bitrix_sessid_get(), array("mfi_mode", "fileID", "cid")));
			$arFile['FILE_SIZE_FORMATTED'] = CFile::FormatSize($arFile['FILE_SIZE']);
			$arResult['FILES'][$arFile['ID']] = $arFile;
			$_SESSION["MFU_UPLOADED_FILES_".$GLOBALS["USER"]->GetId()."_".$arResult['CONTROL_UID']][] = $arFile['ID'];
		}
	}
}

if (
	$arResult["diskEnabled"]
	&& isset($arParams["arAttachedObject"])
	&& is_array($arParams["arAttachedObject"])
	&& !empty($arParams["arAttachedObject"])
)
{
	$arResult["arAttachedObject"] = array();
	foreach($arParams["arAttachedObject"] as $val)
	{
		$oAttachedModel = \Bitrix\Disk\AttachedObject::loadById($val, array('OBJECT.STORAGE', 'VERSION'));
		if ($oAttachedModel)
		{
			$oDiskFile = $oAttachedModel->getFile();
			if ($oDiskFile)
			{
				$arResult["arAttachedObject"][] = array(
					"ID" => $val,
					"DISK_FILE_ID" => $oDiskFile->getId()
				);
			}
		}
	}

}

CUtil::InitJSCore(array('ajax'));

$this->IncludeComponentTemplate();

return $arParams['CONTROL_ID'];
?>