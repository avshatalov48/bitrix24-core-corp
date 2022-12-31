<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CBPCrmCreateToDoActivity extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			"Title" => "",
			"Description" => null,
			"Deadline" => null,
			"Responsible" => null,
			"AutoComplete" => null,
			//return
			"Id" => null,
		];

		$this->SetPropertiesTypes([
			'Id' => [
				'Type' => 'int',
			],
		]);
	}

	public function Execute()
	{
		if (!CModule::IncludeModule("crm"))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		if (!\Bitrix\Crm\Settings\Crm::isUniversalActivityScenarioEnabled())
		{
			return CBPActivityExecutionStatus::Closed;
		}

		[$ownerTypeId, $ownerId] = \CCrmBizProcHelper::resolveEntityId($this->getDocumentId());

		$responsibleId = $this->getResponsibleId($ownerTypeId, $ownerId);
		$deadline = $this->getDeadline($responsibleId);
		$description = (string)$this->Description;

		$todo = new \Bitrix\Crm\Activity\Entity\ToDo(
			new Bitrix\Crm\ItemIdentifier($ownerTypeId, $ownerId)
		);

		$todo->setDeadline($deadline);
		$todo->setDescription($description);

		if ($responsibleId)
		{
			$todo->setResponsibleId($responsibleId);
		}

		$todo->setCheckPermissions(false);

		if ($this->AutoComplete === 'Y')
		{
			$todo->setAutocompleteRule(\Bitrix\Crm\Activity\AutocompleteRule::AUTOMATION_ON_STATUS_CHANGED);
		}

		if ($this->workflow->isDebug())
		{
			$this->writeDebugInfo($this->getDebugInfo());
		}

		$saveResult = $todo->save();
		if ($saveResult->isSuccess())
		{
			$this->Id = $todo->getId();
		}
		else
		{
			$this->writeToTrackingService(
				$saveResult->getErrorMessages()[0],
				0,
				CBPTrackingType::Error
			);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	private function getResponsibleId($ownerTypeId, $ownerId)
	{
		$id = $this->Responsible;
		if (!$id)
		{
			return CCrmOwnerType::GetResponsibleID($ownerTypeId, $ownerId, false);
		}

		return CBPHelper::ExtractUsers($id, $this->GetDocumentId(), true);
	}

	private function getDeadline($userId): \Bitrix\Main\Type\DateTime
	{
		$offset = $userId ? CTimeZone::GetOffset($userId, true) : 0;
		$ts = \MakeTimeStamp((string)$this->Deadline) ?: time();

		return \Bitrix\Main\Type\DateTime::createFromTimestamp($ts - $offset);
	}

	public static function ValidateProperties($testProperties = [], CBPWorkflowTemplateUser $user = null)
	{
		$errors = [];
		$fieldsMap = static::getPropertiesMap([]);

		foreach ($fieldsMap as $propertyKey => $fieldProperties)
		{
			if (
				CBPHelper::getBool($fieldProperties['Required'])
				&& CBPHelper::isEmptyValue($testProperties[$propertyKey])
			)
			{
				$errors[] = [
					"code" => "NotExist",
					"parameter" => $propertyKey,
					"message" => GetMessage("CRM_BP_CREATE_TODO_EMPTY_PROP", ['#PROPERTY#' => $fieldProperties['Name']]),
				];
			}
		}

		return array_merge($errors, parent::ValidateProperties($testProperties, $user));
	}

	public static function GetPropertiesDialog(
		$documentType,
		$activityName,
		$arWorkflowTemplate,
		$arWorkflowParameters,
		$arWorkflowVariables,
		$arCurrentValues = null,
		$formName = "",
		$popupWindow = null,
		$siteId = ''
	)
	{
		if (!CModule::IncludeModule("crm"))
		{
			return '';
		}

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
		return [
			'Description' => [
				'Name' => GetMessage('CRM_BP_CREATE_TODO_DESCRIPTION'),
				'Description' => GetMessage('CRM_BP_CREATE_TODO_DESCRIPTION'),
				'FieldName' => 'description',
				'Type' => 'text',
				'Required' => true,
			],
			'Deadline' => [
				'Name' => GetMessage('CRM_BP_CREATE_TODO_DEADLINE'),
				'FieldName' => 'deadline',
				'Type' => 'datetime',
			],
			'Responsible' => [
				'Name' => GetMessage('CRM_BP_CREATE_TODO_RESPONSIBLE_ID'),
				'FieldName' => 'responsible',
				'Type' => 'user',
				'Default' => ($documentType
					? \Bitrix\Bizproc\Automation\Helper::getResponsibleUserExpression($documentType)
					: 'author'
				),
			],
			'AutoComplete' => [
				'Name' => GetMessage('CRM_BP_CREATE_TODO_AUTO_COMPLETE_ON_ENTITY_ST_CHG'),
				'FieldName' => 'auto_completed',
				'Type' => 'bool',
				'Default' => 'N',
			],
		];
	}

	public static function GetPropertiesDialogValues(
		$documentType,
		$activityName,
		&$arWorkflowTemplate,
		&$arWorkflowParameters,
		&$arWorkflowVariables,
		$currentValues,
		&$errors
	)
	{
		$runtime = CBPRuntime::GetRuntime();
		$runtime->StartRuntime();

		$errors = $properties = [];
		/** @var CBPDocumentService $documentService */
		$documentService = $runtime->GetService('DocumentService');

		$fieldsMap = static::getPropertiesMap($documentType);
		foreach ($fieldsMap as $propertyKey => $fieldProperties)
		{
			$field = $documentService->getFieldTypeObject($documentType, $fieldProperties);
			if (!$field)
			{
				continue;
			}

			$properties[$propertyKey] = $field->extractValue(
				['Field' => $fieldProperties['FieldName']],
				$currentValues,
				$errors
			);
		}

		//convert special robot datetime interval
		$startTimeFieldsPrefix = $fieldsMap['Deadline']['FieldName'];
		if (
			isset($currentValues[$startTimeFieldsPrefix . '_interval_d'])
			&& isset($currentValues[$startTimeFieldsPrefix . '_interval_t'])
		)
		{
			$interval = ['d' => $currentValues[$startTimeFieldsPrefix . '_interval_d']];
			$time = \Bitrix\Crm\Automation\Helper::parseTimeString(
				$currentValues[$startTimeFieldsPrefix . '_interval_t']
			);
			$interval['h'] = $time['h'];
			$interval['i'] = $time['i'];
			$properties['Deadline'] = \Bitrix\Crm\Automation\Helper::getDateTimeIntervalString($interval);
		}

		$errors = self::ValidateProperties(
			$properties,
			new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser)
		);

		if ($errors)
		{
			return false;
		}

		$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$currentActivity['Properties'] = $properties;

		return true;
	}
}
