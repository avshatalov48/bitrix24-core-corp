<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/extranet/classes/general/wizard_utils.php");

class WelcomeStep extends CWizardStep
{
	function InitStep()
	{
		$this->SetTitle(GetMessage("WELCOME_STEP_TITLE"));
		$this->SetStepID("welcome_step");

		$wizard =& $this->GetWizard();

		$templatesPath = CExtranetWizardServices::GetTemplatesPath($wizard->GetPath()."/site");
		$arTemplates = CExtranetWizardServices::GetTemplates($templatesPath);

		if (empty($arTemplates))
		{
			$rsDefSiteTemplates = CSite::GetTemplateList(CSite::GetDefSite());
			while ($arDefSiteTemplates = $rsDefSiteTemplates->Fetch())
				if ($arDefSiteTemplates["CONDITION"] == "")
				{
					$site_template = $arDefSiteTemplates["TEMPLATE"];
					break;
				}

			$wizard->SetVar("templateID", $site_template);
			$this->SetNextStep("site_settings");
		}
		else
		{
			$this->SetNextStep("select_template");
		}

		$this->SetNextCaption(GetMessage("NEXT_BUTTON"));
	}

	function ShowStep()
	{
		$wizard =& $this->GetWizard();
		if (strlen($wizard->GetVar("templateID")) > 0)
		{
			$this->content .= GetMessage("WELCOME_TEXT_SHORT");
		}
		else
		{
			$this->content .= GetMessage("WELCOME_TEXT");
		}
	}
}

class SelectTemplateStep extends CWizardStep
{
	function InitStep()
	{
		$this->SetStepID("select_template");
		$this->SetTitle(GetMessage("SELECT_TEMPLATE_TITLE"));
		$this->SetSubTitle(GetMessage("SELECT_TEMPLATE_SUBTITLE"));
		$this->SetPrevStep("welcome_step");
		$this->SetNextStep("select_theme");
		$this->SetNextCaption(GetMessage("NEXT_BUTTON"));
		$this->SetPrevCaption(GetMessage("PREVIOUS_BUTTON"));
	}

	function OnPostForm()
	{
		$wizard =& $this->GetWizard();

		if ($wizard->IsNextButtonClick())
		{
			$templatesPath = CExtranetWizardServices::GetTemplatesPath($wizard->GetPath()."/site");
			$arTemplates = CExtranetWizardServices::GetTemplates($templatesPath);

			$templateID = $wizard->GetVar("templateID");

			if ($templateID == "current_intranet_template")
				$wizard->SetCurrentStep("site_settings");
			elseif (!array_key_exists($templateID, $arTemplates))
				$this->SetError(GetMessage("wiz_template"));
		}
	}

	function ShowStep()
	{
		$wizard =& $this->GetWizard();

		$templatesPath = CExtranetWizardServices::GetTemplatesPath($wizard->GetPath()."/site");
		$arTemplates = CExtranetWizardServices::GetTemplates($templatesPath);

		if (empty($arTemplates))
			return;

		$defaultTemplateID = COption::GetOptionString("main", "wizard_template_id_extranet", "");
		if (strlen($defaultTemplateID) > 0 && 
			(
				array_key_exists($defaultTemplateID, $arTemplates)
				|| $defaultTemplateID == "current_intranet_template"
			)
		)
			$wizard->SetDefaultVar("templateID", $defaultTemplateID);
		else
		{
			$defaultTemplateID = "classic_extranet";
			$wizard->SetDefaultVar("templateID", $defaultTemplateID);
		}

		$this->content .= '<table width="100%" cellspacing="4" cellpadding="8">';

		foreach ($arTemplates as $templateID => $arTemplate)
		{
			if ($defaultTemplateID == "")
			{
				$defaultTemplateID = $templateID;
				$wizard->SetDefaultVar("templateID", $defaultTemplateID);
			}

			$this->content .= "<tr>";
			$this->content .= '<td>'.$this->ShowRadioField("templateID", $templateID, Array("id" => $templateID))."</td>";

			if ($arTemplate["SCREENSHOT"] && $arTemplate["PREVIEW"])
				$this->content .= '<td valign="top">'.CFile::Show2Images($arTemplate["PREVIEW"], $arTemplate["SCREENSHOT"], 150, 150, ' border="0"')."</td>";
			else
				$this->content .= '<td valign="top">'.CFile::ShowImage($arTemplate["SCREENSHOT"], 150, 150, ' border="0"', "", true)."</td>";

			$this->content .= '<td valign="top" width="100%"><label for="'.$templateID.'"><b>'.$arTemplate["NAME"]."</b><br />".$arTemplate["DESCRIPTION"]."</label></td>";

			$this->content .= "</tr>";
			$this->content .= "<tr><td><br /></td></tr>";
		}

		$this->content .= "<tr>";
		$this->content .= '<td>'.$this->ShowRadioField("templateID", "current_intranet_template", Array("id" => "current_intranet_template"))."</td>";
		$this->content .= '<td colspan="2" valign="top" width="100%"><label for="current_intranet_template"><b>'.GetMessage("wiz_cur_intranet_template_name")."</b><br />".GetMessage("wiz_cur_intranet_template_description")."</label></td>";

		$this->content .= "</tr>";
		$this->content .= "<tr><td><br /></td></tr>";

		$this->content .= "</table>";
	}
}


