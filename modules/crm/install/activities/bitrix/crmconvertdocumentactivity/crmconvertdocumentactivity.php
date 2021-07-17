<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CBPCrmConvertDocumentActivity extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"Items" => array(),
			"DealCategoryId" => 0,
			'DisableActivityCompletion' => 'N'
		);
	}

	public function Execute()
	{
		if ($this->Items == null || !CModule::IncludeModule("crm"))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$documentId = $this->GetDocumentId();
		if ($documentId[0] !== 'crm')
		{
			$this->WriteToTrackingService(GetMessage("CRM_CVTDA_INCORRECT_DOCUMENT"), 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		[$entityTypeName, $entityId] = explode('_', $documentId[2]);
		$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);

		if ($this->isAlreadyConverted($entityTypeId, $entityId))
		{
			$this->WriteToTrackingService(GetMessage("CRM_CVTDA_ALREADY_CONVERTED"), 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		try
		{
			$converter = \Bitrix\Crm\Automation\Converter\Factory::create($entityTypeId, $entityId);
		}
		catch (\Bitrix\Main\NotSupportedException $e)
		{
			$this->WriteToTrackingService(GetMessage('CRM_CVTDA_WIZARD_NOT_FOUND'), 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		if ($this->DisableActivityCompletion === 'Y')
		{
			$converter->enableActivityCompletion(false);
		}

		$options = ['categoryId' => (int)$this->DealCategoryId];

		foreach ($this->Items as $itemName)
		{
			$itemTypeId = \CCrmOwnerType::ResolveID($itemName);
			$converter->setTargetItem($itemTypeId, $options);
		}

		$conversionResult = $converter->execute([
			'USER_ID' => \CCrmOwnerType::GetResponsibleID($entityTypeId, $entityId, false)
		]);

		\Bitrix\Crm\Automation\Factory::registerConversionResult($entityTypeId, $entityId, $conversionResult);

		if(!$conversionResult->isSuccess())
		{
			$errorMessages = $conversionResult->getErrorMessages();

			foreach ($errorMessages as $errorMessage)
			{
				$this->WriteToTrackingService($errorMessage, 0, CBPTrackingType::Error);
			}

			$this->createRequest(implode(', ', $errorMessages));
		}

		return CBPActivityExecutionStatus::Closed;
	}

	private function createRequest($errorText)
	{
		$start = ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL');

		$documentId = $this->GetDocumentId();
		[$typeName, $id] = explode('_', $documentId[2]);
		$typeId = \CCrmOwnerType::ResolveID($typeName);

		$allItems = static::getItemsList($documentId);
		$items = $this->Items;
		foreach ($items as $key => $item)
			$items[$key] = $allItems[$item];

		$responsibleId = \CCrmOwnerType::GetResponsibleID($typeId, $id, false);

		$description = GetMessage('CRM_CVTDA_REQUEST_DESCRIPTION_'.$typeName, array(
			'#ITEMS#' => implode(' + ', $items)
		)) . PHP_EOL . $errorText;

		$activityFields = array(
			'AUTHOR_ID' => $responsibleId,
			'START_TIME' => $start,
			'END_TIME' => $start,
			'SUBJECT' => GetMessage('CRM_CVTDA_REQUEST_SUBJECT_'.$typeName),
			'PRIORITY' => CCrmActivityPriority::Medium,
			'DESCRIPTION' => $description,
			'DESCRIPTION_TYPE' => CCrmContentType::PlainText,
			'PROVIDER_ID' => \Bitrix\Crm\Activity\Provider\Request::getId(),
			'PROVIDER_TYPE_ID' => \Bitrix\Crm\Activity\Provider\Request::getTypeId(array()),
			'RESPONSIBLE_ID' => $responsibleId
		);

		$activityFields['BINDINGS'] = array(
			array('OWNER_TYPE_ID' => $typeId, 'OWNER_ID' => $id)
		);

		if(!($id = CCrmActivity::Add($activityFields, false, true, array('REGISTER_SONET_EVENT' => true))))
		{
			$this->WriteToTrackingService(CCrmActivity::GetLastErrorMessage(), 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		if ($id > 0)
		{
			$this->requestId = $id;
			if ($typeId == \CCrmOwnerType::Lead)
			{
				CCrmActivity::SaveCommunications($id, array(array(
					'ENTITY_ID' => (int)str_replace('LEAD_', '', $documentId[2]),
					'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
					'ENTITY_TYPE' => CCrmOwnerType::LeadName,
					'TYPE' => ''
				)), $activityFields, false, false);
			}
		}
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = array();

		if (empty($arTestProperties["Items"]) || !is_array($arTestProperties["Items"]))
		{
			$arErrors[] = array("code" => "NotExist", "parameter" => "Responsible", "message" => GetMessage("CRM_CVTDA_EMPTY_PROP"));
		}

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "", $popupWindow = null, $siteId = '')
	{
		if (!CModule::IncludeModule("crm"))
			return '';

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

		$map = [
			'Items' => [
				'Name' => GetMessage('CRM_CVTDA_ITEMS'),
				'FieldName' => 'items',
				'Type' => 'select',
				'Required' => true,
				'Multiple' => true,
				'Options' => static::getItemsList($documentType)
			]
		];

		if ($documentType[2] === \CCrmOwnerType::LeadName)
		{
			$map['DealCategoryId'] = [
				'Name' => GetMessage('CRM_CVTDA_DEAL_CATEGORY_ID'),
				'FieldName' => 'deal_category_id',
				'Type' => 'deal_category',
			];

			$map['DisableActivityCompletion'] = [
				'Name' => GetMessage('CRM_CVTDA_DISABLE_ACTIVITY_COMPLETION'),
				'FieldName' => 'disable_activity_completion',
				'Type' => 'bool',
				'Default' => 'Y'
			];
		}

		$dialog->setMap($map);

		return $dialog;
	}

	private static function getItemsList($documentType)
	{
		$items = array();

		if ($documentType[1] == 'CCrmDocumentLead')
		{
			$items = array(
				\CCrmOwnerType::DealName => GetMessage('CRM_CVTDA_DEAL'),
				\CCrmOwnerType::ContactName => GetMessage('CRM_CVTDA_CONTACT'),
				\CCrmOwnerType::CompanyName => GetMessage('CRM_CVTDA_COMPANY'),
			);
		}
		elseif ($documentType[1] == 'CCrmDocumentDeal')
		{
			$items = array(
				\CCrmOwnerType::InvoiceName => GetMessage('CRM_CVTDA_INVOICE'),
				\CCrmOwnerType::QuoteName => GetMessage('CRM_CVTDA_QUOTE'),
			);
		}

		return $items;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		$errors = [];

		$arProperties = array(
			'Items' => $arCurrentValues['items'],
			'DealCategoryId' => $arCurrentValues['deal_category_id'] ?? 0,
			'DisableActivityCompletion' => $arCurrentValues['disable_activity_completion']
		);

		if ($arProperties['DealCategoryId'] === '' && static::isExpression($arCurrentValues['deal_category_id_text']))
		{
			$arProperties['DealCategoryId'] = $arCurrentValues['deal_category_id_text'];
		}

		if (
			$arProperties['DisableActivityCompletion'] === ''
			&& static::isExpression($arCurrentValues['disable_activity_completion_text']))
		{
			$arProperties['DisableActivityCompletion'] = $arCurrentValues['disable_activity_completion_text'];
		}

		$errors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($errors) > 0)
		{
			return false;
		}

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $arProperties;

		return true;
	}

	private function isAlreadyConverted(int $entityTypeId, string $entityId): bool
	{
		if ($entityTypeId === \CCrmOwnerType::Lead)
		{
			$result = \CCrmLead::GetListEx(
				array(),
				array('=ID' => $entityId, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('STATUS_ID')
			);
			$presentFields = is_object($result) ? $result->Fetch() : null;

			if ($presentFields && $presentFields['STATUS_ID'] === 'CONVERTED')
			{
				return true;
			}
		}

		return false;
	}
}
