<?
require_once("scripts/utils.php");

class DataInstallStep extends CWizardStep
{
	function InitStep()
	{
		$this->SetStepID("data_install");
		$this->SetTitle(GetMessage("wiz_install_data"));
		$this->SetSubTitle(GetMessage("wiz_install_data"));
		
		$wizard =& $this->GetWizard();
		$wizard->SetVar("siteID", 's1');
	}

	function OnPostForm()
	{
		$wizard =& $this->GetWizard();
		$serviceID = $wizard->GetVar("nextStep");
		$serviceStage = $wizard->GetVar("nextStepStage");

		if ($serviceID == "finish")
		{			
			if (IsModuleInstalled("bitrix24"))
			{
				$wizard->SetCurrentStep("data_install_extranet");
			}
			else
			{
				if (!COption::GetOptionString("main", "wizard_is_installed"))
					COption::SetOptionString("main", "wizard_is_installed", "Y");

				$wizard->SetCurrentStep("finish");
			}

			return;
		}
		
		$arServices = WizardServices::GetServices($_SERVER["DOCUMENT_ROOT"].$wizard->GetPath(), "/site/services/");
				
		if ($serviceStage == "skip")
			$success = true;
		else
			$success = $this->InstallService($serviceID, $serviceStage);

		list($nextService, $nextServiceStage, $stepsComplete, $status) = $this->GetNextStep($arServices, $serviceID, $serviceStage);


		if ($nextService == "finish")
		{			
			$formName = $wizard->GetFormName();
			$response = "window.ajaxForm.StopAjax(); window.ajaxForm.SetStatus('100'); window.ajaxForm.Post('".$nextService."', '".$nextServiceStage."','".$status."');";
			COption::SetOptionString("main", "wizard_first" . substr($wizard->GetID(), 7)  . "_" . $wizard->GetVar("siteID"), "Y", false, $siteID); 
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

		$arServices = WizardServices::GetServices($_SERVER["DOCUMENT_ROOT"].$wizard->GetPath(), "/site/services/");

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

		'.$this->ShowHiddenField("nextStep", "main").'
		'.$this->ShowHiddenField("nextStepStage", "database").'
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

		$siteID = WizardServices::GetCurrentSiteID($wizard->GetVar("siteID"));
		define("WIZARD_SITE_ID", $siteID);
		define("WIZARD_SITE_ROOT_PATH", $_SERVER["DOCUMENT_ROOT"]);
		
		$rsSites = CSite::GetByID($siteID);
		if ($arSite = $rsSites->Fetch())
			define("WIZARD_SITE_DIR", $arSite["DIR"]);
		else
			define("WIZARD_SITE_DIR", "/");
		
		define("WIZARD_SITE_PATH", str_replace("//", "/", WIZARD_SITE_ROOT_PATH."/".WIZARD_SITE_DIR."/"));

		$wizardPath = $wizard->GetPath();
		define("WIZARD_RELATIVE_PATH", $wizardPath);
		define("WIZARD_ABSOLUTE_PATH", $_SERVER["DOCUMENT_ROOT"].$wizardPath);

		$templatesPath = WizardServices::GetTemplatesPath(WIZARD_RELATIVE_PATH."/site");
		$arTemplates = WizardServices::GetTemplates($templatesPath);
		define("WIZARD_TEMPLATE_ID", "bitrix24");
		define("WIZARD_TEMPLATE_RELATIVE_PATH", $templatesPath."/".WIZARD_TEMPLATE_ID);
		define("WIZARD_TEMPLATE_ABSOLUTE_PATH", $_SERVER["DOCUMENT_ROOT"].WIZARD_TEMPLATE_RELATIVE_PATH);

		$servicePath = WIZARD_RELATIVE_PATH."/site/services/".$serviceID;
		define("WIZARD_SERVICE_RELATIVE_PATH", $servicePath);
		define("WIZARD_SERVICE_ABSOLUTE_PATH", $_SERVER["DOCUMENT_ROOT"].$servicePath);
		define("WIZARD_IS_INSTALLED", COption::GetOptionString("main", "wizard_is_installed") == "Y");
		define("WIZARD_SITE_NAME", GetMessage("wiz_slogan")/*$wizard->GetVar("siteName")*/);
		
		/*if($firstStep == "N" || wizard->GetVar("installDemoData") == "Y")
		{
			COption::SetOptionString("main", "wizard_clear_exec", "N", "", $siteID); 
		}*/
		
		$dbGroupUsers = CGroup::GetList($by="id", $order="asc", Array("ACTIVE" => "Y"));
		$arGroupsId = Array("ADMIN_SECTION", "SUPPORT", "CREATE_GROUPS", "PERSONNEL_DEPARTMENT", "DIRECTION", "MARKETING_AND_SALES");
	
	
		while($arGroupUser = $dbGroupUsers->Fetch()){
	
			if(in_array($arGroupUser["STRING_ID"], $arGroupsId))
			{
				define("WIZARD_".$arGroupUser["STRING_ID"]."_GROUP", $arGroupUser["ID"]);
			}
			else
			{
				if(substr($arGroupUser["STRING_ID"], -2) == $wizard->GetVar("siteID"))
				{
					define("WIZARD_".substr($arGroupUser["STRING_ID"], 0, -3)."_GROUP", $arGroupUser["ID"]);
				}
			}
		}
		
		if (!file_exists(WIZARD_SERVICE_ABSOLUTE_PATH."/".$serviceStage))
			return false;

		if (LANGUAGE_ID != "en" && LANGUAGE_ID != "ru")
		{
			if (file_exists(WIZARD_SERVICE_ABSOLUTE_PATH."/lang/en/".$serviceStage))
				__IncludeLang(WIZARD_SERVICE_ABSOLUTE_PATH."/lang/en/".$serviceStage);
		}

		if (file_exists(WIZARD_SERVICE_ABSOLUTE_PATH."/lang/".LANGUAGE_ID."/".$serviceStage))
			__IncludeLang(WIZARD_SERVICE_ABSOLUTE_PATH."/lang/".LANGUAGE_ID."/".$serviceStage);


		@set_time_limit(3600);
		global $DB, $DBType, $APPLICATION, $USER, $CACHE_MANAGER, $WIZARD_SERVICE_ABSOLUTE_PATH, $WIZARD_SERVICE_RELATIVE_PATH;

		$WIZARD_SERVICE_RELATIVE_PATH = WIZARD_SERVICE_RELATIVE_PATH;
		$WIZARD_SERVICE_ABSOLUTE_PATH = WIZARD_SERVICE_ABSOLUTE_PATH;

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

class DataInstallExtranetStep extends CWizardStep
{
		function InitStep()
	{
		$this->SetStepID("data_install_extranet");
		$this->SetTitle(GetMessage("wiz_install_data_extranet"));
		$this->SetSubTitle(GetMessage("wiz_install_data_extranet"));
		
		COption::SetOptionString("main", "wizard_site_folder_extranet", "/extranet/");
		COption::SetOptionString("main", "wizard_site_code_extranet", "ex");
		COption::SetOptionString("main", "wizard_site_name_extranet", "Ёкстранет");
	}

	function OnPostForm()
	{
		$wizard =& $this->GetWizard();
		$serviceID = $wizard->GetVar("nextStep");
		$serviceStage = $wizard->GetVar("nextStepStage");

		if ($serviceID == "finish")
		{
			if (!COption::GetOptionString("main", "wizard_is_installed"))
				COption::SetOptionString("main", "wizard_is_installed", "Y");
				
			$wizard->SetCurrentStep("finish");
			return;
		}

		$arServices = WizardServices::GetServices($_SERVER["DOCUMENT_ROOT"].$wizard->GetPath(), "/site/services_ex/");

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

		$arServices = WizardServices::GetServices($_SERVER["DOCUMENT_ROOT"].$wizard->GetPath(), "/site/services_ex/");

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

		'.$this->ShowHiddenField("nextStep", "main").'
		'.$this->ShowHiddenField("nextStepStage", "database").'
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


		define("WIZARD_SITE_ID", $siteID);
		define("WIZARD_SITE_DIR", $siteFolder);
		define("WIZARD_SITE_NAME", $siteName);			
		define("WIZARD_SITE_PATH", $_SERVER["DOCUMENT_ROOT"].$siteFolder);

		$wizardPath = $wizard->GetPath();
		define("WIZARD_RELATIVE_PATH", $wizardPath);
		define("WIZARD_ABSOLUTE_PATH", $_SERVER["DOCUMENT_ROOT"].$wizardPath);

		$templatesPath = WizardServices::GetTemplatesPath(WIZARD_RELATIVE_PATH."/site");

		define("WIZARD_TEMPLATE_ID", "bitrix24");
		define("WIZARD_TEMPLATE_RELATIVE_PATH", $templatesPath."/".WIZARD_TEMPLATE_ID);
		define("WIZARD_TEMPLATE_ABSOLUTE_PATH", $_SERVER["DOCUMENT_ROOT"].WIZARD_TEMPLATE_RELATIVE_PATH);

		$servicePath = WIZARD_RELATIVE_PATH."/site/services_ex/".$serviceID;
		define("WIZARD_SERVICE_RELATIVE_PATH", $servicePath);
		define("WIZARD_SERVICE_ABSOLUTE_PATH", $_SERVER["DOCUMENT_ROOT"].$servicePath);
		define("WIZARD_IS_INSTALLED", COption::GetOptionString("main", "wizard_is_installed") == "Y");
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
		global $DB, $DBType, $APPLICATION, $USER, $CACHE_MANAGER, $WIZARD_SERVICE_ABSOLUTE_PATH, $WIZARD_SERVICE_RELATIVE_PATH;

		$WIZARD_SERVICE_RELATIVE_PATH = WIZARD_SERVICE_RELATIVE_PATH;
		$WIZARD_SERVICE_ABSOLUTE_PATH = WIZARD_SERVICE_ABSOLUTE_PATH;

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
		$wizard =& $this->GetWizard();
		
		$siteID = WizardServices::GetCurrentSiteID($wizard->GetVar("siteID"));
		$rsSites = CSite::GetByID($siteID);
		$siteDir = "/"; 
		if ($arSite = $rsSites->Fetch())
			$siteDir = $arSite["DIR"];
			 
		$wizard->SetFormActionScript(str_replace("//", "/", $siteDir."/?finish"));

		if (!IsModuleInstalled("bitrix24"))
		{
			$arEditions = array("Portal", "Communications", "Enterprise");
			CBXFeatures::InitiateEditionsSettings($arEditions);
		}
		
		$this->CreateNewIndex();
		$this->content .= GetMessage("FINISH_STEP_CONTENT");
	}

	function CreateNewIndex()
	{
		$wizard =& $this->GetWizard();
		$siteID = WizardServices::GetCurrentSiteID($wizard->GetVar("siteID"));
		
		define("WIZARD_SITE_ID", $siteID);
		define("WIZARD_SITE_ROOT_PATH", $_SERVER["DOCUMENT_ROOT"]);

		$rsSites = CSite::GetByID($siteID);
		if ($arSite = $rsSites->Fetch())
			define("WIZARD_SITE_DIR", $arSite["DIR"]);
		else
			define("WIZARD_SITE_DIR", "/");
			
		define("WIZARD_SITE_PATH", str_replace("//", "/", WIZARD_SITE_ROOT_PATH."/".WIZARD_SITE_DIR."/"));
		//Copy index page
		CopyDirFiles(
			WIZARD_SITE_PATH."/_index.php",
			WIZARD_SITE_PATH."/index.php",
			$rewrite = true,
			$recursive = true,
			$delete_after_copy = true
		);
	}
}
?>