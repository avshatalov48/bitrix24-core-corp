<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm;

class CBPCrmConvertDocumentActivity extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Responsible' => null,
			"Title" => "",
			"Items" => [],
			"DealCategoryId" => 0,
			'DisableActivityCompletion' => 'N',

			//return
			'InvoiceId' => null,
			'QuoteId' => null,
			'DealId' => null,
			'ContactId' => null,
			'CompanyId' => null,
		];

		$this->SetPropertiesTypes([
			'InvoiceId' => [
				'Type' => 'int',
			],
			'QuoteId' => [
				'Type' => 'int',
			],
			'DealId' => [
				'Type' => 'int',
			],
			'ContactId' => [
				'Type' => 'int',
			],
			'CompanyId' => [
				'Type' => 'int',
			],
		]);
	}

	protected function ReInitialize()
	{
		parent::ReInitialize();
		$this->InvoiceId = null;
		$this->QuoteId = null;
		$this->DealId = null;
		$this->ContactId = null;
		$this->CompanyId = null;
	}

	public function Execute()
	{
		if ($this->Items == null || !CModule::IncludeModule("crm"))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$this->logDebug();

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
			$this->WriteToTrackingService(GetMessage("CRM_CVTDA_ALREADY_CONVERTED_1"), 0, CBPTrackingType::Error);

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

		$responsibleId = CBPHelper::ExtractUsers($this->Responsible, $documentId, true);
		$conversionResult = $converter->execute([
			'USER_ID' => $responsibleId,
			'RESPONSIBLE_ID' => $responsibleId,
		]);

		\Bitrix\Crm\Automation\Factory::registerConversionResult($entityTypeId, $entityId, $conversionResult);

		if ($conversionResult->isSuccess())
		{
			$this->setReturnIds($conversionResult);
			$this->onSuccessConversion($entityTypeId, $entityId);
		}
		else
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

	private function setReturnIds(Crm\Automation\Converter\Result $result): void
	{
		$propertyMap = [
			\CCrmOwnerType::Invoice => 'InvoiceId',
			\CCrmOwnerType::SmartInvoice => 'InvoiceId',
			\CCrmOwnerType::Quote => 'QuoteId',
			\CCrmOwnerType::Deal => 'DealId',
			\CCrmOwnerType::Contact => 'ContactId',
			\CCrmOwnerType::Company => 'CompanyId',
		];

		/** @var Crm\Entity\Identificator\Complex $boundEntity */
		foreach ($result->getBoundEntities() as $boundEntity)
		{
			$key = $propertyMap[$boundEntity->getTypeId()];
			if ($key)
			{
				$this->__set($key, $boundEntity->getId());
				$this->logDebugResult($key, $boundEntity->getTypeId(), $boundEntity->getId());
			}
		}
	}

	private function onSuccessConversion($entityTypeId, $entityId)
	{
		if (
			$entityTypeId === \CCrmOwnerType::Lead
			&& $this->GetRootActivity()->getDocumentEventType() === CBPDocumentEventType::Automation
		)
		{
			$this->workflow->Terminate();
			throw new \Bitrix\Main\SystemException('TerminateActivity');
		}
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

		$description = GetMessage('CRM_CVTDA_REQUEST_DESCRIPTION_' . $typeName, [
				'#ITEMS#' => implode(' + ', $items),
			]) . PHP_EOL . $errorText;

		$activityFields = [
			'AUTHOR_ID' => $responsibleId,
			'START_TIME' => $start,
			'END_TIME' => $start,
			'SUBJECT' => GetMessage('CRM_CVTDA_REQUEST_SUBJECT_' . $typeName),
			'PRIORITY' => CCrmActivityPriority::Medium,
			'DESCRIPTION' => $description,
			'DESCRIPTION_TYPE' => CCrmContentType::PlainText,
			'PROVIDER_ID' => \Bitrix\Crm\Activity\Provider\Request::getId(),
			'PROVIDER_TYPE_ID' => \Bitrix\Crm\Activity\Provider\Request::getTypeId([]),
			'RESPONSIBLE_ID' => $responsibleId,
		];

		$activityFields['BINDINGS'] = [
			[
				'OWNER_TYPE_ID' => $typeId,
				'OWNER_ID' => $id,
			],
		];

		if (!($id = CCrmActivity::Add($activityFields, false, true, ['REGISTER_SONET_EVENT' => true])))
		{
			$this->WriteToTrackingService(CCrmActivity::GetLastErrorMessage(), 0, CBPTrackingType::Error);

			return CBPActivityExecutionStatus::Closed;
		}

		if ($id > 0)
		{
			$this->requestId = $id;
			if ($typeId == \CCrmOwnerType::Lead)
			{
				CCrmActivity::SaveCommunications($id, [[
					'ENTITY_ID' => (int)str_replace('LEAD_', '', $documentId[2]),
					'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
					'ENTITY_TYPE' => CCrmOwnerType::LeadName,
					'TYPE' => '',
				]], $activityFields, false, false);
			}
		}
	}

	public static function ValidateProperties($arTestProperties = [], CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = [];

		if (empty($arTestProperties["Items"]) || !is_array($arTestProperties["Items"]))
		{
			$arErrors[] = ["code" => "NotExist", "parameter" => "Responsible", "message" => GetMessage("CRM_CVTDA_EMPTY_PROP")];
		}

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "", $popupWindow = null, $siteId = '')
	{
		if (!CModule::IncludeModule("crm"))
			return '';

		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, [
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $arWorkflowTemplate,
			'workflowParameters' => $arWorkflowParameters,
			'workflowVariables' => $arWorkflowVariables,
			'currentValues' => $arCurrentValues,
			'formName' => $formName,
			'siteId' => $siteId,
		]);

		$dialog->setMap(static::getPropertiesMap($documentType));

		return $dialog;
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		$map = [
			'Responsible' => [
				'Name' => GetMessage("CRM_CVTDA_RESPONSIBLE"),
				'FieldName' => 'responsible',
				'Type' => 'user',
				'Required' => true,
				'Default' => Bitrix\Bizproc\Automation\Helper::getResponsibleUserExpression($documentType),
			],
			'Items' => [
				'Name' => GetMessage('CRM_CVTDA_ITEMS'),
				'FieldName' => 'items',
				'Type' => 'select',
				'Required' => true,
				'Multiple' => true,
				'Options' => static::getItemsList($documentType),
			],
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
				'Default' => 'Y',
			];
		}

		return $map;
	}

	private static function getItemsList($documentType)
	{
		$items = [];

		if ($documentType[1] === 'CCrmDocumentLead')
		{
			$items = [
				\CCrmOwnerType::DealName => GetMessage('CRM_CVTDA_DEAL'),
				\CCrmOwnerType::ContactName => GetMessage('CRM_CVTDA_CONTACT'),
				\CCrmOwnerType::CompanyName => GetMessage('CRM_CVTDA_COMPANY'),
			];
		}
		elseif ($documentType[1] === 'CCrmDocumentDeal')
		{
			$dealConfig = Crm\Conversion\ConversionManager::getConfig(\CCrmOwnerType::Deal);

			if ($dealConfig)
			{
				foreach ($dealConfig->getItems() as $configItem)
				{
					$dstTypeName = \CCrmOwnerType::ResolveName($configItem->getEntityTypeID());
					$items[$dstTypeName] = \CCrmOwnerType::GetDescription($configItem->getEntityTypeID());
				}
			}
		}

		return $items;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		$errors = [];

		$arProperties = [
			'Responsible' => CBPHelper::UsersStringToArray($arCurrentValues["responsible"], $documentType, $errors),
			'Items' => $arCurrentValues['items'],
			'DealCategoryId' => $arCurrentValues['deal_category_id'] ?? 0,
			'DisableActivityCompletion' => $arCurrentValues['disable_activity_completion'],
		];

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
				[],
				['=ID' => $entityId, 'CHECK_PERMISSIONS' => 'N'],
				false,
				false,
				['STATUS_ID']
			);
			$presentFields = is_object($result) ? $result->Fetch() : null;

			if ($presentFields && $presentFields['STATUS_ID'] === 'CONVERTED')
			{
				return true;
			}
		}

		return false;
	}

	private function logDebug()
	{
		if ($this->workflow->isDebug())
		{
			$debugInfo = $this->getDebugInfo();
			$this->writeDebugInfo($debugInfo);
		}
	}

	private function logDebugResult($fieldId, $entityTypeId, $entityId)
	{
		if ($this->workflow->isDebug())
		{
			$debugInfo = $this->getDebugInfo(
				[$fieldId => $entityId],
				[$fieldId => \CCrmOwnerType::GetDescription($entityTypeId)]
			);
			$this->writeDebugInfo($debugInfo);
		}
	}
}
