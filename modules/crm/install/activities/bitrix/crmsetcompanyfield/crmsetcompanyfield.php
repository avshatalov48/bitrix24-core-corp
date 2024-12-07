<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$runtime = CBPRuntime::GetRuntime();
$runtime->IncludeActivityFile('SetFieldActivity');

class CBPCrmSetCompanyField extends CBPSetFieldActivity
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
			$this->writeToTrackingService(
				Loc::getMessage('CRM_ACTIVITY_SET_COMPANY_ERROR'),
				0,
				\CBPTrackingType::Error
			);

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

		try
		{
			if ($this->workflow->isDebug())
			{
				$map = $this->getDebugInfo(
					$fieldValue,
					$documentService->GetDocumentFields(
						\CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::Company)
					)
				);
				$this->writeDebugInfo($map);
			}

			$documentService->UpdateDocument($documentId, $fieldValue, $this->ModifiedBy);
		}
		catch (Exception $e)
		{
			$this->writeToTrackingService($e->getMessage(), 0, CBPTrackingType::Error);
			$this->ErrorMessage = $e->getMessage();
		}

		return CBPActivityExecutionStatus::Closed;
	}

	public static function GetPropertiesDialog(
		$documentType,
		$activityName,
		$arWorkflowTemplate,
		$arWorkflowParameters,
		$arWorkflowVariables,
		$arCurrentValues = null,
		$formName = "",
		$popupWindow = null
	)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return '';
		}

		$documentType = \CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::Company);

		return parent::GetPropertiesDialog(
			$documentType,
			$activityName,
			$arWorkflowTemplate,
			$arWorkflowParameters,
			$arWorkflowVariables,
			$arCurrentValues,
			$formName,
			$popupWindow
		);
	}

	public static function GetPropertiesDialogValues(
		$documentType,
		$activityName,
		&$arWorkflowTemplate,
		&$arWorkflowParameters,
		&$arWorkflowVariables,
		$arCurrentValues,
		&$arErrors
	)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return false;
		}

		$documentType = \CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::Company);

		return parent::GetPropertiesDialogValues(
			$documentType,
			$activityName,
			$arWorkflowTemplate,
			$arWorkflowParameters,
			$arWorkflowVariables,
			$arCurrentValues,
			$arErrors
		);
	}

	private function getCompanyDocumentId()
	{
		$id = null;

		[$entityTypeId, $entityId] = CCrmBizProcHelper::resolveEntityId($this->getDocumentId());

		if ($entityTypeId === \CCrmOwnerType::Lead)
		{
			$entity = \CCrmLead::GetByID($entityId, false);
			if ($entity)
			{
				$id = isset($entity['COMPANY_ID']) ? (int)($entity['COMPANY_ID']) : 0;
			}
		}
		elseif ($entityTypeId === \CCrmOwnerType::Deal)
		{
			$entity = \CCrmDeal::GetByID($entityId, false);
			if ($entity)
			{
				$id = isset($entity['COMPANY_ID']) ? (int)($entity['COMPANY_ID']) : 0;
			}
		}
		elseif ($entityTypeId === \CCrmOwnerType::Contact)
		{
			$entity = \CCrmContact::GetByID($entityId, false);
			if ($entity)
			{
				$id = isset($entity['COMPANY_ID']) ? (int)($entity['COMPANY_ID']) : 0;
			}
		}
		elseif ($entityTypeId === \CCrmOwnerType::Order)
		{
			$entity = \Bitrix\Crm\Order\Order::load($entityId);
			if ($entity)
			{
				$company = $entity->getContactCompanyCollection()->getCompany();

				if ($company)
				{
					$id = $company->getField('ENTITY_ID');
				}
			}
		}
		else
		{
			$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);
			if (isset($factory))
			{
				$entity = $factory->getItem($entityId);
				if ($entity)
				{
					$id = (int)$entity->getCompanyId();
				}
			}
		}

		return $id ? CCrmBizProcHelper::ResolveDocumentId(\CCrmOwnerType::Company, $id) : null;
	}

	public function collectUsages()
	{
		$usages = [];
		$this->collectUsagesRecursive($this->arProperties, $usages);

		return $usages;
	}
}