<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Tasks;
use Bitrix\Tasks\Internals\Task\Status;

class CBPTasksChangeStatusActivity extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"TargetStatus" => null,
			"ModifiedBy" => null,
		);
	}

	public function Execute()
	{
		if ($this->TargetStatus == null || !CModule::IncludeModule("tasks"))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$documentId = $this->GetDocumentId();
		$targetStatus = (int) $this->TargetStatus;

		/** @var CBPDocumentService $ds */
		$ds = $this->workflow->GetService('DocumentService');

		$document = $ds->GetDocument($documentId);
		if ($document && (int)$document['STATUS'] === $targetStatus)
		{
			return CBPActivityExecutionStatus::Closed;
		}

		if (!$this->canChangeStatus($targetStatus))
		{
			$this->WriteToTrackingService(GetMessage('TASKS_CHANGE_STATUS_NO_PERMISSIONS'), 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		$ds->UpdateDocument($documentId, ['STATUS' => $targetStatus], $this->ModifiedBy);

		return CBPActivityExecutionStatus::Closed;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$errors = [];

		if (empty($arTestProperties["TargetStatus"]))
		{
			$errors[] = array("code" => "NotExist", "parameter" => "TargetStatus", "message" => GetMessage("TASKS_CHANGE_STATUS_EMPTY_PROP"));
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
			'TargetStatus' => [
				'Name' => GetMessage('TASKS_CHANGE_STATUS_STATUS'),
				'FieldName' => 'target_status',
				'Type' => 'select',
				'Required' => true,
				'Options' => [
					Status::PENDING => GetMessage('TASKS_CHANGE_STATUS_PENDING'),
					Status::IN_PROGRESS => GetMessage('TASKS_CHANGE_STATUS_IN_PROGRESS'),
					Status::SUPPOSEDLY_COMPLETED => getMessage('TASKS_CHANGE_STATE_SUPPOSEDLY_COMPLETED'),
					Status::COMPLETED => GetMessage('TASKS_CHANGE_STATUS_COMPLETED'),
					Status::DEFERRED => GetMessage('TASKS_CHANGE_STATUS_DEFERRED'),
				]
			],
			'ModifiedBy' => [
				'Name' => GetMessage('TASKS_CHANGE_STATUS_MODIFIED_BY'),
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
			'TargetStatus' => (int) $arCurrentValues['target_status'],
			'ModifiedBy' => CBPHelper::UsersStringToArray($arCurrentValues["modified_by"], $documentType, $errors)
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

	private function canChangeStatus(&$targetStatus)
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
					$canChange = Tasks\Access\TaskAccessController::can(
						$ownerId,
						Tasks\Access\ActionDictionary::ACTION_TASK_CHANGE_STATUS,
						$taskId
					);
				}
			}
		}

		return $canChange;
	}
}