class SelectThemeStep extends CWizardStep
{
	function InitStep()
	{
		$this->SetStepID("select_theme");
		$this->SetTitle(GetMessage("SELECT_THEME_TITLE"));
		$this->SetSubTitle(GetMessage("SELECT_THEME_SUBTITLE"));
		$this->SetPrevStep("select_template");
		$this->SetNextStep("site_settings");
		$this->SetNextCaption(GetMessage("NEXT_BUTTON"));
		$this->SetPrevCaption(GetMessage("PREVIOUS_BUTTON"));
	}

	function OnPostForm()
	{
		$wizard =& $this->GetWizard();

		if ($wizard->IsNextButtonClick())
		{
			$templateID = $wizard->GetVar("templateID");
			$themeVarName = $templateID."_themeID";
			$themeID = $wizard->GetVar($themeVarName);

			$templatesPath = CExtranetWizardServices::GetTemplatesPath($wizard->GetPath()."/site");
			$arThemes = CExtranetWizardServices::GetThemes($templatesPath."/".$templateID."/themes");

			if (!array_key_exists($themeID, $arThemes))
				$this->SetError(GetMessage("wiz_template_color"));
		}
	}

	function ShowStep()
	{
		$wizard =& $this->GetWizard();
		$templateID = $wizard->GetVar("templateID");

		$templatesPath = CExtranetWizardServices::GetTemplatesPath($wizard->GetPath()."/site");
		$arThemes = CExtranetWizardServices::GetThemes($templatesPath."/".$templateID."/themes");

		if (empty($arThemes))
			return;

		$themeVarName = $templateID."_themeID";

		$defaultThemeID = COption::GetOptionString("main", "wizard_".$templateID."_theme_id_extranet", "");
		if (strlen($defaultThemeID) > 0 && array_key_exists($defaultThemeID, $arThemes))
			$wizard->SetDefaultVar($themeVarName, $defaultThemeID);
		else
		{
			$defaultThemeID = "red";
			$wizard->SetDefaultVar($themeVarName, $defaultThemeID);
		}

		$this->content .= '<table width="100%" cellspacing="4" cellpadding="8">';

		foreach ($arThemes as $themeID => $arTheme)
		{
			if ($defaultThemeID == "")
			{
				$defaultThemeID = $themeID;
				$wizard->SetDefaultVar($themeVarName, $defaultThemeID);
			}

			$this->content .= "<tr>";

			$this->content .= "<td>".$this->ShowRadioField($themeVarName, $themeID, Array("id" => $themeVarName."_".$themeID))."</td>";

			if ($arTheme["SCREENSHOT"] && $arTheme["PREVIEW"])
				$this->content .= '<td valign="top">'.CFile::Show2Images($arTheme["PREVIEW"], $arTheme["SCREENSHOT"], 150, 150, ' border="0"')."</td>";
			else
				$this->content .= '<td valign="top">'.CFile::ShowImage($arTheme["SCREENSHOT"], 150, 150, ' border="0"', "", true)."</td>";

			$this->content .= '<td valign="top" width="100%"><label for="'.$themeVarName."_".$themeID.'"><b>'.$arTheme["NAME"]."</b><br />".$arTheme["DESCRIPTION"]."</label></td>";

			$this->content .= "</tr>";
			$this->content .= "<tr><td><br /></td></tr>";
		}

		$this->content .= "</table>";


	}
}

