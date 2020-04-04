<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if (!CModule::IncludeModule('bizproc'))
{
	return false;
}

if (!function_exists("BPWSInitParam"))
{
	function BPWSInitParam(&$arParams, $name)
	{
		$arParams[$name] = trim($arParams[$name]);
		if ($arParams[$name] <= 0)
			$arParams[$name] = trim($_REQUEST[$name]);
		if ($arParams[$name] <= 0)
			$arParams[$name] = trim($_REQUEST[strtolower($name)]);
	}
}
/********************************************************************
				Input params
********************************************************************/
/***************** BASE ********************************************/
if(isset($arParams["DOCUMENT_TYPE"]))
{
	$arParams["STORAGE_ID"] = intval(str_replace("STORAGE_", "", $arParams["DOCUMENT_TYPE"]));
}
$arParams["TEMPLATE_ID"] = intval($_REQUEST["workflow_template_id"]);
/***************** STANDART ****************************************/
$arParams["SET_TITLE"] = ($arParams["SET_TITLE"] == "N" ? "N" : "Y");
/********************************************************************
				Main data
********************************************************************/
$arError = array();
if (strlen($arParams["MODULE_ID"]) <= 0)
{
	$arError[] = array(
		"id" => "empty_module_id",
		"text" => GetMessage("BPATT_NO_MODULE_ID"));
}
if (strlen($arParams["STORAGE_ID"]) <= 0)
{
	$arError[] = array(
		"id" => "empty_document_type",
		"text" => GetMessage("BPABS_EMPTY_DOC_TYPE"));
}
if (strlen($arParams["DOCUMENT_ID"]) <= 0)
{
	$arError[] = array(
		"id" => "empty_document_id",
		"text" => GetMessage("BPABS_EMPTY_DOC_ID"));
}

$documentData = array(
	'DISK' => array(
		'DOCUMENT_TYPE' => \Bitrix\Disk\BizProcDocument::generateDocumentComplexType($arParams['STORAGE_ID']),
		'DOCUMENT_ID' => \Bitrix\Disk\BizProcDocument::getDocumentComplexId($arParams["DOCUMENT_ID"]),
	),
	'WEBDAV' => array(
		'DOCUMENT_TYPE' => \Bitrix\Disk\BizProcDocumentCompatible::generateDocumentComplexType($arParams['STORAGE_ID']),
		'DOCUMENT_ID' => \Bitrix\Disk\BizProcDocumentCompatible::getDocumentComplexId($arParams["DOCUMENT_ID"]),
	),
);

if (!check_bitrix_sessid())
{
	$arError[] = array(
		"id" => "access_denied",
		"text" => GetMessage("BPABS_NO_PERMS"));
}

if (empty($arError))
{
	if (!CBPDocument::CanUserOperateDocument(
		CBPCanUserOperateOperation::StartWorkflow,
		$GLOBALS["USER"]->GetID(),
		$documentData['DISK']["DOCUMENT_ID"],
		array()))
	{
		$arError[] = array(
			"id" => "access_denied",
			"text" => GetMessage("BPABS_NO_PERMS"));
	}
}
if (!empty($arError))
{
	$e = new CAdminException($arError);
	ShowError($e->GetString());
	return false;
}
elseif (!empty($_REQUEST["cancel"]) && !empty($_REQUEST["back_url"]))
{
	LocalRedirect(str_replace("#WF#", "", $_REQUEST["back_url"]));
}
/********************************************************************
				/Main data
********************************************************************/
$arResult["SHOW_MODE"] = "SelectWorkflow";
$arResult["TEMPLATES"] = array();
$arResult["PARAMETERS_VALUES"] = array();
$arResult["ERROR_MESSAGE"] = "";

$runtime = CBPRuntime::GetRuntime();
$runtime->StartRuntime();
$arResult["DocumentService"] = $runtime->GetService("DocumentService");
/********************************************************************
				Data
********************************************************************/
foreach($documentData as $nameModule => $data)
{
	$workflowTemplateObject = CBPWorkflowTemplateLoader::GetList(
		array(),
		array("DOCUMENT_TYPE" => $data["DOCUMENT_TYPE"], "ACTIVE" => "Y"),
		false,
		false,
		array("ID", "NAME", "DESCRIPTION", "MODIFIED", "USER_ID", "PARAMETERS")
	);
	while ($workflowTemplate = $workflowTemplateObject->GetNext())
	{
		if (!CBPDocument::CanUserOperateDocument(
			CBPCanUserOperateOperation::StartWorkflow,
			$GLOBALS["USER"]->GetID(),
			$data["DOCUMENT_ID"],
			array())):
			continue;
		endif;
		if($nameModule == 'DISK')
		{
			$arResult["TEMPLATES"][$workflowTemplate["ID"]] = $workflowTemplate;
			$arResult["TEMPLATES"][$workflowTemplate["ID"]]["URL"] =
				htmlspecialcharsex($APPLICATION->GetCurPageParam(
					"workflow_template_id=".$workflowTemplate["ID"].'&'.bitrix_sessid_get(),
					Array("workflow_template_id", "sessid")));
		}
		else
		{
			$arResult["TEMPLATES_OLD"][$workflowTemplate["ID"]] = $workflowTemplate;
			$arResult["TEMPLATES_OLD"][$workflowTemplate["ID"]]["URL"] =
				htmlspecialcharsex($APPLICATION->GetCurPageParam(
					"workflow_template_id=".$workflowTemplate["ID"].'&old=1&'.bitrix_sessid_get(),
					Array("workflow_template_id", "sessid")));
		}

	}
}

