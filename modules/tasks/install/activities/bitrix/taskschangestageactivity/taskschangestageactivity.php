<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Tasks;

class CBPTasksChangeStageActivity extends CBPActivity
{
	private static $cycleCounter = [];

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"TargetStage" => null,
		);
	}

	public function Execute()
	{
		if ($this->TargetStage == null || !CModule::IncludeModule("tasks"))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$documentId = $this->GetDocumentId();
		$documentType = $this->GetDocumentType();
		$targetStage = (int) $this->TargetStage;

		$target = Tasks\Integration\Bizproc\Automation\Factory::createTarget($documentType[2], $documentId[2]);

		$currentStage = (int) $target->getDocumentStatus();
		$allStages = array_keys($target->getDocumentStatusList());

		if ($targetStage === $currentStage || !in_array($targetStage, $allStages))
		{
			$this->WriteToTrackingService(GetMessage('TASKS_CHANGE_STAGE_STAGE_ERROR', 0, CBPTrackingType::Error));
			return $this->endExecution();
		}

		//check recursion
		if ($this->checkCycling($targetStage))
		{
			$this->WriteToTrackingService(GetMessage('TASKS_CHANGE_STAGE_RECURSION', 0, CBPTrackingType::Error));
			return $this->endExecution(GetMessage('TASKS_CHANGE_STAGE_RECURSION'));
		}
		// end check recursion

		$target->setDocumentStatus($targetStage);

		return $this->endExecution();
	}

	private function endExecution($message = null)
	{
		if (!$message)
		{
			$message = GetMessage('TASKS_CHANGE_STAGE_TERMINATED');
		}

		CBPDocument::TerminateWorkflow(
			$this->GetWorkflowInstanceId(),
			$this->GetDocumentId(),
			$errors,
			$message
		);

		//Stop running queue
		throw new Exception("TerminateWorkflow");
		return CBPActivityExecutionStatus::Closed;
	}

	private function checkCycling($targetStage)
	{
		$documentTag = $this->GetDocumentType()[2] .'|'. $this->GetDocumentId()[2];

		if (!isset(self::$cycleCounter[$documentTag]))
		{
			self::$cycleCounter[$documentTag] = [];
		}
		if (!isset(self::$cycleCounter[$documentTag][$targetStage]))
		{
			self::$cycleCounter[$documentTag][$targetStage] = 0;
		}

		++self::$cycleCounter[$documentTag][$targetStage];

		return (self::$cycleCounter[$documentTag][$targetStage] > 2);
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$errors = [];

		if (empty($arTestProperties["TargetStage"]))
		{
			$errors[] = array("code" => "NotExist", "parameter" => "TargetStage", "message" => GetMessage("TASKS_CHANGE_STAGE_EMPTY_PROP"));
		}

		return array_merge($errors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "", $popupWindow = null, $siteId = '')
	{
		if (!CModule::IncludeModule("tasks"))
		{
			return '';
		}

		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, array(
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $arWorkflowTemplate,
			'workflowParameters' => $arWorkflowParameters,
			'workflowVariables' => $arWorkflowVariables,
			'currentValues' => $arCurrentValues,
			'formName' => $formName,
			'siteId' => $siteId
		));

		$dialog->setMapCallback(array(__CLASS__, 'getPropertiesDialogMap'));

		return $dialog;
	}

	/**
	 * @param \Bitrix\Bizproc\Activity\PropertiesDialog $dialog
	 * @return array Map.
	 */
	public static function getPropertiesDialogMap($dialog)
	{
		if (!CModule::IncludeModule('tasks'))
		{
			return [];
		}

		$documentStatuses = [];
		$target = Tasks\Integration\Bizproc\Automation\Factory::createTarget($dialog->getDocumentType()[2]);

		foreach ($target->getDocumentStatusList() as  $id => $stage)
		{
			$documentStatuses[$id] = $stage['TITLE'];
		}

		return array(
			'TargetStage' => array(
				'Name' => GetMessage('TASKS_CHANGE_STAGE_STAGE'),
				'FieldName' => 'target_stage',
				'Type' => 'select',
				'Required' => true,
				'Options' => $documentStatuses
			)
		);
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		$errors = [];

		$properties = array(
			'TargetStage' => (int) $arCurrentValues['target_stage']
		);

		$errors = self::ValidateProperties(
			$properties,
			new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser)
		);

		if (count($errors) > 0)
		{
			return false;
		}

		$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$currentActivity["Properties"] = $properties;

		return true;
	}
}