<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("socialnetwork"))
	return;

if (WIZARD_IS_RERUN !== true)
{
	$APPLICATION->SetGroupRight("socialnetwork", WIZARD_EXTRANET_ADMIN_GROUP, "W");
	$APPLICATION->SetGroupRight("socialnetwork", WIZARD_EXTRANET_CREATE_WG_GROUP, "K");

	COption::SetOptionString("socialnetwork", "allow_frields", "N", false, WIZARD_SITE_ID, false, WIZARD_SITE_ID);
	COption::SetOptionString("socialnetwork", "subject_path_template", WIZARD_SITE_DIR."workgroups/group/search/#subject_id#/", false, WIZARD_SITE_ID);
	COption::SetOptionString("socialnetwork", "group_path_template", WIZARD_SITE_DIR."workgroups/group/#group_id#/", false, WIZARD_SITE_ID);
	COption::SetOptionString("socialnetwork", "messages_path", WIZARD_SITE_DIR."contacts/personal/messages/", false, WIZARD_SITE_ID);
	
	if(!class_exists('CUserOptions'))
		include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/classes/".$GLOBALS['DBType']."/favorites.php");

	$sOptions = 'a:1:{s:7:"GADGETS";a:9:{s:18:"SONET_USER_LINKS@1";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:0;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:19:"SONET_USER_GROUPS@2";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:3;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:17:"SONET_USER_HEAD@3";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:4;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:19:"SONET_USER_HONOUR@4";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:5;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:20:"SONET_USER_ABSENCE@5";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:6;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:17:"SONET_USER_DESC@6";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:0;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:7:"TASKS@7";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:1;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:13:"SONET_FORUM@8";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:2;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:12:"SONET_BLOG@9";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:3;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}}}';
	$arOptions = unserialize($sOptions);
	CUserOptions::SetOption("intranet", "~gadgets_sonet_user_extranet", $arOptions, false, 0);

	$sOptions = 'a:1:{s:7:"GADGETS";a:9:{s:18:"SONET_GROUP_DESC@1";a:3:{s:6:"COLUMN";i:0;s:3:"ROW";i:0;s:4:"HIDE";s:1:"N";}s:12:"SONET_BLOG@2";a:3:{s:6:"COLUMN";i:0;s:3:"ROW";i:1;s:4:"HIDE";s:1:"N";}s:13:"SONET_FORUM@3";a:3:{s:6:"COLUMN";i:0;s:3:"ROW";i:2;s:4:"HIDE";s:1:"N";}s:7:"TASKS@4";a:3:{s:6:"COLUMN";i:0;s:3:"ROW";i:3;s:4:"HIDE";s:1:"N";}s:18:"SONET_GROUP_TAGS@5";a:3:{s:6:"COLUMN";i:0;s:3:"ROW";i:4;s:4:"HIDE";s:1:"N";}s:19:"SONET_GROUP_LINKS@6";a:3:{s:6:"COLUMN";i:1;s:3:"ROW";i:0;s:4:"HIDE";s:1:"N";}s:19:"SONET_GROUP_USERS@7";a:3:{s:6:"COLUMN";i:1;s:3:"ROW";i:1;s:4:"HIDE";s:1:"N";}s:18:"SONET_GROUP_MODS@8";a:3:{s:6:"COLUMN";i:1;s:3:"ROW";i:2;s:4:"HIDE";s:1:"N";}s:16:"UPDATES_ENTITY@9";a:3:{s:6:"COLUMN";i:1;s:3:"ROW";i:3;s:4:"HIDE";s:1:"N";}}}';
	$arOptions = unserialize($sOptions);
	CUserOptions::SetOption("intranet", "~gadgets_sonet_group_extranet", $arOptions, false, 0);	

	socialnetwork::__SetLogFilter(WIZARD_SITE_ID);
}

