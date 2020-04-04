<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

//echo "WIZARD_SITE_ID=".WIZARD_SITE_ID." | ";
//echo "WIZARD_SITE_PATH=".WIZARD_SITE_PATH." | ";
//echo "WIZARD_RELATIVE_PATH=".WIZARD_RELATIVE_PATH." | ";
//echo "WIZARD_ABSOLUTE_PATH=".WIZARD_ABSOLUTE_PATH." | ";
//echo "WIZARD_TEMPLATE_ID=".WIZARD_TEMPLATE_ID." | ";
//echo "WIZARD_TEMPLATE_RELATIVE_PATH=".WIZARD_TEMPLATE_RELATIVE_PATH." | ";
//echo "WIZARD_TEMPLATE_ABSOLUTE_PATH=".WIZARD_TEMPLATE_ABSOLUTE_PATH." | ";
//echo "WIZARD_THEME_ID=".WIZARD_THEME_ID." | ";
//echo "WIZARD_THEME_RELATIVE_PATH=".WIZARD_THEME_RELATIVE_PATH." | ";
//echo "WIZARD_THEME_ABSOLUTE_PATH=".WIZARD_THEME_ABSOLUTE_PATH." | ";
//echo "WIZARD_SERVICE_RELATIVE_PATH=".WIZARD_SERVICE_RELATIVE_PATH." | ";
//echo "WIZARD_SERVICE_ABSOLUTE_PATH=".WIZARD_SERVICE_ABSOLUTE_PATH." | ";
//echo "WIZARD_IS_RERUN=".WIZARD_IS_RERUN." | ";
//die();

if (!defined("WIZARD_TEMPLATE_ID"))
	return;

function ___writeToAreasFile($fn, $text)
{
	if(file_exists($fn) && !is_writable($fn) && defined("BX_FILE_PERMISSIONS"))
		@chmod($fn, BX_FILE_PERMISSIONS);

	$fd = @fopen($fn, "wb");
	if(!$fd)
		return false;

	if(false === fwrite($fd, $text))
	{
		fclose($fd);
		return false;
	}

	fclose($fd);

	if(defined("BX_FILE_PERMISSIONS"))
		@chmod($fn, BX_FILE_PERMISSIONS);
}

$logo = WIZARD_SITE_LOGO;

if (in_array(WIZARD_TEMPLATE_ID, array("bright_extranet", "classic_extranet", "modern_extranet")))
{
	$bitrixTemplateDir = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/";

	CopyDirFiles(
		$_SERVER["DOCUMENT_ROOT"].CExtranetWizardServices::GetTemplatesPath(WIZARD_RELATIVE_PATH."/site")."/".WIZARD_TEMPLATE_ID,
		$bitrixTemplateDir.WIZARD_TEMPLATE_ID,
		$rewrite = true,
		$recursive = true
	);

	$logo = (intval($logo) ? CFile::GetPath($logo) : '/bitrix/templates/'.WIZARD_TEMPLATE_ID.'/images/default_logo.gif');

	CWizardUtil::ReplaceMacros(
		$bitrixTemplateDir.WIZARD_TEMPLATE_ID.'/include_areas/company_name.php',
		array(
			"SITE_DIR" => WIZARD_SITE_DIR,
			"COMPANY_NAME" => WIZARD_SITE_NAME,
			"COMPANY_LOGO" => $logo,
		)
	);

	CWizardUtil::ReplaceMacros(
		$bitrixTemplateDir.WIZARD_TEMPLATE_ID.'/header.php',
		array(
			"SITE_DIR" => WIZARD_SITE_DIR,
		)
	);

	CWizardUtil::ReplaceMacros(
		$bitrixTemplateDir.WIZARD_TEMPLATE_ID.'/footer.php',
		array(
			"SITE_DIR" => WIZARD_SITE_DIR,
		)
	);

	COption::SetOptionString("main", "wizard_template_id_extranet", WIZARD_TEMPLATE_ID);
}
else
{
	if (
		WIZARD_USE_SITE_LOGO
		&& (
			strpos(WIZARD_TEMPLATE_ID, "light") === 0
			|| WIZARD_TEMPLATE_ID == "bitrix24"
		)
	)
	{
		CheckDirPath(WIZARD_SITE_PATH."/include/");
		$rnd = substr(time(), -4);

		if($logo > 0)
		{
			$ff = CFile::GetByID($logo);
			if($zr = $ff->Fetch())
			{
				$strOldFile = str_replace("//", "/", $_SERVER["DOCUMENT_ROOT"]."/".(COption::GetOptionString("main", "upload_dir", "upload", WIZARD_SITE_ID))."/".$zr["SUBDIR"]."/".$zr["FILE_NAME"]);

				if (WIZARD_TEMPLATE_ID == "bitrix24")
				{
					$io = CBXVirtualIo::GetInstance();
					$strOldFile = $io->GetPhysicalName($strOldFile);
				}

				@copy($strOldFile, WIZARD_SITE_PATH."/include/logo.".$rnd.".jpg");
				___writeToAreasFile(WIZARD_SITE_PATH."/include/company_name.php", '<img src="'.WIZARD_SITE_DIR.'include/logo.'.$rnd.'.jpg"  />');
				CFile::Delete($logo);
			}
			COption::SetOptionString("main", "wizard_site_logo", WIZARD_SITE_LOGO, false, WIZARD_SITE_ID);
		}
	}
	elseif (!WIZARD_USE_SITE_LOGO)
	{
		COption::SetOptionString("main", "wizard_site_logo", "", false, WIZARD_SITE_ID);
		___writeToAreasFile(WIZARD_SITE_PATH."/include/company_name.php", COption::GetOptionString("main", "site_name", "Compamy Name", WIZARD_SITE_ID));
	}

	COption::SetOptionString("main", "wizard_template_id_extranet", "current_intranet_template");
}

