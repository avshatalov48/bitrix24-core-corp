<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$runtime = CBPRuntime::GetRuntime();
$runtime->IncludeActivityFile('SetFieldActivity');

class CBPCrmSetContactField extends CBPSetFieldActivity
{
	public function Execute()
	{
		if (!CModule::IncludeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$documentId = $this->getContactDocumentId();
		$documentType = CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::Contact);

		if (!$documentId)
		{
			$this->WriteToTrackingService(GetMessage('CRM_ACTIVITY_SET_CONTACT_ERROR'), 0, \CBPTrackingType::Error);

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
					$documentService->GetDocumentFields(\CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::Contact))
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

		$documentType = \CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::Contact);

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

		$documentType = \CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::Contact);

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

	private function getContactDocumentId()
	{
		$id = null;

		[$entityTypeId, $entityId] = CCrmBizProcHelper::resolveEntityId($this->getDocumentId());

		if ($entityTypeId === \CCrmOwnerType::Lead)
		{
			$entity = \CCrmLead::GetByID($entityId, false);
			if ($entity)
			{
				$id = isset($entity['CONTACT_ID']) ? (int)($entity['CONTACT_ID']) : 0;
			}
		}
		elseif ($entityTypeId === \CCrmOwnerType::Deal)
		{
			$entity = \CCrmDeal::GetByID($entityId, false);
			if ($entity)
			{
				$id = isset($entity['CONTACT_ID']) ? (int)($entity['CONTACT_ID']) : 0;
			}
		}
		elseif ($entityTypeId === \CCrmOwnerType::Order)
		{
			$entity = \Bitrix\Crm\Order\Order::load($entityId);
			if ($entity)
			{
				$contacts = $entity->getContactCompanyCollection()->getContacts();

				foreach ($contacts as $contact)
				{
					$id = $contact->getField('ENTITY_ID');
					break;
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
					$id = (int)$entity->getContactId();
				}
			}
		}

		return $id ? CCrmBizProcHelper::ResolveDocumentId(\CCrmOwnerType::Contact, $id) : null;
	}

	public function collectUsages()
	{
		$usages = [];
		$this->collectUsagesRecursive($this->arProperties, $usages);

		return $usages;
	}
}