<?php

class WizardServices
{
	public static function GetTemplates($relativePath)
	{
		$absolutePath = $_SERVER["DOCUMENT_ROOT"].$relativePath;
		$absolutePath = str_replace("\\", "/", $absolutePath);

		$arWizardTemplates = Array();

		if (!$handle  = @opendir($absolutePath))
			return $arWizardTemplates;

		while(($dirName = @readdir($handle)) !== false)
		{
			if ($dirName == "." || $dirName == ".." || !is_dir($absolutePath."/".$dirName)) 
				continue;

			$arTemplate = Array(
				"DESCRIPTION"=>"",
				"NAME" => $dirName,
			);

			if (file_exists($absolutePath."/".$dirName."/description.php"))
			{
				if (LANGUAGE_ID != "en" && LANGUAGE_ID != "ru")
				{
					if (file_exists($absolutePath."/".$dirName."/lang/en/description.php"))
						__IncludeLang($absolutePath."/".$dirName."/lang/en/description.php");
				}

				if (file_exists($absolutePath."/".$dirName."/lang/".LANGUAGE_ID."/description.php"))
						__IncludeLang($absolutePath."/".$dirName."/lang/".LANGUAGE_ID."/description.php");

				include($absolutePath."/".$dirName."/description.php");
			}

			$arTemplate["ID"] = $dirName;
			$arTemplate["SORT"] = 0;

			if (file_exists($absolutePath."/".$dirName."/screen.gif"))
				$arTemplate["SCREENSHOT"] = $relativePath."/".$dirName."/screen.gif";
			else
				$arTemplate["SCREENSHOT"] = false;

			if (file_exists($absolutePath."/".$dirName."/preview.gif"))
				$arTemplate["PREVIEW"] = $relativePath."/".$dirName."/preview.gif";
			else
				$arTemplate["PREVIEW"] = false;

			$arWizardTemplates[$arTemplate["ID"]] = $arTemplate;
		}

		closedir($handle);

		uasort(
			$arWizardTemplates,
			function ($a, $b) {
				return strcmp($a["SORT"], $b["SORT"]);
			}
		);

		return $arWizardTemplates;
	}

	public static function GetTemplatesPath($path)
	{
		$templatesPath = $path."/templates";

		if (file_exists($_SERVER["DOCUMENT_ROOT"].$templatesPath."/".LANGUAGE_ID))
			$templatesPath .= "/".LANGUAGE_ID;

		return $templatesPath;
	}

	public static function GetServices($wizardPath, $serviceFolder = "", $arFilter = Array())
	{
		$arServices = Array();

		$wizardPath = rtrim($wizardPath, "/");
		$serviceFolder = rtrim($serviceFolder, "/");

		if (LANGUAGE_ID != "en" && LANGUAGE_ID != "ru")
		{
			if (file_exists($wizardPath."/lang/en".$serviceFolder."/.services.php"))
				__IncludeLang($wizardPath."/lang/en".$serviceFolder."/.services.php");
		}
		if (file_exists($wizardPath."/lang/".LANGUAGE_ID.$serviceFolder."/.services.php"))
			__IncludeLang($wizardPath."/lang/".LANGUAGE_ID.$serviceFolder."/.services.php");

		$servicePath = $wizardPath."/".$serviceFolder;
		include($servicePath."/.services.php");

		if (empty($arServices))
			return $arServices;

		$servicePosition = 1;
		foreach ($arServices as $serviceID => $arService)
		{
			if (isset($arFilter["SKIP_INSTALL_ONLY"]) && array_key_exists("INSTALL_ONLY", $arService) && $arService["INSTALL_ONLY"] == $arFilter["SKIP_INSTALL_ONLY"])
			{
				unset($arServices[$serviceID]);
				continue;
			}

			if (isset($arFilter["SERVICES"]) && is_array($arFilter["SERVICES"]) && !in_array($serviceID, $arFilter["SERVICES"]) && !array_key_exists("INSTALL_ONLY", $arService))
			{
				unset($arServices[$serviceID]);
				continue;
			}

			//Check service dependencies
			$modulesCheck = Array($serviceID);
			if (array_key_exists("MODULE_ID", $arService))
				$modulesCheck = (is_array($arService["MODULE_ID"]) ? $arService["MODULE_ID"] : Array($arService["MODULE_ID"]));

			foreach ($modulesCheck as $moduleID)
			{
				if (!IsModuleInstalled($moduleID))
				{
					unset($arServices[$serviceID]);
					continue 2;
				}
			}

			$arServices[$serviceID]["POSITION"] = $servicePosition;
			$servicePosition += (isset($arService["STAGES"]) && !empty($arService["STAGES"]) ? count($arService["STAGES"]) : 1);
		}

		return $arServices;
	}