class SiteSettingsStep extends CWizardStep
{
	function GetFileContentImgSrc($filename, $default_value)
	{
		if (
			file_exists($filename)
			&& ($siteLogo = file_get_contents($filename)) !== false
			&& strlen($siteLogo) > 0
		)
		{
			if (strpos($siteLogo, "default_logo") !== false)
			{
				$siteLogo = $default_value;
			}
			else if(preg_match("/src\s*=\s*(\S+)[ \t\r\n\/>]*/i", $siteLogo, $reg))
			{
				$siteLogo = "/".trim($reg[1], "\"' />");
			}
			else
			{
				$siteLogo = $default_value;
			}
		}
		else
		{
			$siteLogo = $default_value;
		}

		return $siteLogo;
	}

	function InitStep()
	{
		$this->SetStepID("site_settings");
		$this->SetTitle(GetMessage("wiz_settings"));
		$this->SetSubTitle(GetMessage("wiz_settings"));
		$this->SetNextStep("data_install");
		$this->SetPrevStep("select_theme");
		$this->SetNextCaption(GetMessage("wiz_install"));
		$this->SetPrevCaption(GetMessage("PREVIOUS_BUTTON"));

		$wizard =& $this->GetWizard();

		if (strlen(COption::GetOptionString("extranet", "extranet_site")) > 0)
		{
			$siteId = COption::GetOptionString("extranet", "extranet_site");
			$rsSites = CSite::GetList(
				$by="sort",
				$order="desc",
				array(
					"ID" => $siteId
				)
			);
			if ($arSite = $rsSites->Fetch())
			{
				$siteName = $arSite["NAME"];
				$siteFolder = $arSite["DIR"];
				$siteDocumentRoot = (!empty($arSite["DOC_ROOT"]) ? $arSite["DOC_ROOT"] : $_SERVER['DOCUMENT_ROOT']);
			}
		}
		else
		{
			$siteId = "co";
			$siteName = (
				strlen(COption::GetOptionString("main", "site_name")) > 0
					? COption::GetOptionString("main", "site_name").GetMessage("site_name_suffix")
					: $siteName = GetMessage("wiz_slogan")
			);
			$siteFolder = "/extranet/";
			$siteDocumentRoot = $_SERVER['DOCUMENT_ROOT'];
		}

		$siteLogo = $this->GetFileContentImgSrc($siteDocumentRoot.$siteFolder."include/company_name.php", false);
/*
		if (!$siteLogo)
		{
			$defaultSiteId = CSite::GetDefSite();

			$rsSites = CSite::GetByID($defaultSiteId);
			if ($arSite = $rsSites->Fetch())
			{
				$defaultSiteFolder = $arSite["DIR"];
				$defaultSiteDocRoot = $arSite["DOC_ROOT"];
			}

			if (empty($defaultSiteFolder))
			{
				$defaultSiteFolder = "/";
			}

			if (empty($defaultSiteDocRoot))
			{
				$defaultSiteDocRoot = $_SERVER['DOCUMENT_ROOT'];
			}

			$rsDefSiteTemplates = CSite::GetTemplateList($defaultSiteId);
			while ($arDefSiteTemplates = $rsDefSiteTemplates->Fetch())
			{
				if ($arDefSiteTemplates["CONDITION"] == "")
				{
					$defaultTemplateId = $arDefSiteTemplates["TEMPLATE"];

					if(strpos($defaultTemplateId, "light") === 0)
					{
						$defaultThemeId = COption::GetOptionString("main", "wizard_light_theme_id", false, $defaultSiteId);
						if ($defaultThemeId)
						{
							$siteLogo = $this->GetFileContentImgSrc($defaultSiteDocRoot.$defaultSiteFolder."include/company_name.php",
								"/bitrix/wizards/bitrix/portal/images/templates/light/themes/".$defaultThemeId."/images/".LANGUAGE_ID."/logo.jpg"
							);
						}
					}
					elseif ($defaultTemplateId == "bitrix24")
					{
						$siteLogo = $this->GetFileContentImgSrc($defaultSiteDocRoot.$defaultSiteFolder."include/company_name.php", false);
					}
					else
					{
						$siteLogo = COption::GetOptionString("main", "wizard_site_logo", false, $defaultSiteId);
					}

					break;
				}
			}
		}
*/

		$wizard->SetDefaultVars(
			Array(
				"siteLogo" => $siteLogo,
				"useSiteLogo" => COption::GetOptionString("main", "wizard_use_site_logo_extranet", "Y", $siteId),
				"siteName" => $siteName,
				"siteID" => $siteId,
				"siteFolder" => $siteFolder,
			)
		);

	}

