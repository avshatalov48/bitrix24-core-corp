<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CBPCrmResourceBookingCancel extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"ResourceField" => null,

			//reserved
			"ResourceId" => null,
		);
	}

	public function Execute()
	{
		if (!CModule::IncludeModule("crm"))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$fieldId = $this->ResourceField;

		$fieldValue[$fieldId] = ['empty'];

		$this->writeDebugInfo($this->getDebugInfo(['ResourceField' => $fieldId]));

		$documentService = $this->workflow->GetService("DocumentService");
		$documentService->UpdateDocument($this->GetDocumentId(), $fieldValue);

		return CBPActivityExecutionStatus::Closed;
	}

	public static function ValidateProperties($testProps = array(), CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = array();

		if (empty($testProps["ResourceField"]))
		{
			$arErrors[] = array("code" => "NotExist", "parameter" => "ResourceField", "message" => GetMessage("CRM_RBC_ERROR_FIELD"));
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

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		$arProperties = array(
			'ResourceField' => $arCurrentValues['resource_field'],
		);

		$errors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));

		if ($errors)
		{
			return false;
		}

		$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$currentActivity["Properties"] = $arProperties;

		return true;
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		return [
			'ResourceField' => [
				'Name' => GetMessage('CRM_RBC_RESOURCE_FIELD'),
				'FieldName' => 'resource_field',
				'Type' => 'select',
				'Required' => true,
				'Options' => self::getResourceFields($documentType)
			],
		];
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
}
