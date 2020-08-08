<?

use Bitrix\Bizproc\WorkflowInstanceTable;
use Bitrix\Crm;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

class CBPCrmChangeDealCategoryActivity
	extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			'Title' => '',
			'CategoryId' => 0,
		);
	}

	public function Execute()
	{
		if (!CModule::IncludeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$sourceDealId = explode('_', $this->GetDocumentId()[2])[1];
		$sourceFields = [];

		if($sourceDealId > 0)
		{
			$dbResult = \CCrmDeal::GetListEx(
				array(),
				array('=ID' => $sourceDealId, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID', 'ASSIGNED_BY_ID', 'STAGE_ID', 'CATEGORY_ID')
			);
			$sourceFields = $dbResult->Fetch();
		}

		if (!$sourceFields)
		{
			$this->WriteToTrackingService(GetMessage('CRM_CDCA_NO_SOURCE_FIELDS'), 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		$resultError = \CCrmDeal::MoveToCategory(
			$sourceDealId,
			(int) $this->CategoryId,
			[
				'ENABLE_WORKFLOW_CHECK' => false,
				'USER_ID' => $sourceFields['ASSIGNED_BY_ID']
			]
		);

		if ($resultError === Crm\Category\DealCategoryChangeError::NONE)
		{
			//stop all Workflows
			$documentId = $this->GetDocumentId();
			$instanceIds = WorkflowInstanceTable::getIdsByDocument($documentId);
			$instanceIds[] = $this->GetWorkflowInstanceId();
			$instanceIds = array_unique($instanceIds);

			foreach ($instanceIds as $instanceId)
			{
				\CBPDocument::TerminateWorkflow(
					$instanceId,
					$documentId,
					$errors,
					GetMessage('CRM_CDCA_MOVE_TERMINATION_TITLE')
				);
			}

			//Fake document update for clearing document cache
			/** @var CBPDocumentService $ds */
			$ds = $this->workflow->GetService('DocumentService');
			$ds->UpdateDocument($documentId, []);

			$dbResult = \CCrmDeal::GetListEx(
				array(),
				array('=ID' => $sourceDealId, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID', 'ASSIGNED_BY_ID', 'STAGE_ID', 'CATEGORY_ID')
			);
			$newFields = $dbResult->Fetch();


			//Region automation
			$starter = new \Bitrix\Crm\Automation\Starter(\CCrmOwnerType::Deal, $sourceDealId);
			$starter->setContextToBizproc();
			$starter->runOnUpdate($newFields, $sourceFields);
			//End region

			//Stop running queue
			throw new Exception("TerminateWorkflow");
		}
		else
		{
			$this->WriteToTrackingService(
				$this->resolveMoveCategoryErrorText($resultError),
				0,
				CBPTrackingType::Error
			);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$errors = [];

		if ($arTestProperties["CategoryId"] === null || $arTestProperties["CategoryId"] === '')
		{
			$errors[] = array("code" => "NotExist", "parameter" => "CategoryId", "message" => GetMessage("CRM_CDCA_EMPTY_CATEGORY"));
		}

		return array_merge($errors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = '', $popupWindow = null, $siteId = '')
	{
		if (!CModule::IncludeModule("crm"))
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

		$dialog->setMap(array(
			'CategoryId' => array(
				'Name' => GetMessage('CRM_CDCA_CATEGORY'),
				'FieldName' => 'category_id',
				'Type' => 'select',
				'Options' => \Bitrix\Crm\Category\DealCategory::getSelectListItems(),
			)
		));

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		$arProperties = ['CategoryId' => $arCurrentValues['category_id']];

		$errors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($errors) > 0)
		{
			return false;
		}

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $arProperties;

		return true;
	}

	private function resolveMoveCategoryErrorText($errorCode)
	{
		switch ($errorCode)
		{
			case Crm\Category\DealCategoryChangeError::CATEGORY_NOT_FOUND:
			{
				$text = GetMessage('CRM_CDCA_MOVE_ERROR_CATEGORY_NOT_FOUND');
				break;
			}
			case Crm\Category\DealCategoryChangeError::CATEGORY_NOT_CHANGED:
			{
				$text = GetMessage('CRM_CDCA_MOVE_ERROR_CATEGORY_NOT_CHANGED');
				break;
			}
			case Crm\Category\DealCategoryChangeError::RESPONSIBLE_NOT_FOUND:
			{
				$text = GetMessage('CRM_CDCA_MOVE_ERROR_RESPONSIBLE_NOT_FOUND');
				break;
			}
			case Crm\Category\DealCategoryChangeError::STAGE_NOT_FOUND:
			{
				$text = GetMessage('CRM_CDCA_MOVE_ERROR_STAGE_NOT_FOUND');
				break;
			}

			default:
			{
				$text = GetMessage('CRM_CDCA_MOVE_ERROR', ['#ERROR_CODE#' => $errorCode]);
			}
		}

		return $text;
	}
}