	function OnPostForm()
	{
		$wizard =& $this->GetWizard();

		if (strpos($wizard->GetVar("templateID"), "light") === 0)
		{
			$this->SaveFile("siteLogo", Array("extensions" => "gif,jpg,jpeg,png", "max_height" => 72, "max_width" => 285, "make_preview" => "Y"));
		}
		elseif ($wizard->GetVar("templateID") == "bitrix24")
		{
			$this->SaveFile("siteLogo", Array("extensions" => "gif,jpg,jpeg,png", "max_height" => 55, "max_width" => 222, "make_preview" => "Y"));
		}
		else
		{
			$this->SaveFile("siteLogo", Array("extensions" => "gif,jpg,jpeg,png", "max_height" => 80, "max_width" => 90, "make_preview" => "Y"));
		}

		if (strlen(COption::GetOptionString("extranet", "extranet_site")) > 0)
		{
			COption::SetOptionString("main", "wizard_extranet_rerun", "Y");
			define("WIZARD_IS_RERUN", true);
		}
		else
		{
			COption::SetOptionString("main", "wizard_extranet_rerun", "N");
			define("WIZARD_IS_RERUN", false);
		}

		define("WIZARD_IS_RERUN", strlen(COption::GetOptionString("extranet", "extranet_site")) > 0);
		
		if ($wizard->IsNextButtonClick() && WIZARD_IS_RERUN !== true)
		{
			$siteID = $wizard->GetVar("siteID");
			$siteFolder = $wizard->GetVar("siteFolder");
			$siteName = $wizard->GetVar("siteName");

			if (strlen($siteID) != 2)
			{
				$this->SetError(GetMessage("wiz_site_id_error"));
				return;
			}
			elseif (strlen(trim($siteFolder, " /")) == 0)
			{
				$this->SetError(GetMessage("wiz_site_folder_error"));	
				return;
			}
			else
			{
				$rsSites = CSite::GetList($by="sort", $order="desc", array());
				while($arSite = $rsSites->Fetch())
				{
					if (trim($arSite["DIR"], "/") == trim($siteFolder, "/"))
					{
						$this->SetError(GetMessage("wiz_site_folder_already_exists"));
						$bError = true;
					}

					if ($arSite["ID"] == trim($siteID))
					{
						$this->SetError(GetMessage("wiz_site_id_already_exists"));
						$bError = true;
					}
				}

				if (!$bError)
				{
					COption::SetOptionString("main", "wizard_site_code_extranet", $siteID);
					COption::SetOptionString("main", "wizard_site_folder_extranet", $siteFolder);
					COption::SetOptionString("main", "wizard_site_name_extranet", $siteName);

					$useSiteLogo = $wizard->GetVar("useSiteLogo");
					COption::SetOptionString("main", "wizard_use_site_logo_extranet", $useSiteLogo == "Y" ? "Y" : "N", false, $siteID);
				}
				else
				{
					return;
				}
			}
		}
		elseif ($wizard->IsNextButtonClick())
		{
			$siteName = $wizard->GetVar("siteName", true);
			$siteID = $wizard->GetVar("siteID", true);
			$useSiteLogo = $wizard->GetVar("useSiteLogo");

			COption::SetOptionString("main", "wizard_site_name_extranet", $siteName);
			COption::SetOptionString("main", "site_name", $siteName, false, $siteID);
			COption::SetOptionString("main", "wizard_use_site_logo_extranet", $useSiteLogo == "Y" ? "Y" : "N", false, $siteID);
		}
	}