	public static function IncludeServiceLang($relativePath, $lang = false, $bReturnArray = false)
	{
		global $WIZARD_SERVICE_ABSOLUTE_PATH;
		if($lang === false)
			$lang = LANGUAGE_ID;

		$arMessages = Array();
		if ($lang != "en" && $lang != "ru")
		{
			if (file_exists($WIZARD_SERVICE_ABSOLUTE_PATH."/lang/en/".$relativePath))
			{
				if ($bReturnArray)
				{
					$arMessages = __IncludeLang($WIZARD_SERVICE_ABSOLUTE_PATH."/lang/en/".$relativePath, true, true);
				}
				else
				{
					__IncludeLang($WIZARD_SERVICE_ABSOLUTE_PATH."/lang/en/".$relativePath, false, true);
				}
			}
		}

		$fname = $WIZARD_SERVICE_ABSOLUTE_PATH."/lang/".$lang."/".$relativePath;
		$fname = \Bitrix\Main\Localization\Translation::convertLangPath($fname, $lang);
		if (file_exists($fname))
		{
			if ($bReturnArray)
			{
				$arMessages = array_merge($arMessages, __IncludeLang($fname, true, true));
			}
			else
			{
				__IncludeLang($fname, false, true);
			}
		}

		return $arMessages;
	}

	public static function GetCurrentSiteID($selectedSiteID = null)
	{
		if ($selectedSiteID <> '')
		{
			$obSite = CSite::GetList("def", "desc", Array("LID" => $selectedSiteID));
			if (!$arSite = $obSite->Fetch())
				$selectedSiteID = null;
		}

		$currentSiteID = $selectedSiteID;
		if ($currentSiteID == null)
		{
			$currentSiteID = SITE_ID;
			if (defined("ADMIN_SECTION"))
			{
				$obSite = CSite::GetList("def", "desc", Array("ACTIVE" => "Y"));
				if ($arSite = $obSite->Fetch())
					$currentSiteID = $arSite["LID"];
			}
		}
		return $currentSiteID;
	}

	public static function GetThemes($relativePath)
	{
		$arThemes = Array();

		if (!is_dir($_SERVER["DOCUMENT_ROOT"].$relativePath))
			return $arThemes;

		$themePath = $_SERVER["DOCUMENT_ROOT"].$relativePath;
		$themePath = str_replace("\\", "/", $themePath);

		if ($handle = @opendir($themePath))
		{
			while (($file = readdir($handle)) !== false)
			{
				if ($file == "." || $file == ".." || !is_dir($themePath."/".$file))
					continue;

				$arTemplate = Array();
				if (is_file($themePath."/".$file."/description.php"))
				{
					if (LANGUAGE_ID != "en" && LANGUAGE_ID != "ru")
					{
						if (file_exists($themePath."/".$file."/lang/en/description.php"))
							__IncludeLang($themePath."/".$file."/lang/en/description.php");
					}

					if (file_exists($themePath."/".$file."/lang/".LANGUAGE_ID."/description.php"))
							__IncludeLang($themePath."/".$file."/lang/".LANGUAGE_ID."/description.php");

					@include($themePath."/".$file."/description.php");
				}

				$arThemes[$file] = $arTemplate + Array(
					"ID" => $file,
					"SORT" => (isset($arTemplate["SORT"]) && intval($arTemplate["SORT"]) > 0 ? intval($arTemplate["SORT"]) : 10),
					"NAME" => (isset($arTemplate["NAME"]) ? $arTemplate["NAME"] : $file),
					"PREVIEW" => (file_exists($themePath."/".$file."/preview.gif") ? $relativePath."/".$file."/preview.gif" : false),
					"SCREENSHOT" => (file_exists($themePath."/".$file."/screen.gif") ? $relativePath."/".$file."/screen.gif" : false),
				);

			}
			@closedir($handle);
		}

		uasort(
			$arThemes,
			function ($a, $b) {
				return strcmp($a["SORT"], $b["SORT"]);
			}
		);

		return $arThemes;
	}