//Attach template to  site
$obSite = CSite::GetList($by = "def", $order = "desc", Array("LID" => WIZARD_SITE_ID));
if ($arSite = $obSite->Fetch())
{
	$arTemplates = Array();
	$found = false;
	$foundEmpty = false;
	$allowGuests = "N";

	$obTemplate = CSite::GetTemplateList($arSite["LID"]);
	while($arTemplate = $obTemplate->Fetch())
	{
		if(!$found && strlen(trim($arTemplate["CONDITION"]))<=0)
		{
			$arTemplate["TEMPLATE"] = WIZARD_TEMPLATE_ID;
			$found = true;
		}
		if($arTemplate["TEMPLATE"] == "login")
		{
			$foundEmpty = true;
			if($allowGuests == "Y")
				continue;
		}
		$arTemplates[]= $arTemplate;
	}

	if (!$found)
	{
		$arTemplates[]= Array("CONDITION" => "", "SORT" => 150, "TEMPLATE" => WIZARD_TEMPLATE_ID);
	}

	if (
		!$foundEmpty 
		&& $allowGuests <> "Y"
	)
	{
		$arTemplates[]= Array("CONDITION" => "!\$GLOBALS['USER']->IsAuthorized() && (!isset(\$_SERVER['REMOTE_USER']) || strlen(\$_SERVER['REMOTE_USER']) <= 0)", "SORT" => 250, "TEMPLATE" => "login");
	}

	$current_template = "";
	$rsTemplate = CSite::GetTemplateList($arSite["LID"]);
	while($arTemplate = $rsTemplate->Fetch())
	{
		if(strlen(trim($arTemplate["CONDITION"]))<=0)
		{
			$current_template = $arTemplate["TEMPLATE"];
		}
	}

	if (WIZARD_TEMPLATE_ID === "bitrix24" && ($current_template !== "bitrix24" || WIZARD_B24_TO_CP))
	{
		CopyDirFiles(
			WIZARD_SITE_PATH."index.php",
			WIZARD_SITE_PATH."index_old.php",
			$rewrite = true,
			$recursive = true,
			$delete_after_copy = true
		);

		if (file_exists(WIZARD_SITE_PATH."index_b24.php"))
		{
			CopyDirFiles(
				WIZARD_SITE_PATH."index_b24.php",
				WIZARD_SITE_PATH."index.php",
				$rewrite = true,
				$recursive = true,
				$delete_after_copy = true
			);
		}
		else
		{	
			$path = WIZARD_SITE_PATH."contacts/personal.php";
			if (file_exists($path))
			{
				$fp = fopen($path, 'r');
				$contents = fread($fp, filesize($path));
				fclose($fp);
			}
			$sonet_user = preg_match('/\$APPLICATION->IncludeComponent\(\"bitrix:socialnetwork_user\".*?\);/si', $contents, $matches);

			$sonet_replace = $matches[0];

			CopyDirFiles(
				WIZARD_ABSOLUTE_PATH."/site/public/".LANGUAGE_ID."/index_b24.php",
				WIZARD_SITE_PATH."index.php",
				$rewrite = true,
				$recursive = true,
				$delete_after_copy = false
			);
			$path_index_b24 = WIZARD_SITE_PATH."index.php";
			if (file_exists($path_index_b24))
			{
				$fp = fopen($path_index_b24, 'r');
				$contents_b24 = fread($fp, filesize($path_index_b24));
				fclose($fp);
			}
			$contents_b24_new = preg_replace('/\$APPLICATION->IncludeComponent\(\"bitrix:socialnetwork_user\"[^;]+;/si', $sonet_replace, $contents_b24);
			$contents_b24_new = preg_replace('/#SITE_DIR#/si', WIZARD_SITE_DIR, $contents_b24_new);

			if ($contents_b24_new != $contents_b24)
			{
				$fp = fopen($path_index_b24, 'w');
				fwrite($fp, $contents_b24_new);
				fclose($fp);
			}			
		}
		
		if (file_exists(WIZARD_SITE_PATH.".top.menu_ext.php"))
		{
			CopyDirFiles(
				WIZARD_SITE_PATH.".top.menu_ext.php",
				WIZARD_SITE_PATH.".top.menu_ext.php.old",
				$rewrite = true,
				$recursive = true,
				$delete_after_copy = true
			);
		}

		CopyDirFiles(
			WIZARD_ABSOLUTE_PATH."/site/public/".LANGUAGE_ID."/.top.menu_ext.php",
			WIZARD_SITE_PATH.".top.menu_ext.php",
			$rewrite = true,
			$recursive = true,
			$delete_after_copy = false
		);

		if (!file_exists(WIZARD_SITE_PATH.".left.menu.php"))
		{
			CopyDirFiles(
				WIZARD_ABSOLUTE_PATH."/site/public/".LANGUAGE_ID."/.left.menu.php",
				WIZARD_SITE_PATH.".left.menu.php",
				$rewrite = true,
				$recursive = true,
				$delete_after_copy = false
			);
		}

		if (!file_exists(WIZARD_SITE_PATH.".left.menu_ext.php"))
		{
			CopyDirFiles(
				WIZARD_ABSOLUTE_PATH."/site/public/".LANGUAGE_ID."/.left.menu_ext.php",
				WIZARD_SITE_PATH.".left.menu_ext.php",
				$rewrite = true,
				$recursive = true,
				$delete_after_copy = false
			);
		}

		CopyDirFiles(
			WIZARD_ABSOLUTE_PATH."/site/public/".LANGUAGE_ID."/workgroups/.left.menu_ext.php",
			WIZARD_SITE_PATH."workgroups/.left.menu_ext.php",
			$rewrite = true,
			$recursive = true,
			$delete_after_copy = false
		);
	}
	elseif ($current_template === "bitrix24" && WIZARD_TEMPLATE_ID !== "bitrix24")
	{
		CopyDirFiles(
			WIZARD_SITE_PATH."index.php",
			WIZARD_SITE_PATH."index_b24.php",
			$rewrite = true,
			$recursive = true,
			$delete_after_copy = true
		);
		
		CopyDirFiles(
			WIZARD_SITE_PATH."index_old.php",
			WIZARD_SITE_PATH."index.php",
			$rewrite = true,
			$recursive = true,
			$delete_after_copy = true
		);

		if (file_exists(WIZARD_SITE_PATH.".top.menu_ext.php.old"))
			CopyDirFiles(
				WIZARD_SITE_PATH.".top.menu_ext.php.old",
				WIZARD_SITE_PATH.".top.menu_ext.php",
				$rewrite = true,
				$recursive = true,
				$delete_after_copy = true
			);
	}

	$arFields = Array(
		"TEMPLATE" => $arTemplates,
		"NAME" => $arSite["NAME"],
	);

	$obSite = new CSite();
	$obSite->Update($arSite["LID"], $arFields);
}
?>