	function ShowStep()
	{
		$wizard =& $this->GetWizard();

		$this->content .= '<table width="100%" cellspacing="0" cellpadding="0">';

		$this->content .= '<tr><td>';
		$this->content .= '<label for="site-name">'.GetMessage("wiz_company_name").'</label><br />';
		$this->content .= $this->ShowInputField("text", "siteName", Array("id" => "site-name", "style" => "width:90%"));
		$this->content .= '</td></tr>';

		$this->content .= '<tr><td><br /></td></tr>';

		$templateID = $wizard->GetVar("templateID");

		if (in_array($templateID, array("bright_extranet", "classic_extranet", "modern_extranet")))
		{
			$fileID = COption::GetOptionString("main", "wizard_site_logo_extranet", "");
			if (intval($fileID) > 0)
			{
				$wizard->SetVar("siteLogo", $fileID);
			}
			$siteLogo = $wizard->GetVar("siteLogo");

			$this->content .= '<tr><td>';
			$this->content .= '<label for="site-logo">'.GetMessage("wiz_company_logo").'</label><br />';
			$this->content .= $this->ShowFileField("siteLogo", Array("show_file_info" => "N", "id" => "site-logo"));
			$this->content .= "<br />".CFile::ShowImage($siteLogo, 200, 200, "border=0", "", true);
			$this->content .= '</td></tr>';

			$this->content .= '<tr><td>&nbsp;</td></tr>';
		}
		elseif (
			strpos($templateID, "light") === 0
			|| $templateID == "bitrix24"
		)
		{
			$siteLogo = $wizard->GetVar("siteLogo", true);

			$this->content .= '<tr><td>';

			$this->content .= <<<JS
				<script type="text/javascript">
						function OnSiteLogoClick(checked)
						{
								var siteLogoImage = document.getElementById("site-logo-image");
								var siteLogoUpload = document.getElementById("site-logo-upload");

								siteLogoUpload.disabled = !checked;

								if (siteLogoImage)
									siteLogoImage.className = checked ? "" : "disabled";
						}
				</script>
JS;

			$this->content .= $this->ShowCheckboxField("useSiteLogo", "Y", Array("id" => "use-site-logo", "onclick" => "OnSiteLogoClick(this.checked)"));
			$this->content .= ' <label for="use-site-logo">'.GetMessage("wiz_company_logo").'</label>';

			$this->content .= (
				$siteLogo
					? "<div style='margin: 5px 0 5px 0;'>".CFile::ShowImage($siteLogo, 0, 0, "border=0 id=\"site-logo-image\"".($wizard->GetVar("useSiteLogo", true) != "Y" ? " class=\"disabled\"" : ""), "", true)."</div>"
					: "<br />"
			);

			$arParams = Array("id" => "site-name", "style" => "width:90%", "id" => "site-logo-upload", "show_file_info" => "N");
			if ($wizard->GetVar("useSiteLogo", true) != "Y")
				$arParams["disabled"] = "disabled";

			$this->content .= $this->ShowFileField("siteLogo", $arParams);
			$this->content .= '</td></tr>';
		}

		define("WIZARD_IS_RERUN", strlen(COption::GetOptionString("extranet", "extranet_site")) > 0);
		
		if(WIZARD_IS_RERUN !== true)
		{
		
			$this->content .= '<tr><td><br /></td></tr>';

			$this->content .= '<tr><td>';
			$this->content .= '<label for="site-id">'.GetMessage("wiz_site_id").'</label><br />';
			$this->content .= $this->ShowInputField("text", "siteID", Array("id" => "site-id", "style" => "width:25px"));
			$this->content .= '</td></tr>';

			$this->content .= '<tr><td><br /></td></tr>';

			$this->content .= '<tr><td>';
			$this->content .= '<label for="site-folder">'.GetMessage("wiz_site_folder").'</label><br />';
			$this->content .= $this->ShowInputField("text", "siteFolder", Array("id" => "site-folder", "style" => "width:90%"));
			$this->content .= '</td></tr>';
		
		}

		$this->content .= '</table>';


		$formName = $wizard->GetFormName();
		$installCaption = $this->GetNextCaption();
		$nextCaption = GetMessage("NEXT_BUTTON");

	}
}


class DataInstallStep extends CWizardStep
{
	function InitStep()
	{
		$this->SetStepID("data_install");
		$this->SetTitle(GetMessage("wiz_install_data"));
		$this->SetSubTitle(GetMessage("wiz_install_data"));
	}

