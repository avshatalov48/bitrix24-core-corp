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
		$documentType = CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::Company);

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

		if (method_exists($this, 'prepareFieldsValues'))
		{
			$fieldValue = $this->prepareFieldsValues($documentId, $documentType, $fieldValue);
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

		list($entityType, $entityId) = mb_split('_(?=[^_]*$)', $this->GetDocumentId()[2]);
		$entityTypeId = CCrmOwnerType::ResolveID($entityType);

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
		elseif ($entityType === \CCrmOwnerType::ContactName)
		{
			$entity = \CCrmContact::GetByID($entityId, false);
			$id = isset($entity['COMPANY_ID']) ? intval($entity['COMPANY_ID']) : 0;
		}
		else
		{
			$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);
			if (isset($factory) && $factory->isAutomationEnabled())
			{
				$entity = $factory->getItem($entityId);
				$id = (int)$entity->getCompanyId();
			}
		}

		return $id ? CCrmBizProcHelper::ResolveDocumentId(\CCrmOwnerType::Company, $id) : null;
	}
}