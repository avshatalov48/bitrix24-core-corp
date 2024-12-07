<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CBPCrmSetObserverField extends CBPActivity
{
	const ACTION_ADD_OBSERVERS = 'add';
	const ACTION_REMOVE_OBSERVERS = 'remove';
	const ACTION_REPLACE_OBSERVERS = 'replace';

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
			'ActionOnObservers' => null,
			'Observers' => null,
		];
	}

	public function execute()
	{
		if (!CModule::IncludeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$documentType = $this->GetDocumentType()[2];
		$documentId = mb_split('_(?=[^_]*$)', $this->GetDocumentId()[2])[1];
		$observerIds = CBPHelper::ExtractUsers($this->Observers, $this->GetDocumentId());

		if ($this->workflow->isDebug())
		{
			$this->writeDebugInfo($this->getDebugInfo([
				'ActionOnObservers' => $this->ActionOnObservers,
				'Observers' => $this->Observers,
			]));
		}

		if ($documentType === CCrmOwnerType::LeadName || $documentType === CCrmOwnerType::DealName)
		{
			$this->setLeadDealObservers($documentType, $documentId, $observerIds);
		}
		else
		{
			$this->setItemObservers(CCrmOwnerType::ResolveID($documentType), $documentId, $observerIds);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	protected function setLeadDealObservers(string $documentType, int $documentId, array $observerIds)
	{
		$classHandler = $this->defineClassHandler($documentType);
		$methodHandler = $this->defineMethodHandler();

		if(!is_null($classHandler) && !is_null($methodHandler))
		{
			call_user_func([$classHandler, $methodHandler], $documentId, $observerIds);
		}
	}

	protected function defineClassHandler(string $documentType) : ?string
	{
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

	protected function setItemObservers(int $typeId, int $entityId, array $observerIds)
	{
		$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($typeId);
		if (is_null($factory))
		{
			return;
		}

		$item = $factory->getItem($entityId);
		switch ($this->ActionOnObservers)
		{
			case self::ACTION_ADD_OBSERVERS:
				$observerIds = array_merge($item->getObservers(), $observerIds);
				break;

			case self::ACTION_REMOVE_OBSERVERS:
				$observerIds = array_diff($item->getObservers(), $observerIds);
				break;

			case self::ACTION_REPLACE_OBSERVERS:
				// if we're replacing observers then identifiers list is ready-to-use, keep as is
				break;

			default:
				$observerIds = [];
		}

		$item->setObservers($observerIds);
		$factory->getUpdateOperation($item)->disableAllChecks()->launch();
	}

	public static function getPropertiesDialog($documentType, $activityName, $workflowTemplate, $workflowParameters, $workflowVariables, $currentValues = null, $formName = '', $popupWindow = null, $siteId = '')
	{
		if(!CModule::IncludeModule('crm'))
		{
			return '';
		}

		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, [
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $workflowTemplate,
			'workflowParameters' => $workflowParameters,
			'workflowVariables' => $workflowVariables,
			'currentValues' => $currentValues,
			'formName' => $formName,
			'siteId' => $siteId
		]);

		$dialog->setMap(self::getPropertiesDialogMap());

		return $dialog;
	}

	protected static function getPropertiesDialogMap(): array
	{
		return [
			'ActionOnObservers' => [
				'Name' => GetMessage("CRM_SOF_ACTION_ON_OBSERVERS"),
				'FieldName' => 'action_on_observers',
				'Type' => \Bitrix\Bizproc\FieldType::SELECT,
				'Required' => true,
				'Options' => self::getActionsOnObservers(),
				'Default' => self::ACTION_ADD_OBSERVERS,
			],
			'Observers' => [
				'Name' => GetMessage("CRM_SOF_OBSERVERS"),
				'FieldName' => 'observers',
				'Type' => \Bitrix\Bizproc\FieldType::USER,
				'Required' => true,
				'Multiple' => true,
			],
		];
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		return static::getPropertiesDialogMap();
	}

	protected static function getActionsOnObservers(): array
	{
		return [
			self::ACTION_ADD_OBSERVERS => getMessage('CRM_SOF_ACTION_ADD_OBSERVERS'),
			self::ACTION_REMOVE_OBSERVERS => getMessage('CRM_SOF_ACTION_REMOVE_OBSERVERS'),
			self::ACTION_REPLACE_OBSERVERS => GetMessage('CRM_SOF_ACTION_REPLACE_OBSERVERS'),
		];
	}

	public static function getPropertiesDialogValues($documentType, $activityName, &$workflowTemplate, &$workflowParameters, &$workflowVariables, $currentValues, &$errors)
	{
		$runtime = CBPRuntime::GetRuntime();
		$runtime->StartRuntime();

		$properties = [];
		$documentService = $runtime->GetService('DocumentService');

		foreach (self::getPropertiesDialogMap() as $propertyKey => $fieldProperties)
		{
			$field = $documentService->getFieldTypeObject($documentType, $fieldProperties);
			if(!$field)
				continue;

			$properties[$propertyKey] = $field->extractValue(
				['Field' => $fieldProperties['FieldName']],
				$currentValues,
				$errors
			);
		}

		$errors = self::validateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if(count($errors) > 0)
		{
			return false;
		}

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($workflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $properties;

		return true;
	}

	public static function validateProperties($testProperties = [], CBPWorkflowTemplateUser $user = null)
	{
		$errors = [];

		foreach (self::getPropertiesDialogMap() as $propertyKey => $fieldProperties)
		{
			if(
				CBPHelper::getBool($fieldProperties['Required'])
				&& CBPHelper::isEmptyValue($testProperties[$propertyKey])
			)
			{
				$errors[] = [
					'code' => 'NotExist',
					'parameter' => 'FieldValue',
					'message' => GetMessage("CRM_SOF_EMPTY_PROP", ['#PROPERTY#' => $fieldProperties['Name']])
				];
			}
		}

		return array_merge($errors, parent::validateProperties($testProperties, $user));
	}
}
