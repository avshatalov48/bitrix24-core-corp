<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

class CBPCrmSetObserverField
	extends CBPActivity
{
	const ACTION_ADD_OBSERVERS = 'add';
	const ACTION_REMOVE_OBSERVERS = 'remove';
	const ACTION_REPLACE_OBSERVERS = 'replace';

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			'Title' => '',
			'ActionOnObservers' => null,
			'Observers' => null,
		);
	}

	public function Execute()
	{
		if (!CModule::IncludeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$documentId = explode("_", $this->GetDocumentId()[2])[1];
		$observerIds = CBPHelper::ExtractUsers($this->Observers, $this->GetDocumentId());

		$classHandler = $this->defineClassHandler();
		$methodHandler = $this->defineMethodHandler();

		if(is_null($classHandler) || is_null($methodHandler))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		call_user_func([$classHandler, $methodHandler], $documentId, $observerIds);

		return CBPActivityExecutionStatus::Closed;
	}

	protected function defineClassHandler() : ?string
	{
		$documentType = $this->GetDocumentType()[2];
		if ($documentType === CCrmOwnerType::DealName)
		{
			return CCrmDeal::class;
		}
		elseif ($documentType === CCrmOwnerType::LeadName)
		{
			return CCrmLead::class;
		}
		else
		{
			return null;
		}
	}

	protected function defineMethodHandler() : ?string
	{
		$actions = self::getActionsOnObservers();
		switch($this->ActionOnObservers)
		{
			case self::ACTION_ADD_OBSERVERS:
				return 'AddObserverIDs';
			case self::ACTION_REMOVE_OBSERVERS:
				return 'RemoveObserverIDs';
			case self::ACTION_REPLACE_OBSERVERS:
				return 'ReplaceObserverIDs';
			default:
				return null;
		}
	}

	public static function GetPropertiesDialog($documentType, $activityName, $workflowTemplate, $workflowParameters, $workflowVariables, $currentValues = null, $formName = '', $popupWindow = null, $siteId = '')
	{
		if(!CModule::IncludeModule('crm'))
		{
			return '';
		}

		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, array(
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $workflowTemplate,
			'workflowParameters' => $workflowParameters,
			'workflowVariables' => $workflowVariables,
			'currentValues' => $currentValues,
			'formName' => $formName,
			'siteId' => $siteId
		));

		$dialog->setMap(self::GetPropertiesDialogMap());

		return $dialog;
	}

	protected static function GetPropertiesDialogMap()
	{
		return array(
			'ActionOnObservers' => array(
				'Name' => GetMessage("CRM_SOF_ACTION_ON_OBSERVERS"),
				'FieldName' => 'action_on_observers',
				'Type' => \Bitrix\Bizproc\FieldType::SELECT,
				'Required' => true,
				'Options' => self::getActionsOnObservers(),
				'Default' => self::ACTION_ADD_OBSERVERS,
			),
			'Observers' => array(
				'Name' => GetMessage("CRM_SOF_OBSERVERS"),
				'FieldName' => 'observers',
				'Type' => \Bitrix\Bizproc\FieldType::USER,
				'Required' => true,
				'Multiple' => true,
			)
		);
	}

	protected static function getActionsOnObservers()
	{
		return array(
			self::ACTION_ADD_OBSERVERS => getMessage('CRM_SOF_ACTION_ADD_OBSERVERS'),
			self::ACTION_REMOVE_OBSERVERS => getMessage('CRM_SOF_ACTION_REMOVE_OBSERVERS'),
			self::ACTION_REPLACE_OBSERVERS => GetMessage('CRM_SOF_ACTION_REPLACE_OBSERVERS'),
		);
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$workflowTemplate, &$workflowParameters, &$workflowVariables, $currentValues, &$errors)
	{
		$runtime = CBPRuntime::GetRuntime();
		$runtime->StartRuntime();

		$properties = [];
		$documentService = $runtime->GetService('DocumentService');

		foreach (self::GetPropertiesDialogMap() as $propertyKey => $fieldProperties)
		{
			$field = $documentService->getFieldTypeObject($documentType, $fieldProperties);
			if(!$field)
				continue;

			$properties[$propertyKey] = $field->extractValue(
				array('Field' => $fieldProperties['FieldName']),
				$currentValues,
				$errors
			);
		}

		$errors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if(count($errors) > 0)
		{
			return false;
		}

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($workflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $properties;

		return true;
	}

	public static function ValidateProperties($testProperties = [], CBPWorkflowTemplateUser $user = null)
	{
		$errors = [];

		foreach (self::GetPropertiesDialogMap() as $propertyKey => $fieldProperties)
		{
			if(
				CBPHelper::getBool($fieldProperties['Required'])
				&& CBPHelper::isEmptyValue($testProperties[$propertyKey])
			)
			{
				$errors[] = array(
					'code' => 'NotExist',
					'parameter' => 'FieldValue',
					'message' => GetMessage("CRM_SOF_EMPTY_PROP", array('#PROPERTY#' => $fieldProperties['Name']))
				);
			}
		}

		return array_merge($errors, parent::ValidateProperties($testProperties, $user));
	}
}