if (WIZARD_B24_TO_CP)
{
	$filesUserIBlockID = 0;
	$filesGroupIBlockID = 0;
	$calendarUserIBlockID = 0;
	$calendarGroupIBlockID = 0;
	$photoUserIBlockID = 0;
	$photoGroupIBlockID = 0;
	$tasksIblockId = 0;

	if (CModule::IncludeModule("iblock"))
	{
		$ib = new CIBlock;
		$default_site_id = CSite::GetDefSite();
		if (strlen($default_site_id) > 0)
		{
			$dbRes = CIBlock::GetList(array(), array("SITE_ID" => $default_site_id, "CODE" => "user_files"));
			if ($arRes = $dbRes->Fetch())
			{
				$filesUserIBlockID = $arRes["ID"];
			}
			$dbRes = CIBlock::GetList(array(), array("SITE_ID" => $default_site_id, "CODE" => "group_files"));
			if ($arRes = $dbRes->Fetch())
			{
				$filesGroupIBlockID = $arRes["ID"];
			}
			$dbRes = CIBlock::GetList(array(), array("SITE_ID" => $default_site_id, "CODE" => "calendar_employees"));
			if ($arRes = $dbRes->Fetch())
			{
				$calendarUserIBlockID = $arRes["ID"];
			}
			$dbRes = CIBlock::GetList(array(), array("SITE_ID" => $default_site_id, "CODE" => "user_photogallery"));
			if ($arRes = $dbRes->Fetch())
			{
				$photoUserIBlockID = $arRes["ID"];
			}
			$dbRes = CIBlock::GetList(array(), array("SITE_ID" => $default_site_id, "CODE" => "group_photogallery"));
			if ($arRes = $dbRes->Fetch())
			{
				$photoGroupIBlockID = $arRes["ID"];
			}
			$dbRes = CIBlock::GetList(array(), array("SITE_ID" => $default_site_id, "CODE" => "intranet_tasks"));
			if ($arRes = $dbRes->Fetch())
			{
				$tasksIblockId = $arRes["ID"];
			}
		}
	}

	$arReplace = Array(
		"FILES_USER_IBLOCK_ID" => $filesUserIBlockID,
		"CALENDAR_USER_IBLOCK_ID" => $calendarUserIBlockID,
		"PHOTO_USER_IBLOCK_ID" => $photoUserIBlockID,
		"FILES_GROUP_IBLOCK_ID" => $filesGroupIBlockID,
		"CALENDAR_GROUP_IBLOCK_ID" => $calendarGroupIBlockID,
		"PHOTO_GROUP_IBLOCK_ID" => $photoGroupIBlockID,
		"TASKS_IBLOCK_ID" => $tasksIblockId,
	);

	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/workgroups/index.php", $arReplace);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/contacts/personal.php", $arReplace);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/index.php", $arReplace);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/index_b24.php", $arReplace);
}

$cnt = CSocNetGroupSubject::GetList(array(), array("SITE_ID" => WIZARD_SITE_ID), array());
if (IntVal($cnt) > 0)
	return;

$arGroupSubjects = array();
$arGroupSubjectsId = array();

for ($i = 0; $i < 4; $i++)
{
	$arGroupSubjects[$i] = array(
		"SITE_ID" => WIZARD_SITE_ID,
		"NAME" => GetMessage("SONET_GROUP_SUBJECT_".$i),
	);
	$arGroupSubjectsId[$i] = 0;
}

$errorMessage = "";

foreach ($arGroupSubjects as $ind => $arGroupSubject)
{
	$idTmp = CSocNetGroupSubject::Add($arGroupSubject);
	if ($idTmp)
	{
		$arGroupSubjectsId[$ind] = IntVal($idTmp);
	}
	else
	{
		if ($e = $GLOBALS["APPLICATION"]->GetException())
			$errorMessage .= $e->GetString();
	}
}

