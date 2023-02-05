<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;

class CBPCrmResourceBooking extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			"Title" => "",
			"ResourceField" => null,
			"ResourceName" => null,
			"ResourceStart" => null,
			"ResourceDuration" => null,
			"ResourceUsers" => null,

			//reserved
			"ResourceId" => null,
		];
	}

	public function Execute()
	{
		if (!CModule::IncludeModule("crm"))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$name = $this->ResourceName;
		$start = $this->getResourceStart();
		$duration = (int) $this->ResourceDuration;

		$fieldId = $this->ResourceField;

		if ($this->workflow->isDebug())
		{
			$this->writeDebugInfo($this->getDebugInfo(
				[
					'ResourceField' => $fieldId,
					'ResourceName' => $name,
					'ResourceStart' => $start,
					'ResourceDuration' => $duration,
				]
			));
		}

		if (!$start)
		{
			$this->WriteToTrackingService(GetMessage("CRM_RB_ERROR_START_DATE"), 0, CBPTrackingType::Error);

			return CBPActivityExecutionStatus::Closed;
		}

		if (!$duration)
		{
			$this->WriteToTrackingService(GetMessage("CRM_RB_ERROR_DURATION_EMPTY"), 0, CBPTrackingType::Error);

			return CBPActivityExecutionStatus::Closed;
		}

		$users = \CBPHelper::ExtractUsers($this->ResourceUsers, $this->GetDocumentId());

		if (!$users)
		{
			$this->WriteToTrackingService(GetMessage("CRM_RB_ERROR_RESOURCE_USERS"), 0, CBPTrackingType::Error);

			return CBPActivityExecutionStatus::Closed;
		}

		$fieldValue[$fieldId] = [];

		foreach ($users as $userId)
		{
			$fieldValue[$fieldId][] = implode('|', [
				'user',
				$userId,
				(string) $start,
				$duration,
				$name
			]);
		}

		$documentService = $this->workflow->GetService("DocumentService");
		$documentService->UpdateDocument($this->GetDocumentId(), $fieldValue);

		return CBPActivityExecutionStatus::Closed;
	}

	public static function ValidateProperties($testProps = array(), CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = array();

		if (empty($testProps["ResourceField"]))
		{
			$arErrors[] = array("code" => "NotExist", "parameter" => "ResourceField", "message" => GetMessage("CRM_RB_ERROR_FIELD"));
		}
		if (empty($testProps["ResourceStart"]))
		{
			$arErrors[] = array("code" => "NotExist", "parameter" => "ResourceStart", "message" => GetMessage("CRM_RB_ERROR_START_DATE"));
		}
		if (empty($testProps["ResourceDuration"]))
		{
			$arErrors[] = array("code" => "NotExist", "parameter" => "ResourceDuration", "message" => GetMessage("CRM_RB_ERROR_DURATION_EMPTY"));
		}
		if (empty($testProps["ResourceUsers"]))
		{
			$arErrors[] = array("code" => "NotExist", "parameter" => "ResourceUsers", "message" => GetMessage("CRM_RB_ERROR_RESOURCE_USERS"));
		}

		return array_merge($arErrors, parent::ValidateProperties($testProps, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "", $popupWindow = null, $siteId = '')
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

		$dialog->setMap(static::getPropertiesMap($documentType));

		return $dialog;
	}

	private static function getDurationOptions()
	{
		$periods = [
			300, 600, 900, 1200, 1500, 1800, 2100, 2400, 2700, 3000, 3300,
			3600, 5400, 7200, 10800, 14400, 18000, 21600,
			86400, 172800, 259200, 345600, 432000, 518400, 604800, 864000
		];

		$options = [];
		foreach ($periods as $p)
		{
			$options[$p] = \CBPHelper::FormatTimePeriod($p);
		}

		return $options;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		$arProperties = array(
			'ResourceField' => $arCurrentValues['resource_field'],
			'ResourceName' => $arCurrentValues['resource_name'],
			'ResourceStart' => empty($arCurrentValues['resource_start'])
				? $arCurrentValues['resource_start_text'] : $arCurrentValues['resource_start'],
			'ResourceDuration' => $arCurrentValues['resource_duration'],
			'ResourceUsers' => \CBPHelper::UsersStringToArray($arCurrentValues['resource_users'], $documentType, $errors),
		);

		if (!$errors)
		{
			$errors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		}

		if ($errors)
		{
			return false;
		}

		$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$currentActivity["Properties"] = $arProperties;

		return true;
	}

	private static function getResourceFields(array $documentType)
	{
		$documentFields = \CBPRuntime::GetRuntime()
			->GetService('DocumentService')
			->GetDocumentFields($documentType);

		$result = [];
		foreach ($documentFields as $id => $field)
		{
			if ($field['Type'] === 'UF:resourcebooking')
			{
				$result[$id] = $field['Name'];
			}
		}
		return $result;
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		return [
			'ResourceField' => array(
				'Name' => \Bitrix\Main\Localization\Loc::getMessage('CRM_RB_RESOURCE_FIELD'),
				'FieldName' => 'resource_field',
				'Type' => 'select',
				'Required' => true,
				'Options' => self::getResourceFields($documentType)
			),
			'ResourceName' => array(
				'Name' => \Bitrix\Main\Localization\Loc::getMessage('CRM_RB_RESOURCE_NAME'),
				'Description' => \Bitrix\Main\Localization\Loc::getMessage('CRM_RB_RESOURCE_NAME'),
				'FieldName' => 'resource_name',
				'Type' => 'string',
			),
			'ResourceStart' => array(
				'Name' => \Bitrix\Main\Localization\Loc::getMessage('CRM_RB_RESOURCE_START_DATE'),
				'FieldName' => 'resource_start',
				'Type' => 'datetime',
				'Required' => true
			),
			'ResourceDuration' => array(
				'Name' => \Bitrix\Main\Localization\Loc::getMessage('CRM_RB_RESOURCE_DURATION'),
				'FieldName' => 'resource_duration',
				'Type' => 'select',
				'Required' => true,
				'Options' => self::getDurationOptions()
			),
			'ResourceUsers' => array(
				'Name' => \Bitrix\Main\Localization\Loc::getMessage('CRM_RB_RESOURCE_USERS'),
				'FieldName' => 'resource_users',
				'Type' => 'user',
				'Required' => true,
				'Multiple' => true,
				'Default' => \Bitrix\Bizproc\Automation\Helper::getResponsibleUserExpression($documentType),
			),
		];
	}

	/**
	 * @return mixed|null
	 */
	private function getResourceStart()
	{
		$start = $this->ResourceStart;

		if (is_array($start))
		{
			$start = current(\CBPHelper::makeArrayFlat($start));
		}

		return $start ? Main\Type\DateTime::createFromUserTime((string)$start) : null;
	}
}