	function OnPostForm()
	{
		$wizard =& $this->GetWizard();
		$serviceID = $wizard->GetVar("nextStep");
		$serviceStage = $wizard->GetVar("nextStepStage");

		if ($serviceID == "finish")
		{
			$wizard->SetCurrentStep("finish");
			return;
		}

		$arServices = CExtranetWizardServices::GetServices($_SERVER["DOCUMENT_ROOT"].$wizard->GetPath(), "/site/services/");

		if (COption::GetOptionString("main", "wizard_extranet_rerun") == "Y")
			define("WIZARD_IS_RERUN", true);

		if(WIZARD_IS_RERUN === true)
		{
			$rsSites = CSite::GetByID(COption::GetOptionString("extranet", "extranet_site"));
			if ($arSite = $rsSites->Fetch())
				define("WIZARD_SITE_PATH", $_SERVER["DOCUMENT_ROOT"].$arSite["DIR"]);
				
			if(
				$wizard->GetVar("installStructureData") != "Y"
				&& !file_exists(WIZARD_SITE_PATH.".superleft.menu.php")
			)
			{
				$s = Array();
				foreach($arServices["main"]["STAGES"] as $v)
					if(!in_array($v, array("groups.php", "property.php", "options.php", "events.php")))
						$s[] = $v;
				$arServices["main"]["STAGES"] = $s;

				unset($arServices["blog"]);
				unset($arServices["fileman"]);
				unset($arServices["files"]);
				unset($arServices["forum"]);
				unset($arServices["intranet"]);
				unset($arServices["socialnetwork"]);
				unset($arServices["statistic"]);
				unset($arServices["workflow"]);
			}
		}

		if ($serviceStage == "skip")
			$success = true;
		else
			$success = $this->InstallService($serviceID, $serviceStage);

		list($nextService, $nextServiceStage, $stepsComplete, $status) = $this->GetNextStep($arServices, $serviceID, $serviceStage);

		if ($nextService == "finish")
		{
			$formName = $wizard->GetFormName();
			$response = "window.ajaxForm.StopAjax(); window.ajaxForm.SetStatus('100'); window.ajaxForm.Post('".$nextService."', '".$nextServiceStage."','".$status."');";
		}
		else
		{
			$arServiceID = array_keys($arServices);
			$lastService = array_pop($arServiceID);
			$stepsCount = $arServices[$lastService]["POSITION"];
			if (array_key_exists("STAGES", $arServices[$lastService]) && is_array($arServices[$lastService]))
				$stepsCount += count($arServices[$lastService]["STAGES"])-1;

			$percent = round($stepsComplete/$stepsCount * 100);
			$response = "window.ajaxForm.SetStatus('".$percent."'); window.ajaxForm.Post('".$nextService."', '".$nextServiceStage."','".$status."');";
		}

		die("[response]".$response."[/response]");
	}

	function ShowStep()
	{
		$wizard =& $this->GetWizard();

		$arServices = CExtranetWizardServices::GetServices($_SERVER["DOCUMENT_ROOT"].$wizard->GetPath(), "/site/services/");

		list($firstService, $stage, $status) = $this->GetFirstStep($arServices);

		$this->content .= '
		<div class="instal-load-block" id="result">
			<div class="instal-load-label" id="status"></div>
			<div class="instal-progress-bar-outer" style="width: 670px;">
				<div class="instal-progress-bar-alignment">
					<div class="instal-progress-bar-inner" id="indicator">
						<div class="instal-progress-bar-inner-text" style="width: 670px;" id="percent"></div>
					</div>
					<span id="percent2">0%</span>
				</div>
			</div>
		</div>
		<div id="error_container" style="display:none">
			<div id="error_notice">
				<div class="inst-note-block inst-note-block-red">
					<div class="inst-note-block-icon"></div>
					<div class="inst-note-block-label">'.GetMessage("INST_ERROR_OCCURED").'</div><br />
					<div class="inst-note-block-text">'.GetMessage("INST_ERROR_NOTICE").'<div id="error_text"></div></div>
				</div>
			</div>

			<div id="error_buttons" align="center">
			<br /><input type="button" value="'.GetMessage("INST_RETRY_BUTTON").'" id="error_retry_button" onclick="" class="instal-btn instal-btn-inp" />&nbsp;<input type="button" id="error_skip_button" value="'.GetMessage("INST_SKIP_BUTTON").'" onclick="" class="instal-btn instal-btn-inp" />&nbsp;</div>
		</div>

		'.$this->ShowHiddenField("nextStep", $firstService).'
		'.$this->ShowHiddenField("nextStepStage", $stage).'
		<iframe style="display:none;" id="iframe-post-form" name="iframe-post-form" src="javascript:\'\'"></iframe>
		';

		$wizard =& $this->GetWizard();

		$formName = $wizard->GetFormName();
		$NextStepVarName = $wizard->GetRealName("nextStep");


		$this->content .= '
		<script type="text/javascript">
			var ajaxForm = new CAjaxForm("'.$formName.'", "iframe-post-form", "'.$NextStepVarName.'");
			ajaxForm.Post("'.$firstService.'", "'.$stage.'", "'.$status.'");
		</script>';
	}