if (StrLen($errorMessage) <= 0)
{

	$filesUserIBlockID = 0;
	$filesGroupIBlockID = 0;
	$calendarUserIBlockID = 0;
	$calendarGroupIBlockID = 0;
	$photoUserIBlockID = 0;
	$photoGroupIBlockID = 0;

	if (CModule::IncludeModule("iblock"))
	{
		$ib = new CIBlock;

		$default_site_id = CSite::GetDefSite();
		if (strlen($default_site_id) > 0)
		{
			$dbRes = CIBlock::GetList(array(), array("SITE_ID" => $default_site_id, "CODE" => "user_files"));
			if ($arRes = $dbRes->Fetch())
			{
				$filesUserIBlockID = $arRes["ID"];
				
				$arSiteID = array(WIZARD_SITE_ID);
				$rsSites = CIBlock::GetSite($filesUserIBlockID);
				while($arSite = $rsSites->Fetch())
					$arSiteID[] = $arSite["SITE_ID"];

				$arIBlockFields = Array(
					"ACTIVE" => $arRes["ACTIVE"],
					"SITE_ID" => $arSiteID
				);
				$res = $ib->Update($filesUserIBlockID, $arIBlockFields);
			}

			$dbRes = CIBlock::GetList(array(), array("SITE_ID" => $default_site_id, "CODE" => "group_files_".$default_site_id));
			if ($arRes = $dbRes->Fetch())
			{
				$filesGroupIBlockID = $arRes["ID"];

				$arSiteID = array(WIZARD_SITE_ID);
				$rsSites = CIBlock::GetSite($filesGroupIBlockID);
				while($arSite = $rsSites->Fetch())
					$arSiteID[] = $arSite["SITE_ID"];

				$arIBlockFields = Array(
					"ACTIVE" => $arRes["ACTIVE"],
					"SITE_ID" => $arSiteID
				);
				$res = $ib->Update($filesGroupIBlockID, $arIBlockFields);
			}

			$dbRes = CIBlock::GetList(array(), array("SITE_ID" => $default_site_id, "CODE" => "calendar_employees"));
			if ($arRes = $dbRes->Fetch())
			{
				$calendarUserIBlockID = $arRes["ID"];

				$arSiteID = array(WIZARD_SITE_ID);
				$rsSites = CIBlock::GetSite($calendarUserIBlockID);
				while($arSite = $rsSites->Fetch())
					$arSiteID[] = $arSite["SITE_ID"];

				$arIBlockFields = Array(
					"ACTIVE" => $arRes["ACTIVE"],
					"SITE_ID" => $arSiteID
				);
				$res = $ib->Update($calendarUserIBlockID, $arIBlockFields);
			}

			$dbRes = CIBlock::GetList(array(), array("SITE_ID" => $default_site_id, "CODE" => "calendar_groups_".$default_site_id));
			if ($arRes = $dbRes->Fetch())
			{
				$calendarGroupIBlockID = $arRes["ID"];

				$arSiteID = array(WIZARD_SITE_ID);
				$rsSites = CIBlock::GetSite($calendarGroupIBlockID);
				while($arSite = $rsSites->Fetch())
					$arSiteID[] = $arSite["SITE_ID"];

				$arIBlockFields = Array(
					"ACTIVE" => $arRes["ACTIVE"],
					"SITE_ID" => $arSiteID
				);
				$res = $ib->Update($calendarGroupIBlockID, $arIBlockFields);
			}

			$dbRes = CIBlock::GetList(array(), array("SITE_ID" => $default_site_id, "CODE" => "user_photogallery"));
			if ($arRes = $dbRes->Fetch())
			{
				$photoUserIBlockID = $arRes["ID"];

				$arSiteID = array(WIZARD_SITE_ID);
				$rsSites = CIBlock::GetSite($photoUserIBlockID);
				while($arSite = $rsSites->Fetch())
					$arSiteID[] = $arSite["SITE_ID"];

				$arIBlockFields = Array(
					"ACTIVE" => $arRes["ACTIVE"],
					"SITE_ID" => $arSiteID
				);
				$res = $ib->Update($photoUserIBlockID, $arIBlockFields);
			}

			$dbRes = CIBlock::GetList(array(), array("SITE_ID" => $default_site_id, "CODE" => "group_photogallery_".$default_site_id));
			if ($arRes = $dbRes->Fetch())
			{
				$photoGroupIBlockID = $arRes["ID"];

				$arSiteID = array(WIZARD_SITE_ID);
				$rsSites = CIBlock::GetSite($photoGroupIBlockID);
				while($arSite = $rsSites->Fetch())
					$arSiteID[] = $arSite["SITE_ID"];

				$arIBlockFields = Array(
					"ACTIVE" => $arRes["ACTIVE"],
					"SITE_ID" => $arSiteID
				);
				$res = $ib->Update($photoGroupIBlockID, $arIBlockFields);
			}
		}
	}

	// tasks
	$tasksIblockId = 0;
	if (CModule::IncludeModule("iblock") && strlen($default_site_id) > 0)
	{
		$ib = new CIBlock;

		$ibtype_tasks = COption::GetOptionString('intranet', 'iblock_type_tasks', '', '');	
		if (strlen($ibtype_tasks) <= 0)
		{
			$ibtype_tasks = "services";
			COption::SetOptionString("intranet", 'iblock_type_tasks', $ibtype_tasks);
		}

		$dbRes = CIBlock::GetList(array(), array("SITE_ID" => $default_site_id, "CODE" => "intranet_tasks"));
		if ($arRes = $dbRes->Fetch())
		{
			$tasksIblockId = $arRes["ID"];

			$arSiteID = array(WIZARD_SITE_ID);
			$rsSites = CIBlock::GetSite($tasksIblockId);
			while($arSite = $rsSites->Fetch())
				$arSiteID[] = $arSite["SITE_ID"];

			$arIBlockFields = Array(
				"ACTIVE" => $arRes["ACTIVE"],
				"SITE_ID" => $arSiteID
			);
			$res = $ib->Update($tasksIblockId, $arIBlockFields);
		}
	}

	$arReplace = Array(
		"FILES_USER_IBLOCK_ID" => $filesUserIBlockID,
		"CALENDAR_USER_IBLOCK_ID" => $calendarUserIBlockID,
		"PHOTO_USER_IBLOCK_ID" => $photoUserIBlockID,
		"FILES_GROUP_IBLOCK_ID" => $filesGroupIBlockID,
		"CALENDAR_GROUP_IBLOCK_ID" => $calendarGroupIBlockID,
		"PHOTO_GROUP_IBLOCK_ID" => $photoGroupIBlockID,
		"TASKS_IBLOCK_ID" => $tasksIblockId,
	);

	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/workgroups/index.php", $arReplace);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/contacts/personal.php", $arReplace);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/index.php", $arReplace);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/index_b24.php", $arReplace);
}
?>
