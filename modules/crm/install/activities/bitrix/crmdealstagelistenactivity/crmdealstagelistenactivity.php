<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPCrmDealStageListenActivity
	extends CBPActivity
	implements IBPEventActivity, IBPActivityExternalEventListener
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"DealId" => null,
			"WaitForState" => null,
			//return
			"StageSemantics" => null,
			"StageId" => null,
		);

		$this->SetPropertiesTypes(array(
			'StageSemantics' => array(
				'Type' => 'string',
			),
			'StageId' => array(
				'Type' => 'string',
			),
		));
	}

	public function ReInitialize()
	{
		parent::ReInitialize();
		$this->StageSemantics = null;
		$this->StageId = null;
	}

	public function Cancel()
	{
		$this->Unsubscribe($this);
		return CBPActivityExecutionStatus::Closed;
	}

	public function Execute()
	{
		if ($this->DealId == null || !CModule::IncludeModule("crm"))
			return CBPActivityExecutionStatus::Closed;

		$deal = CCrmDeal::GetByID($this->DealId, false);
		$stageSemantics = CCrmDeal::GetStageSemantics($deal['STAGE_ID']);

		$targetStage = (array)$this->WaitForState;
		if ($stageSemantics != 'process' || in_array($deal['STAGE_ID'], $targetStage))
		{
			$this->StageSemantics = $stageSemantics;
			$this->StageId = $deal['STAGE_ID'];
			return CBPActivityExecutionStatus::Closed;
		}

		$this->Subscribe($this);
		$this->WriteToTrackingService(GetMessage("BPCDSA_TRACK", array("#DEAL#" => $deal["TITLE"])));
		return CBPActivityExecutionStatus::Executing;
	}

	public function Subscribe(IBPActivityExternalEventListener $eventHandler)
	{
		if ($eventHandler == null)
			throw new Exception("eventHandler");

		$schedulerService = $this->workflow->GetService("SchedulerService");
		$schedulerService->SubscribeOnEvent($this->workflow->GetInstanceId(), $this->name, "crm", "OnAfterCrmDealUpdate", array('ID' => $this->DealId));

		$this->workflow->AddEventHandler($this->name, $eventHandler);
	}

	public function Unsubscribe(IBPActivityExternalEventListener $eventHandler)
	{
		if ($eventHandler == null)
			throw new Exception("eventHandler");

		$schedulerService = $this->workflow->GetService("SchedulerService");
		$schedulerService->UnSubscribeOnEvent($this->workflow->GetInstanceId(), $this->name, "crm", "OnAfterCrmDealUpdate", array('ID' => $this->DealId));

		$this->workflow->RemoveEventHandler($this->name, $eventHandler);
	}

	public function OnExternalEvent($arEventParameters = array())
	{
		if ($this->executionStatus != CBPActivityExecutionStatus::Closed)
		{
			if ($this->DealId != $arEventParameters[0]['ID'] || !isset($arEventParameters[0]['STAGE_ID']))
				return;

			$stageSemantics = CCrmDeal::GetStageSemantics($arEventParameters[0]['STAGE_ID']);

			$targetStage = (array)$this->WaitForState;

			if ($stageSemantics == 'process' && !in_array($arEventParameters[0]['STAGE_ID'], $targetStage))
			{
				return;
			}

			$this->StageSemantics = $stageSemantics;
			$this->StageId = $arEventParameters[0]['STAGE_ID'];

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
			!array_key_exists("DealId", $arTestProperties)
			|| (intval($arTestProperties["DealId"]) <= 0 && !CBPDocument::IsExpression($arTestProperties["DealId"]))
		)
		{
			$arErrors[] = array("code" => "NotExist", "parameter" => "DealId", "message" => GetMessage("BPCDSA_EMPTY_PROP"));
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
				if (array_key_exists("DealId", $arCurrentActivity["Properties"]) && !is_null($arCurrentActivity["Properties"]["DealId"]))
					$arCurrentValues["deal_id"] = $arCurrentActivity["Properties"]["DealId"];
				if (array_key_exists("WaitForState", $arCurrentActivity["Properties"]) && !is_null($arCurrentActivity["Properties"]["WaitForState"]))
					$arCurrentValues["stage"] = $arCurrentActivity["Properties"]["WaitForState"];
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

		$arProperties["DealId"] = $arCurrentValues["deal_id"];
		$arProperties["WaitForState"] = $arCurrentValues["stage"];

		$arErrors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($arErrors) > 0)
			return false;

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $arProperties;

		return true;
	}
}
?>
