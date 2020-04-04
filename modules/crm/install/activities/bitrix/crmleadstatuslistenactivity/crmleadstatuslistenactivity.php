<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPCrmLeadStatusListenActivity
	extends CBPActivity
	implements IBPEventActivity, IBPActivityExternalEventListener
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"LeadId" => null,
			"WaitForState" => null,
			//return
			"StatusSemantics" => null,
			"StatusId" => null,
		);

		$this->SetPropertiesTypes(array(
			'StatusSemantics' => array(
				'Type' => 'string',
			),
			'StatusId' => array(
				'Type' => 'string',
			),
		));
	}

	public function ReInitialize()
	{
		parent::ReInitialize();
		$this->StatusSemantics = null;
		$this->StatusId = null;
	}

	public function Cancel()
	{
		$this->Unsubscribe($this);
		return CBPActivityExecutionStatus::Closed;
	}

	public function Execute()
	{
		if ($this->LeadId == null || !CModule::IncludeModule("crm"))
			return CBPActivityExecutionStatus::Closed;

		$lead = CCrmLead::GetByID($this->LeadId, false);
		$statusSemantics = CCrmLead::GetStatusSemantics($lead['STATUS_ID']);

		$targetStatus = (array)$this->WaitForState;
		if ($statusSemantics != 'process' || in_array($lead['STATUS_ID'], $targetStatus))
		{
			$this->StatusSemantics = $statusSemantics;
			$this->StatusId = $lead['STATUS_ID'];
			return CBPActivityExecutionStatus::Closed;
		}

		$this->Subscribe($this);
		$this->WriteToTrackingService(GetMessage("BPCLSLA_TRACK", array("#LEAD#" => $lead["TITLE"])));
		return CBPActivityExecutionStatus::Executing;
	}

	public function Subscribe(IBPActivityExternalEventListener $eventHandler)
	{
		if ($eventHandler == null)
			throw new Exception("eventHandler");

		$schedulerService = $this->workflow->GetService("SchedulerService");
		$schedulerService->SubscribeOnEvent($this->workflow->GetInstanceId(), $this->name, "crm", "OnAfterCrmLeadUpdate", array('ID' => $this->LeadId));

		$this->workflow->AddEventHandler($this->name, $eventHandler);
	}

	public function Unsubscribe(IBPActivityExternalEventListener $eventHandler)
	{
		if ($eventHandler == null)
			throw new Exception("eventHandler");

		$schedulerService = $this->workflow->GetService("SchedulerService");
		$schedulerService->UnSubscribeOnEvent($this->workflow->GetInstanceId(), $this->name, "crm", "OnAfterCrmLeadUpdate", array('ID' => $this->LeadId));

		$this->workflow->RemoveEventHandler($this->name, $eventHandler);
	}

	public function OnExternalEvent($arEventParameters = array())
	{
		if ($this->executionStatus != CBPActivityExecutionStatus::Closed)
		{
			if ($this->LeadId != $arEventParameters[0]['ID'] || !isset($arEventParameters[0]['STATUS_ID']))
				return;

			$statusSemantics = CCrmLead::GetStatusSemantics($arEventParameters[0]['STATUS_ID']);

			$targetStatus = (array)$this->WaitForState;

			if ($statusSemantics == 'process' && !in_array($arEventParameters[0]['STATUS_ID'], $targetStatus))
			{
				return;
			}

			$this->StatusSemantics = $statusSemantics;
			$this->StatusId = $arEventParameters[0]['STATUS_ID'];

			$this->Unsubscribe($this);
			$this->workflow->CloseActivity($this);
		}
	}

	public function HandleFault(Exception $exception)
	{
		if ($exception == null)
			throw new Exception("exception");

		$status = $this->Cancel();
		if ($status == CBPActivityExecutionStatus::Canceling)
			return CBPActivityExecutionStatus::Faulting;

		return $status;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = array();

		if (
			!array_key_exists("LeadId", $arTestProperties)
			|| (intval($arTestProperties["LeadId"]) <= 0 && !CBPDocument::IsExpression($arTestProperties["LeadId"]))
		)
		{
			$arErrors[] = array("code" => "NotExist", "parameter" => "LeadId", "message" => GetMessage("BPCLSLA_EMPTY_PROP"));
		}

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "")
	{
		$runtime = CBPRuntime::GetRuntime();

		if (!is_array($arCurrentValues))
		{
			$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);

			if (is_array($arCurrentActivity["Properties"]))
			{
				if (array_key_exists("LeadId", $arCurrentActivity["Properties"]) && !is_null($arCurrentActivity["Properties"]["LeadId"]))
					$arCurrentValues["lead_id"] = $arCurrentActivity["Properties"]["LeadId"];
				if (array_key_exists("WaitForState", $arCurrentActivity["Properties"]) && !is_null($arCurrentActivity["Properties"]["WaitForState"]))
					$arCurrentValues["status"] = $arCurrentActivity["Properties"]["WaitForState"];
			}
		}

		return $runtime->ExecuteResourceFile(
			__FILE__,
			"properties_dialog.php",
			array(
				"arCurrentValues" => $arCurrentValues,
				"formName" => $formName
			)
		);
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$arErrors = Array();

		$arProperties = array();

		$arProperties["LeadId"] = $arCurrentValues["lead_id"];
		$arProperties["WaitForState"] = $arCurrentValues["status"];

		$arErrors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($arErrors) > 0)
			return false;

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $arProperties;

		return true;
	}
}