	function InstallService($serviceID, $serviceStage)
	{

		$wizard =& $this->GetWizard();

		$siteID =  COption::GetOptionString("main", "wizard_site_code_extranet");
		$siteFolder =  COption::GetOptionString("main", "wizard_site_folder_extranet");
		$siteName =  COption::GetOptionString("main", "wizard_site_name_extranet");

		if (COption::GetOptionString("main", "wizard_extranet_rerun") == "Y")
			define("WIZARD_IS_RERUN", true);
		
		if (WIZARD_IS_RERUN === true)
		{
			$rsSites = CSite::GetByID(COption::GetOptionString("extranet", "extranet_site"));
			if ($arSite = $rsSites->Fetch())
			{
				define("WIZARD_SITE_ID", $arSite["ID"]);
				define("WIZARD_SITE_DIR", $arSite["DIR"]);				
				define("WIZARD_SITE_NAME", $siteName);				
				define("WIZARD_SITE_PATH", $_SERVER["DOCUMENT_ROOT"].$arSite["DIR"]);
				define("WIZARD_SITE_LOGO", intval($wizard->GetVar("siteLogo")));
				define("WIZARD_USE_SITE_LOGO", $wizard->GetVar("useSiteLogo") == "Y");
				$bFound = true;
			}
		}

		if (!$bFound)
		{
			define("WIZARD_SITE_ID", $siteID);
			define("WIZARD_SITE_DIR", $siteFolder);
			define("WIZARD_SITE_NAME", $siteName);			
			define("WIZARD_SITE_PATH", $_SERVER["DOCUMENT_ROOT"].$siteFolder);
			define("WIZARD_SITE_LOGO", intval($wizard->GetVar("siteLogo")));
			define("WIZARD_USE_SITE_LOGO", $wizard->GetVar("useSiteLogo") == "Y");
		}

		$wizardPath = $wizard->GetPath();
		define("WIZARD_RELATIVE_PATH", $wizardPath);
		define("WIZARD_ABSOLUTE_PATH", $_SERVER["DOCUMENT_ROOT"].$wizardPath);

		$templatesPath = "";

		$templatesPath = CExtranetWizardServices::GetTemplatesPath(WIZARD_RELATIVE_PATH."/site");
		$arTemplates = CExtranetWizardServices::GetTemplates($templatesPath);

		$templateID = $wizard->GetVar("templateID");
		if ($templateID == "current_intranet_template")
		{
			$rsDefSiteTemplates = CSite::GetTemplateList(CSite::GetDefSite());
			while ($arDefSiteTemplates = $rsDefSiteTemplates->Fetch())
				if ($arDefSiteTemplates["CONDITION"] == "")
				{
					$templateID = $arDefSiteTemplates["TEMPLATE"];
					break;
				}
		}

		define("WIZARD_TEMPLATE_ID", $templateID);
		if (in_array($templateID, array("bright_extranet", "classic_extranet", "modern_extranet")))
		{
			define("WIZARD_TEMPLATE_RELATIVE_PATH", $templatesPath."/".WIZARD_TEMPLATE_ID);
			define("WIZARD_TEMPLATE_ABSOLUTE_PATH", $_SERVER["DOCUMENT_ROOT"].WIZARD_TEMPLATE_RELATIVE_PATH);
			$themeID = $wizard->GetVar($templateID."_themeID");

			$arThemes = CExtranetWizardServices::GetThemes(WIZARD_TEMPLATE_RELATIVE_PATH."/themes");

			define("WIZARD_THEME_ID", $themeID);
			define("WIZARD_THEME_RELATIVE_PATH", WIZARD_TEMPLATE_RELATIVE_PATH."/themes/".WIZARD_THEME_ID);
			define("WIZARD_THEME_ABSOLUTE_PATH", $_SERVER["DOCUMENT_ROOT"].WIZARD_THEME_RELATIVE_PATH);
		}

		$servicePath = WIZARD_RELATIVE_PATH."/site/services/".$serviceID;
		define("WIZARD_SERVICE_RELATIVE_PATH", $servicePath);
		define("WIZARD_SERVICE_ABSOLUTE_PATH", $_SERVER["DOCUMENT_ROOT"].$servicePath);
		
		$b24ToCp = file_exists(WIZARD_SITE_PATH.".superleft.menu.php") ? true : false;
		define("WIZARD_B24_TO_CP", $b24ToCp);
		
		define("WIZARD_SITE_LOGO", intval($wizard->GetVar("siteLogo")));

		if (!file_exists(WIZARD_SERVICE_ABSOLUTE_PATH."/".$serviceStage))
			return false;

		if (LANGUAGE_ID != "en" && LANGUAGE_ID != "ru")
		{
			if (file_exists(WIZARD_SERVICE_ABSOLUTE_PATH."/lang/en/".$serviceStage))
				__IncludeLang(WIZARD_SERVICE_ABSOLUTE_PATH."/lang/en/".$serviceStage);
		}

		$dbGroups = CGroup::GetList($by="id", $order="asc", Array("ACTIVE" => "Y"));
		while($arGroup = $dbGroups->Fetch())
			define("WIZARD_".$arGroup["STRING_ID"]."_GROUP", $arGroup["ID"]);

		if (file_exists(WIZARD_SERVICE_ABSOLUTE_PATH."/lang/".LANGUAGE_ID."/".$serviceStage))
			__IncludeLang(WIZARD_SERVICE_ABSOLUTE_PATH."/lang/".LANGUAGE_ID."/".$serviceStage);

		@set_time_limit(3600);
		global $DB, $DBType, $APPLICATION, $USER, $CACHE_MANAGER;

		include(WIZARD_SERVICE_ABSOLUTE_PATH."/".$serviceStage);
	}