if ($arParams["TEMPLATE_ID"] > 0 && strlen($_POST["CancelStartParamWorkflow"]) <= 0
	&& (array_key_exists($arParams["TEMPLATE_ID"], $arResult["TEMPLATES"]) || array_key_exists($arParams["TEMPLATE_ID"], $arResult["TEMPLATES_OLD"])))
{
	$templates = array();
	$documentParameters = array();
	if(array_key_exists($arParams["TEMPLATE_ID"], $arResult["TEMPLATES"]))
	{
		$templates = $arResult["TEMPLATES"];
		$documentParameters = $documentData['DISK'];
		$arResult['CHECK_TEMPLATE'] = 'DISK';
	}
	else
	{
		$templates = $arResult["TEMPLATES_OLD"];
		$documentParameters = $documentData['WEBDAV'];
		$arResult['CHECK_TEMPLATE'] = 'WEBDAV';
	}

	$workflowTemplate = $templates[$arParams["TEMPLATE_ID"]];

	$arWorkflowParameters = array();
	$bCanStartWorkflow = false;

	if (count($workflowTemplate["PARAMETERS"]) <= 0)
	{
		$bCanStartWorkflow = true;
	}
	elseif ($_SERVER["REQUEST_METHOD"] == "POST" && strlen($_POST["DoStartParamWorkflow"]) > 0)
	{
		$arErrorsTmp = array();
		$arRequest = $_REQUEST;

		foreach ($_FILES as $k => $v)
		{
			if (array_key_exists("name", $v))
			{
				if (is_array($v["name"]))
				{
					$ks = array_keys($v["name"]);
					for ($i = 0, $cnt = count($ks); $i < $cnt; $i++)
					{
						$ar = array();
						foreach ($v as $k1 => $v1)
						{
							$ar[$k1] = $v1[$ks[$i]];
						}
						$arRequest[$k][] = $ar;
					}
				}
				else
				{
					$arRequest[$k] = $v;
				}
			}
		}

		$arWorkflowParameters = CBPWorkflowTemplateLoader::CheckWorkflowParameters(
			$workflowTemplate["PARAMETERS"],
			$arRequest,
			$documentParameters["DOCUMENT_TYPE"],
			$arErrorsTmp
		);

		if (count($arErrorsTmp) > 0)
		{
			$bCanStartWorkflow = false;
			foreach ($arErrorsTmp as $e)
			{
				$arError[] = array(
					"id" => "CheckWorkflowParameters",
					"text" => $e["message"]);
			}
		}
		else
		{
			$bCanStartWorkflow = true;
		}
	}

	if ($bCanStartWorkflow)
	{
		$arErrorsTmp = array();

		$wfId = CBPDocument::StartWorkflow(
			$arParams["TEMPLATE_ID"],
			$documentParameters["DOCUMENT_ID"],
			array_merge($arWorkflowParameters, array("TargetUser" => "user_".intval($GLOBALS["USER"]->GetID()))),
			$arErrorsTmp
		);

		if (count($arErrorsTmp) > 0)
		{
			$arResult["SHOW_MODE"] = "StartWorkflowError";
			foreach ($arErrorsTmp as $e)
			{
				$arError[] = array(
					"id" => "StartWorkflowError",
					"text" => "[".$e["code"]."] ".$e["message"]);
			}
		}
		else
		{
			$arResult["SHOW_MODE"] = "StartWorkflowSuccess";
			if (strlen($arResult["back_url"]) > 0)
			{
				LocalRedirect(str_replace("#WF#", $wfId, $_REQUEST["back_url"]));
				die();
			}
		}
	}
	else
	{
		$p = ($_SERVER["REQUEST_METHOD"] == "POST" && strlen($_POST["DoStartParamWorkflow"]) > 0);
		$keys = array_keys($workflowTemplate["PARAMETERS"]);
		foreach ($keys as $key)
		{
			$v = ($p ? $_REQUEST[$key] : $workflowTemplate["PARAMETERS"][$key]["Default"]);
			if (!is_array($v))
			{
				$arResult["PARAMETERS_VALUES"][$key] = CBPHelper::ConvertParameterValues($v);
			}
			else
			{
				$keys1 = array_keys($v);
				foreach ($keys1 as $key1)
				{
					$arResult["PARAMETERS_VALUES"][$key][$key1] = CBPHelper::ConvertParameterValues($v[$key1]);
				}
			}
		}

		$arResult["SHOW_MODE"] = "WorkflowParameters";
	}
	if (!empty($arError))
	{
		$e = new CAdminException($arError);
		$arResult["ERROR_MESSAGE"] = $e->GetString();
	}
}
else
{
	$arResult["SHOW_MODE"] = "SelectWorkflow";
}
/********************************************************************
				/Data
********************************************************************/
$arResult['DOCUMENT_DATA'] = $documentData;
$this->IncludeComponentTemplate();

/********************************************************************
				Standart operations
********************************************************************/
if($arParams["SET_TITLE"] == "Y")
{
	$APPLICATION->SetTitle(GetMessage("BPABS_TITLE"));
}
?>