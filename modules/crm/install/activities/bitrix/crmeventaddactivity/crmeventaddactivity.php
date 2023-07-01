<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CBPCrmEventAddActivity extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			'Title' => '',
			'EventType' => '',
			'EventText' => '',
			'EventUser' => null
		);
	}

	public function Execute()
	{
		if (!CModule::IncludeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$rootActivity = $this->GetRootActivity();
		$documentId = $rootActivity->GetDocumentId();
		$arDocumentInfo = mb_split('_(?=[^_]*$)', $documentId[2]);

		$userId = CBPHelper::ExtractUsers($this->EventUser, $documentId, true);

		$arEntity[$arDocumentInfo[1]] = array(
			'ENTITY_TYPE' => $arDocumentInfo[0],
			'ENTITY_ID' => (int) $arDocumentInfo[1],
			'USER_ID' => $userId ?: 0,
		);

		$arFields = array(
			'ENTITY'  => $arEntity,
			'EVENT_ID' => $this->EventType,
			'EVENT_TEXT_1' => $this->getEventText(),
			'USER_ID' => $userId ?: 0,
		);
		$CCrmEvent = new CCrmEvent();

		if ($this->workflow->isDebug())
		{
			$this->writeDebugInfo($this->getDebugInfo());
		}

		if (!$CCrmEvent->Add($arFields, false))
		{
			global $APPLICATION;
			$e = $APPLICATION->GetException();
			$this->WriteToTrackingService($e->GetString(), 0, CBPTrackingType::Error);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$errors = [];

		if (!array_key_exists('EventType', $arTestProperties) || $arTestProperties['EventType'] == '')
		{
			$errors[] = array('code' => 'NotExist', 'parameter' => 'EventType', 'message' => GetMessage('BPEAA_EMPTY_TYPE'));
		}
		if (!array_key_exists('EventText', $arTestProperties) || $arTestProperties['EventText'] == '')
		{
			$errors[] = array('code' => 'NotExist', 'EventText' => 'MessageText', 'message' => GetMessage('BPEAA_EMPTY_MESSAGE'));
		}

		return array_merge($errors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = '', $popupWindow = null, $siteId = '')
	{
		if (!CModule::IncludeModule('crm'))
		{
			return false;
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
		$errors = [];

		$properties = [
			'EventType' => $arCurrentValues['event_type'],
			'EventText' => $arCurrentValues['event_text'],
			'EventUser' => CBPHelper::UsersStringToArray($arCurrentValues["event_user"], $documentType, $errors),
		];

		if (count($errors) > 0)
		{
			return false;
		}

		$errors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($errors) > 0)
		{
			return false;
		}

		$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$currentActivity['Properties'] = $properties;

		return true;
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		return [
			'EventType' => [
				'Name' => \Bitrix\Main\Localization\Loc::getMessage('BPEAA_EVENT_TYPE'),
				'FieldName' => 'event_type',
				'Type' => 'select',
				'Required' => true,
				'Options' => CCrmStatus::GetStatusList('EVENT_TYPE'),
			],
			'EventText' => [
				'Name' => \Bitrix\Main\Localization\Loc::getMessage('BPEAA_EVENT_TEXT'),
				'Description' => \Bitrix\Main\Localization\Loc::getMessage('BPEAA_EVENT_TEXT'),
				'FieldName' => 'event_text',
				'Type' => 'text',
				'Required' => true,
			],
			'EventUser' => [
				'Name' => \Bitrix\Main\Localization\Loc::getMessage('BPEAA_EVENT_USER'),
				'FieldName' => 'event_user',
				'Type' => 'user'
			]
		];
	}

	protected function getDebugInfo(array $values = [] , array $map = []): array
	{
		$onlyDesignerFields = ['EventType'];

		if (count($map) <= 0)
		{
			$map = static::getPropertiesMap($this->getDocumentType());
		}

		// temporary
		foreach ($onlyDesignerFields as $key)
		{
			unset($map[$key]);
		}

		return parent::getDebugInfo($values, $map);
	}

	private function getEventText(): string
	{
		$text = $this->ParseValue($this->getRawProperty('EventText'), 'text');
		if (is_array($text))
		{
			$text = implode(', ', \CBPHelper::MakeArrayFlat($text));
		}

		return (string)$text;
	}
}