	function GetNextStep(&$arServices, &$currentService, &$currentStage)
	{
		$nextService = "finish";
		$nextServiceStage = "finish";
		$status = GetMessage("INSTALL_SERVICE_FINISH_STATUS");

		if (!array_key_exists($currentService, $arServices))
			return Array($nextService, $nextServiceStage, 0, $status); //Finish

		if ($currentStage != "skip" && array_key_exists("STAGES", $arServices[$currentService]) && is_array($arServices[$currentService]["STAGES"]))
		{
			$stageIndex = array_search($currentStage, $arServices[$currentService]["STAGES"]);
			if ($stageIndex !== false && isset($arServices[$currentService]["STAGES"][$stageIndex+1]))
				return Array(
					$currentService,
					$arServices[$currentService]["STAGES"][$stageIndex+1],
					$arServices[$currentService]["POSITION"]+ $stageIndex,
					$arServices[$currentService]["NAME"]
				); //Current step, next stage
		}

		$arServiceID = array_keys($arServices);
		$serviceIndex = array_search($currentService, $arServiceID);

		if (!isset($arServiceID[$serviceIndex+1]))
			return Array($nextService, $nextServiceStage, 0, $status); //Finish

		$nextServiceID = $arServiceID[$serviceIndex+1];
		$nextServiceStage = "index.php";
		if (array_key_exists("STAGES", $arServices[$nextServiceID]) && is_array($arServices[$nextServiceID]["STAGES"]) && isset($arServices[$nextServiceID]["STAGES"][0]))
			$nextServiceStage = $arServices[$nextServiceID]["STAGES"][0];

		return Array($nextServiceID, $nextServiceStage, $arServices[$nextServiceID]["POSITION"]-1, $arServices[$nextServiceID]["NAME"]); //Next service
	}

	function GetFirstStep(&$arServices)
	{
		foreach ($arServices as $serviceID => $arService)
		{
			$stage = "index.php";
			if (array_key_exists("STAGES", $arService) && is_array($arService["STAGES"]) && isset($arService["STAGES"][0]))
				$stage = $arService["STAGES"][0];
			return Array($serviceID, $stage, $arService["NAME"]);
		}

		return Array("service_not_found", "finish", GetMessage("INSTALL_SERVICE_FINISH_STATUS"));
	}
}

class FinishStep extends CWizardStep
{
	function InitStep()
	{
		$this->SetStepID("finish");
		$this->SetNextStep("finish");
		$this->SetTitle(GetMessage("FINISH_STEP_TITLE"));
		$this->SetNextCaption(GetMessage("wiz_go"));
	}

	function ShowStep()
	{
		$DefaultSiteID = CSite::GetDefSite();
		if (strlen($DefaultSiteID) > 0)
		{
			$rsSites = CSite::GetByID($DefaultSiteID);
			if ($arSite = $rsSites->Fetch())
			{
				$dir = $arSite["DIR"];
			}
		}
		
		if (strlen($dir) <= 0)
			$dir = "/";
	
		$wizard =& $this->GetWizard();
		$wizard->SetFormActionScript($dir);

		if ( file_exists($_SERVER["DOCUMENT_ROOT"].$dir.".superleft.menu.php"))
		{
			DeleteDirFilesEx($dir.".superleft.menu.php");
		}
		if ( file_exists($_SERVER["DOCUMENT_ROOT"].$dir.".superleft.menu_ext.php"))
		{
			DeleteDirFilesEx($dir.".superleft.menu_ext.php");
		}
		
		$this->content .= GetMessage("FINISH_STEP_CONTENT");
	}
}
?>