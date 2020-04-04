<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPControllerRemoteIBlockActivity
	extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"SitesFilterType" => "all",
			"SitesFilterGroups" => array(),
			"SitesFilterSitesGroup" => "",
			"SitesFilterSites" => array(),
			"SyncTime" => "immediate",
		);
	}

	public function Execute()
	{
		global $DB;

		if (!CModule::IncludeModule("controller"))
			return CBPActivityExecutionStatus::Closed;

		if (!CModule::IncludeModule("iblock"))
			return CBPActivityExecutionStatus::Closed;

		$rootActivity = $this->GetRootActivity();
		$documentId = $rootActivity->GetDocumentId();
		if($documentId[0] !== 'iblock' || $documentId[1] !== 'CIBlockDocument' || $documentId[2] <= 0)
			return CBPActivityExecutionStatus::Closed;

		$arFilter = array(
			"=ACTIVE" => "Y",
			"=DISCONNECTED" => "N",
		);
		if($this->SitesFilterType == "groups")
		{
			if(is_array($this->SitesFilterGroups))
				$arFilter["=CONTROLLER_GROUP_ID"] = $this->SitesFilterGroups;
			else
				return CBPActivityExecutionStatus::Closed;
		}
		elseif($this->SitesFilterType == "sites")
		{
			if(intval($this->SitesFilterSitesGroup) > 0 && is_array($this->SitesFilterSites))
			{
				$arFilter["=CONTROLLER_GROUP_ID"] = $this->SitesFilterSitesGroup;
				$arFilter["=ID"] = $this->SitesFilterSites;
			}
			else
			{
				return CBPActivityExecutionStatus::Closed;
			}
		}

		$rootActivity = $this->GetRootActivity();
		$documentId = $rootActivity->GetDocumentId();

		$export_file = $this->Export($documentId);
		if(strlen($export_file) <= 0)
			return CBPActivityExecutionStatus::Closed;

		$documentService = $this->workflow->GetService("DocumentService");
		$document = $documentService->GetDocument($documentId);
		$arIBlock = CIBlock::GetArrayByID($document["IBLOCK_ID"]);
		$iblock_type = $arIBlock["IBLOCK_TYPE_ID"];

		$query = '
if(version_compare(SM_VERSION, "11.0.10") < 0)
{
	echo "Client main module version >= 11.0.10 is required.";
	return false;
}

$charset_to = '.$this->PHP2PHP(LANG_CHARSET).';
$export_file = CTempFile::GetDirectoryName()."import.tar.gz";
$iblock_type = '.$this->PHP2PHP($iblock_type).';

if(!CModule::IncludeModule("iblock"))
{
	echo "Information block module not installed";
	return false;
}

$iblock_id = CIBlockCMLImport::GetIBlockByXML_ID('.$this->PHP2PHP($arIBlock['XML_ID']).');
if(!$iblock_id)
{
	$rsType = CIBlockType::GetByID($iblock_type);
	if(!$rsType->Fetch())
	{
		echo "Information block type not found: $iblock_type";
		return false;
	}
}

CheckDirPath($export_file);
file_put_contents($export_file, base64_decode("'.base64_encode(file_get_contents($export_file)).'"));
if(!file_exists($export_file) || !is_file($export_file))
{
	echo "Can not create file: ".$export_file;
	return false;
}

$USER->Authorize(1);
$USER->SetControllerAdmin(true);
$res = ImportXMLFile($export_file, $iblock_type, false, "N", "N", true, false, true, true);

if($res !== true)
{
	echo $APPLICATION->ConvertCharset($res, LANG_CHARSET, $charset_to);
	return false;
}

return true;
';

		$rsMembers = CControllerMember::GetList(array("ID"=>"ASC"), $arFilter);
		if($this->SyncTime == "task")
		{
			while($arMember = $rsMembers->Fetch())
			{
				CControllerTask::Add(array(
					"TASK_ID" => "REMOTE_COMMAND",
					"CONTROLLER_MEMBER_ID" => $arMember["ID"],
					"INIT_EXECUTE" => $query
				));
			}
		}
		else
		{
			while($arMember = $rsMembers->Fetch())
			{
				CControllerMember::RunCommandWithLog(
					$arMember["ID"],
					$query,
					array(),
					false,
					'run_immediate'
				);
			}
		}

		return CBPActivityExecutionStatus::Closed;
	}

	function Export($documentId)
	{
		$work_dir = CTempFile::GetDirectoryName();
		CheckDirPath($work_dir);

		$file = "import";
		$file_name = $work_dir.$file.".xml";
		$file_dir = $file."_files/";
		$arcname = $work_dir.$file.'.tar.gz';

		if($fp = fopen($file_name, "ab"))
		{
			$documentService = $this->workflow->GetService("DocumentService");
			$document = $documentService->GetDocument($documentId);

			$obExport = new CIBlockCMLExport;
			$step = array();
			$PROPERTY_MAP = array();
			$SECTION_MAP = array();
			if($obExport->Init($fp, $document["IBLOCK_ID"], $step, true, $work_dir, $file_dir, false))
			{
				$obExport->StartExport();
				$obExport->StartExportMetadata();
				$obExport->ExportProperties($PROPERTY_MAP);
				$obExport->ExportSections($SECTION_MAP, time(), 0);
				$obExport->EndExportMetadata();
				$obExport->StartExportCatalog(true, true);
				$obExport->ExportElements($PROPERTY_MAP, $SECTION_MAP, time(), 0, 0, array("SHOW_NEW" => "Y", "IBLOCK_ID" => $document["IBLOCK_ID"], "=ID" => $document["ID"]));
				$obExport->EndExportCatalog();
				$obExport->EndExport();
				fclose($fp);

				include_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT.'/modules/main/classes/general/tar_gz.php');

				$ob = new CArchiver($arcname);
				$res = $ob->Add('"'.$file_name.'"', false, $work_dir);
				if($res)
					$res = $ob->Add('"'.$work_dir.$file_dir.'"', false, $work_dir);

				if($res)
					return $arcname;
				else
					return '';
			}
			else
			{
				return '';
			}
		}
		else
		{
			return '';
		}
	}

	function PHP2PHP($var)
	{
		if(is_array($var))
		{
			$res = "array(\n";
			foreach($var as $k => $v)
			{
				$res .= $this->PHP2PHP($k)." => ".$this->PHP2PHP($v).",\n";
			}
			$res .= ")";
		}
		elseif(is_null($var))
		{
			$res = 'null';
		}
		elseif(is_int($var))
		{
			$res = $var;
		}
		elseif(is_double($var))
		{
			$res = $var;
		}
		elseif(is_bool($var))
		{
			$res = $var? 'true': 'false';
		}
		else
		{
			$res = '"'.EscapePHPString($var).'"';
		}
		return $res;
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "")
	{
		$runtime = CBPRuntime::GetRuntime();

		if (!is_array($arWorkflowParameters))
			$arWorkflowParameters = array();
		if (!is_array($arWorkflowVariables))
			$arWorkflowVariables = array();

		if (!is_array($arCurrentValues))
		{
			$arCurrentValues = array("sites_filter_type" => "all");

			$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
			if (is_array($arCurrentActivity["Properties"]))
			{
				$arCurrentValues["sites_filter_type"] = $arCurrentActivity["Properties"]["SitesFilterType"];
				$arCurrentValues["sites_filter_groups"] = $arCurrentActivity["Properties"]["SitesFilterGroups"];
				if(!is_array($arCurrentValues["sites_filter_groups"]))
					$arCurrentValues["sites_filter_groups"] = array();
				$arCurrentValues["sites_filter_sites_group"] = $arCurrentActivity["Properties"]["SitesFilterSitesGroup"];
				$arCurrentValues["sites_filter_sites"] = $arCurrentActivity["Properties"]["SitesFilterSites"];
				if(!is_array($arCurrentValues["sites_filter_sites"]))
					$arCurrentValues["sites_filter_sites"] = array();
				$arCurrentValues["sync_time"] = $arCurrentActivity["Properties"]["SyncTime"];
			}
		}

		$arSiteGroups = Array();
		$arSites = Array();
		if(CModule::IncludeModule('controller'))
		{
			$rsSiteGroups = CControllerGroup::GetList(Array("ID" => "ASC"));
			while($arSiteGroup = $rsSiteGroups->GetNext())
				$arSiteGroups[$arSiteGroup["ID"]] = $arSiteGroup["NAME"];

			$rsSites = CControllerMember::GetList(Array("ID" => "ASC"), array("=ACTIVE" => "Y", "=DISCONNECTED"=>"N"));
			while($arSite = $rsSites->GetNext())
			{
				if(!array_key_exists($arSite["CONTROLLER_GROUP_ID"], $arSites))
					$arSites[$arSite["CONTROLLER_GROUP_ID"]] = array();
				$arSites[$arSite["CONTROLLER_GROUP_ID"]][$arSite["ID"]] = $arSite["NAME"];
			}
		}

		return $runtime->ExecuteResourceFile(
			__FILE__,
			"properties_dialog.php",
			array(
				"arCurrentValues" => $arCurrentValues,
				"formName" => $formName,
				"is_module_installed" => IsModuleInstalled('controller'),
				"arSiteGroups" => $arSiteGroups,
				"arSites" => $arSites,
			)
		);
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$arErrors = array();

		if(!IsModuleInstalled('controller'))
		{
			$arErrors[] = array(
				"code" => "module",
				"message" => GetMessage("BPCRIA_NO_MODULE"),
			);
			return false;
		}

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = array();

		$arCurrentActivity["Properties"]["SitesFilterType"] = $arCurrentValues["sites_filter_type"];
		if($arCurrentValues["sites_filter_type"]=="groups" && is_array($arCurrentValues["sites_filter_groups"]))
			$arCurrentActivity["Properties"]["SitesFilterGroups"] = $arCurrentValues["sites_filter_groups"];
		else
			$arCurrentActivity["Properties"]["SitesFilterGroups"] = array();

		if($arCurrentValues["sites_filter_type"]=="sites")
		{
			$arCurrentActivity["Properties"]["SitesFilterSitesGroup"] = $arCurrentValues["sites_filter_sites_group"];
			if(is_array($arCurrentValues["sites_filter_sites"]))
				$arCurrentActivity["Properties"]["SitesFilterSites"] = $arCurrentValues["sites_filter_sites"];
			else
				$arCurrentActivity["Properties"]["SitesFilterSites"] = array();
		}
		$arCurrentActivity["Properties"]["SyncTime"] = $arCurrentValues["sync_time"];


		return true;
	}
}
?>