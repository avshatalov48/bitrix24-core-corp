<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Bizproc\Activity\PropertiesDialog;

class CBPCrmCompleteTaskActivity extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
			'TargetStatus' => null
		];
	}

	public function Execute()
	{
		if (!CModule::IncludeModule('crm') || !CModule::IncludeModule('tasks'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		[$entityTypeName, $entityId] = mb_split('_(?=[^_]*$)', $this->GetDocumentId()[2]);
		$stages = $this->getStages($entityTypeName, $entityId);

		if ($this->workflow->isDebug())
		{
			$this->logTargetStatus();
		}

		if ($stages && array_diff((array)$this->TargetStatus, $stages))
		{
			$this->WriteToTrackingService(
				Bitrix\Main\Localization\Loc::getMessage('CRM_CTA_INCORRECT_STAGE'),
				0,
				CBPTrackingType::Error
			);

			return CBPActivityExecutionStatus::Closed;
		}

		foreach ((array)$this->TargetStatus as $ownerStatus)
		{
			$this->completeTasks($entityTypeName, $entityId, $ownerStatus);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	protected function getStages(string $entityTypeName, int $entityId)
	{
		switch ($entityTypeName)
		{
			case CCrmOwnerType::LeadName:
				return $this->getLeadStages();

			case CCrmOwnerType::DealName:
				return $this->getDealStages($entityId);

			default:
				$entityTypeId = CCrmOwnerType::ResolveID($entityTypeName);
				$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);

				return !is_null($factory) ? $this->getItemStages($factory->getItem($entityId)) : [];
		}
	}

	protected function getLeadStages(): array
	{
		return array_keys(CCrmStatus::GetStatusList('STATUS'));
	}

	protected function getDealStages(int $entityId): array
	{
		$dbRes = CCrmDeal::GetListEx(
			[],
			[
				'=ID' => $entityId,
				'CHECK_PERMISSIONS' => 'N'
			],
			false,
			false,
			['CATEGORY_ID']
		);

		$entity = $dbRes->Fetch();
		$categoryId = $categoryId = isset($entity['CATEGORY_ID']) ? (int)$entity['CATEGORY_ID'] : 0;

		return array_keys(\Bitrix\Crm\Category\DealCategory::getStageList($categoryId));
	}

	protected function getItemStages(Bitrix\Crm\Item $item): array
	{
		$target = new \Bitrix\Crm\Automation\Target\ItemTarget($item->getEntityTypeId());
		$target->setEntityById($item->getId());
		return $target->getEntityStatuses();
	}

	private function logTargetStatus(): void
	{
		$map = static::getPropertiesDialogMap(new PropertiesDialog('', [
			'documentType' => $this->getDocumentType()
		]));

		$this->writeDebugInfo($this->getDebugInfo(['TargetStatus' => $this->TargetStatus], $map));
	}

	protected function completeTasks(string $ownerType, int $ownerId, string $ownerStage): void
	{
		$dbResult = \CCrmActivity::GetList(
			[],
			[
				'TYPE_ID' => \CCrmActivityType::Task,
				'COMPLETED' => 'N',
				'CHECK_PERMISSIONS' => 'N',
				'OWNER_ID' => $ownerId,
				'OWNER_TYPE_ID' => CCrmOwnerType::ResolveID($ownerType)
			],
			false,
			false,
			['ID', 'ASSOCIATED_ENTITY_ID', 'SETTINGS']
		);

		$completedTasks = [];
		for ($activity = $dbResult->Fetch(); $activity; $activity = $dbResult->Fetch())
		{
			if (is_array($activity['SETTINGS']) && $activity['SETTINGS']['OWNER_STAGE'] === $ownerStage)
			{
				$isCompleted = CCrmActivity::Update($activity['ID'], ['COMPLETED' => true], false, true);
				if ($isCompleted)
				{
					$completedTasks[] = $activity['ASSOCIATED_ENTITY_ID'];
				}
			}
		}

		if ($this->workflow->isDebug())
		{
			$this->logCompletedTasks($completedTasks);
		}
	}

	private function logCompletedTasks(array $completedTaskIds): void
	{
		$map = [
			'CompletedTasks' => [
				'Name' => \Bitrix\Main\Localization\Loc::getMessage('CRM_CTA_COMPLETED_TASKS'),
				'Type' => \Bitrix\Bizproc\FieldType::INT,
				'Multiple' => true,
			]
		];
		$debugInfo = $this->getDebugInfo(['CompletedTasks' => $completedTaskIds], $map);

		$this->writeDebugInfo($debugInfo);
	}

	public static function GetPropertiesDialog($documentType, $activityName, $workflowTemplate, $workflowParameters, $workflowVariables, $currentValues = null, $formName = '', $popupWindow = null, $siteId = '')
	{
		if (!CModule::IncludeModule('crm') || !CModule::IncludeModule('tasks'))
		{
			return '';
		}

		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__,[
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $workflowTemplate,
			'workflowParameters' => $workflowParameters,
			'workflowVariables' => $workflowVariables,
			'currentValues' => $currentValues,
			'formName' => $formName,
			'siteId' => $siteId
		]);
		$dialog->setMapCallback([static::class, 'getPropertiesDialogMap']);

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$workflowTemplate, &$workflowParameters, &$workflowVariables, $currentValues, &$errors)
	{
		$runtime = CBPRuntime::GetRuntime();
		$runtime->StartRuntime();
		$documentService = $runtime->GetService('DocumentService');

		$properties = [];

		$map = static::getPropertiesDialogMap(new PropertiesDialog('', ['documentType' => $documentType]));
		foreach ($map as $fieldId => $fieldProperties)
		{
			$field = $documentService->getFieldTypeObject($documentType, $fieldProperties);
			if (!$field)
			{
				continue;
			}

			$properties[$fieldId] = $field->extractValue(
				['Field' => $fieldProperties['FieldName']],
				$currentValues,
				$errors
			);
		}

		$errors = static::ValidateProperties(
			$properties,
			new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser)
		);

		if ($errors)
		{
			return false;
		}

		$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($workflowTemplate, $activityName);
		$currentActivity['Properties'] = $properties;

		return true;
	}

	public static function ValidateProperties($testProperties = [], CBPWorkflowTemplateUser $user = null)
	{
		$errors = [];

		if (CBPHelper::isEmptyValue($testProperties['TargetStatus']))
		{
			$errors[] = [
				'code' => 'NotExist',
				'parameter' => 'FieldValue',
				'message' => GetMessage("CRM_GRI_EMPTY_PROP", ['#PROPERTY#' => GetMessage("CRM_CTA_COMPLETE_TASK")])
			];
		}

		return array_merge($errors, parent::ValidateProperties($testProperties, $user));
	}

	public static function getPropertiesDialogMap($dialog)
	{
		static $map = null;

		$map = $map ?: [
			'TargetStatus' => [
				'Name' => GetMessage("CRM_CTA_COMPLETE_TASK"),
				'FieldName' => 'target_status',
				'Type' => \Bitrix\Bizproc\FieldType::SELECT,
				'Required' => true,
				'Multiple' => true,
				'Options' => static::getDocumentStatuses($dialog->getDocumentType()[2])
			]
		];

		return $map;
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		$dialog = new PropertiesDialog('', [
			'documentType' => $documentType,
		]);

		return static::getPropertiesDialogMap($dialog);
	}

	public static function getDocumentStatuses(string $documentType)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return [];
		}

		switch ($documentType)
		{
			case CCrmOwnerType::DealName:
				return \Bitrix\Crm\Category\DealCategory::getFullStageList();

			case CCrmOwnerType::LeadName:
				return CCrmStatus::GetStatusList('STATUS');

			default:
				$documentTypeId = CCrmOwnerType::ResolveID($documentType);

				$target = new \Bitrix\Crm\Automation\Target\ItemTarget($documentTypeId);
				$statuses = [];

				if ($target->isAvailable())
				{
					foreach ($target->getStatusInfos() as $statusName => $statusInfo)
					{
						$statuses[$statusName] = $statusInfo['NAME'];
					}
				}
				return $statuses;
		}
	}
}
