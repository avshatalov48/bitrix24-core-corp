<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Tasks;

class CBPTasksChangeResponsibleActivity extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"Responsible" => null,
			"ModifiedBy" => null,
		);
	}

	public function Execute()
	{
		if ($this->Responsible == null || !CModule::IncludeModule("tasks"))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		if (!$this->canChangeResponsible())
		{
			$this->WriteToTrackingService(GetMessage('TASKS_CHANGE_RESPONSIBLE_NO_PERMISSIONS_MSGVER_1'), 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		$documentId = $this->GetDocumentId();
		$runtime = CBPRuntime::GetRuntime();
		/** @var CBPDocumentService $ds */
		$ds = $runtime->GetService('DocumentService');

		$document = $ds->GetDocument($documentId);
		$responsibleFieldName = 'RESPONSIBLE_ID';
		if (isset($document[$responsibleFieldName]))
		{
			$documentResponsible = CBPHelper::ExtractUsers($document[$responsibleFieldName], $documentId, true);
			$targetResponsibles = CBPHelper::ExtractUsers($this->Responsible, $documentId);

			$searchKey = array_search($documentResponsible, $targetResponsibles);
			if ($searchKey !== false)
			{
				unset($targetResponsibles[$searchKey]);
			}
			shuffle($targetResponsibles);

			if ($targetResponsibles)
			{
				$documentResponsible = 'user_'.$targetResponsibles[0];
				$ds->UpdateDocument(
					$documentId,
					[$responsibleFieldName => $documentResponsible],
					$this->ModifiedBy
				);
			}
		}

		return CBPActivityExecutionStatus::Closed;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$errors = [];

		if (empty($arTestProperties["Responsible"]))
		{
			$errors[] = array("code" => "NotExist", "parameter" => "Responsible", "message" => GetMessage("TASKS_CHANGE_RESPONSIBLE_EMPTY_PROP_MSGVER_1"));
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

		$dialog->setMap([
			'Responsible' => [
				'Name' => GetMessage('TASKS_CHANGE_RESPONSIBLE_NEW_V2'),
				'FieldName' => 'responsible',
				'Type' => 'user',
				'Required' => true,
				'Multiple' => true
			],
			'ModifiedBy' => [
				'Name' => GetMessage('TASKS_CHANGE_RESPONSIBLE_MODIFIED_BY'),
				'FieldName' => 'modified_by',
				'Type' => 'user'
			]
		]);

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		$errors = [];

		$properties = array(
			'Responsible' => CBPHelper::UsersStringToArray($arCurrentValues["responsible"], $documentType, $errors),
			'ModifiedBy' => CBPHelper::UsersStringToArray($arCurrentValues["modified_by"], $documentType, $errors)
		);

		if (count($errors) > 0)
		{
			return false;
		}

		$errors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($errors) > 0)
		{
			return false;
		}

		$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$currentActivity["Properties"] = $properties;

		return true;
	}

	private function canChangeResponsible()
	{
		$documentType = $this->GetDocumentType()[2];
		$taskId = $this->GetDocumentId()[2];

		$canChange = false;

		if (
			Tasks\Integration\Bizproc\Document\Task::isProjectTask($documentType)
			|| Tasks\Integration\Bizproc\Document\Task::isScrumProjectTask($documentType)
		)
		{
			$canChange = true;
		}
		else
		{
			if (Tasks\Integration\Bizproc\Document\Task::isPlanTask($documentType))
			{
				$ownerId = Tasks\Integration\Bizproc\Document\Task::resolvePlanId($documentType);
			}
			else
			{
				$ownerId = Tasks\Integration\Bizproc\Document\Task::resolvePersonId($documentType);
			}

			if ($ownerId > 0)
			{
				$res = \CTasks::GetByID($taskId, false);
				$taskFields = $res ? $res->fetch() : null;

				if ($taskFields)
				{
					$allowedActions = \CTaskItem::getAllowedActionsArray($ownerId, $taskFields, true);

					$canChange = (isset($allowedActions['ACTION_EDIT']) && $allowedActions['ACTION_EDIT'] === true);
				}
			}
		}

		return $canChange;
	}
}