	public static function SetFilePermission($path, $permissions)
	{
		$originalPath = $path;

		CMain::InitPathVars($site, $path);
		$documentRoot = CSite::GetSiteDocRoot($site);

		$path = rtrim($path, "/");

		if ($path == '')
			$path = "/";

		if( ($position = mb_strrpos($path, "/")) !== false)
		{
			$pathFile = mb_substr($path, $position + 1);
			$pathDir = mb_substr($path, 0, $position);
		}
		else
			return false;

		if ($pathFile == "" && $pathDir == "")
			$pathFile = "/";

		$PERM = Array();
		if(file_exists($documentRoot.$pathDir."/.access.php"))
			@include($documentRoot.$pathDir."/.access.php");

		if (!isset($PERM[$pathFile]) || !is_array($PERM[$pathFile]))
			$arPermisson = $permissions;
		else
			$arPermisson = $permissions + $PERM[$pathFile];

		return $GLOBALS["APPLICATION"]->SetFileAccessPermission($originalPath, $arPermisson);
	}

	public static function AddMenuItem($menuFile, $menuItem,  $siteID, $pos = -1)
	{
		if (CModule::IncludeModule('fileman'))
		{
			$arResult = CFileMan::GetMenuArray($_SERVER["DOCUMENT_ROOT"].$menuFile);
			$arMenuItems = $arResult["aMenuLinks"];
			$menuTemplate = $arResult["sMenuTemplate"];

			$bFound = false;
			foreach($arMenuItems as $item)
				if($item[1] == $menuItem[1])
					$bFound = true;

			if(!$bFound)
			{
				if($pos<0 || $pos>=count($arMenuItems))
					$arMenuItems[] = $menuItem;
				else
				{
					for($i=count($arMenuItems); $i>$pos; $i--)
						$arMenuItems[$i] = $arMenuItems[$i-1];
	
					$arMenuItems[$pos] = $menuItem;					
				}

				CFileMan::SaveMenu(Array($siteID, $menuFile), $arMenuItems, $menuTemplate);
			}
		}
	}

	public static function CopyFile($fileFrom, $fileTo)
	{
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'].$fileFrom, $_SERVER['DOCUMENT_ROOT'].$fileTo, false, true);
	}

	public static function ImportIBlockFromXML($xmlFile, $iblockCode, $iblockType, $siteID, $permissions = Array())
	{
		if (!CModule::IncludeModule("iblock"))
			return false;

		$rsIBlock = CIBlock::GetList(array(), array("CODE" => $iblockCode, "TYPE" => $iblockType));
		if ($arIBlock = $rsIBlock->Fetch())
			return false;

		if (!is_array($siteID))
			$siteID = Array($siteID);

		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/classes/mysql/cml2.php");
		ImportXMLFile($xmlFile, $iblockType, $siteID, $section_action = "N", $element_action = "N");

		$iblockID = false;
		$rsIBlock = CIBlock::GetList(array(), array("CODE" => $iblockCode, "TYPE" => $iblockType));
		if ($arIBlock = $rsIBlock->Fetch())
		{
			$iblockID = $arIBlock["ID"];

			if (empty($permissions))
				$permissions = Array(1 => "X", 2 => "R");

			CIBlock::SetPermission($iblockID, $permissions);
		}

		return $iblockID;
	}

	public static function SetIBlockFormSettings($iblockID, $settings)
	{
		CUserOptions::SetOption(
			"form", 
			"form_element_".$iblockID,
			$settings,
			$common = true
		);
	}

	public static function SetUserOption($category, $option, $settings, $common = false, $userID = false)
	{
		CUserOptions::SetOption(
			$category, 
			$option, 
			$settings, 
			$common, 
			$userID
		);
	}

	public static function CreateSectionProperty($iblockID, $fieldCode, $arFieldName = Array())
	{
		$entityID = "IBLOCK_".$iblockID."_SECTION";
		
		$dbField = CUserTypeEntity::GetList(Array(), array("ENTITY_ID" => $entityID, "FIELD_NAME" => $fieldCode));
		if ($arField = $dbField->Fetch())
			return $arField["ID"];

		$arFields = Array(
			"ENTITY_ID" => $entityID,
			"FIELD_NAME" => $fieldCode,
			"USER_TYPE_ID" => "string",
			"MULTIPLE" => "N",
			"MANDATORY" => "N",
			"EDIT_FORM_LABEL" => $arFieldName
		);

		$obUserField = new CUserTypeEntity;
		$fieldID = $obUserField->Add($arFields);

		return $fieldID;
	}
}
