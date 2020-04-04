<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if(!defined("BX_INTASKS_FROM_COMPONENT") || BX_INTASKS_FROM_COMPONENT!==true)die();

CComponentUtil::__IncludeLang(BX_PERSONAL_ROOT."/components/bitrix/intranet.tasks", "action.php");


function __TaskReturnError($str)
{
	echo $str;
	die();
}

if (!check_bitrix_sessid())
{
	__TaskReturnError(GetMessage("INTL_SECURITY_ERROR").".");
}
elseif (!Is_Array($arResult) || !Is_Array($arResult["Perms"]) || Count($arResult["Perms"]) <= 0)
{
	__TaskReturnError(GetMessage("INTL_TASK_INTERNAL_ERROR").".");
}
else
{
	$action = $_GET['action'];

	if ($action == 'folder_edit')
	{
		$errorMessage = "";

		if (!$arResult["Perms"]["modify_folders"])
			$errorMessage = GetMessage("INTL_NO_FOLDER_PERMS").".";

		if (StrLen($errorMessage) <= 0)
		{
			$parentFolderID = IntVal($_GET["parent"]);
			$folderID = IntVal($_GET["id"]);
			$folderName = Trim($GLOBALS["APPLICATION"]->UnJSEscape($_GET["name"]));
			if (StrLen($folderName) <= 0)
				$errorMessage .= GetMessage("INTL_EMPTY_FOLDER_NAME").". ";
		}

		if (StrLen($errorMessage) <= 0)
		{
			$checkFolderId = (($folderID > 0) ? $folderID : $parentFolderID);
			$dbSectionsChain = CIBlockSection::GetNavChain($iblockId, $checkFolderId);
			$arSect = $dbSectionsChain->GetNext();

			if (!$arSect)
			{
				$errorMessage .= GetMessage("INTL_FOLDER_NOT_FOUND").". ";
			}
			else
			{
				if ($taskType == 'group' && $arSect["XML_ID"] != $ownerId)
					$errorMessage .= GetMessage("INTL_TASK_INTERNAL_ERROR")." GTK001".". ";
				elseif ($taskType != 'group' && $arSect["XML_ID"] != "users_tasks")
					$errorMessage .= GetMessage("INTL_TASK_INTERNAL_ERROR")." GTK002".". ";
			}
		}

		if (StrLen($errorMessage) <= 0)
		{
			if ($folderID <= 0)
			{
				$arFields = array(
					"IBLOCK_ID" => $iblockId,
					"IBLOCK_SECTION_ID" => $parentFolderID,
					"ACTIVE" => "Y",
					"NAME" => $folderName
				);

				$iblockSection = new CIBlockSection;
				$iblockSectionID = $iblockSection->Add($arFields, true);
				if ($iblockSectionID <= 0)
					$errorMessage .= $iblockSection->LAST_ERROR . " ";
			}
			else
			{
				$arFields = array(
					"NAME" => $folderName
				);

				$iblockSection = new CIBlockSection;
				$iblockSectionID = $iblockSection->Update($folderID, $arFields, true);
				if ($iblockSectionID <= 0)
					$errorMessage .= $iblockSection->LAST_ERROR . " ";
			}
		}

		if (StrLen($errorMessage) > 0)
			__TaskReturnError($errorMessage);
	}
	elseif ($action == 'folder_delete')
	{
		$errorMessage = "";

		if (!$arResult["Perms"]["modify_folders"])
			$errorMessage = GetMessage("INTL_NO_FOLDER_PERMS").".";

		if (StrLen($errorMessage) <= 0)
		{
			$folderID = IntVal($_GET["id"]);
			if ($folderID <= 0)
				$errorMessage .= GetMessage("INTL_NO_FOLDER_ID").". ";
		}

		if (StrLen($errorMessage) <= 0)
		{
			$dbSectionsChain = CIBlockSection::GetNavChain($iblockId, $folderID);
			$arSect = $dbSectionsChain->GetNext();

			if (!$arSect)
			{
				$errorMessage .= GetMessage("INTL_FOLDER_NOT_FOUND").". ";
			}
			else
			{
				if ($taskType == 'group' && $arSect["XML_ID"] != $ownerId)
					$errorMessage .= GetMessage("INTL_TASK_INTERNAL_ERROR")." GTK001".". ";
				elseif ($taskType != 'group' && $arSect["XML_ID"] != "users_tasks")
					$errorMessage .= GetMessage("INTL_TASK_INTERNAL_ERROR")." GTK002".". ";
			}
		}

		if (StrLen($errorMessage) <= 0)
		{
			if (!CIBlockSection::Delete($folderID))
				$errorMessage .= GetMessage("INTL_FOLDER_DELETE_ERROR")." ";
		}

		if (StrLen($errorMessage) > 0)
			__TaskReturnError($errorMessage);
	}
}
?>