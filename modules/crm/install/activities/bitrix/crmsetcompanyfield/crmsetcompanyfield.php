<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

$runtime = CBPRuntime::GetRuntime();
$runtime->IncludeActivityFile('SetFieldActivity');

class CBPCrmSetCompanyField
	extends CBPSetFieldActivity
{
	public function Execute()
	{
		if (!CModule::IncludeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$documentId = $this->getCompanyDocumentId();

		if (!$documentId)
		{
			$this->WriteToTrackingService(GetMessage('CRM_ACTIVITY_SET_COMPANY_ERROR'), 0, \CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		$fieldValue = $this->FieldValue;

		if (!is_array($fieldValue) || count($fieldValue) <= 0)
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$documentService = $this->workflow->GetService("DocumentService");
		$documentService->UpdateDocument($documentId, $fieldValue, $this->ModifiedBy);

		return CBPActivityExecutionStatus::Closed;
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "", $popupWindow = null)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return '';
		};

		$documentType = \CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::Company);
		return parent::GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues, $formName, $popupWindow);
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return false;
		};

		$documentType = \CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::Company);
		return parent::GetPropertiesDialogValues($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues, $arErrors);
	}

	private function getCompanyDocumentId()
	{
		$id = null;

		list($entityType, $entityId) = explode('_', $this->GetDocumentId()[2]);

		if ($entityType === \CCrmOwnerType::LeadName)
		{
			$entity = \CCrmLead::GetByID($entityId, false);
			$id = isset($entity['COMPANY_ID']) ? intval($entity['COMPANY_ID']) : 0;
		}
		elseif ($entityType === \CCrmOwnerType::DealName)
		{
			$entity = \CCrmDeal::GetByID($entityId, false);
			$id = isset($entity['COMPANY_ID']) ? intval($entity['COMPANY_ID']) : 0;
		}

		return $id ? CCrmBizProcHelper::ResolveDocumentId(\CCrmOwnerType::Company, $id) : null;
	}
}