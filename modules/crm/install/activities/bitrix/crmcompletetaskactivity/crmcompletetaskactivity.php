<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Activity\Provider\Tasks\Task;
use Bitrix\Main\Localization\Loc;
use \Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Order\OrderStatus;

class CBPCrmCompleteTaskActivity extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
			'TargetCategory' => null,
			'TargetStatus' => null,
		];
	}

	public function Execute()
	{
		if (!CModule::IncludeModule('crm') || !CModule::IncludeModule('tasks'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		[$entityTypeName, $entityId] = mb_split('_(?=[^_]*$)', $this->GetDocumentId()[2]);

		if ($this->workflow->isDebug())
		{
			$this->logTargetStatus();
		}

		if (!is_array($this->TargetStatus))
		{
			$this->TargetStatus = [$this->TargetStatus];
		}

		$this->TargetStatus = array_intersect($this->getStages(), $this->TargetStatus);
		if (!$this->TargetStatus)
		{
			$this->WriteToTrackingService(
				Loc::getMessage('CRM_CTA_INCORRECT_STAGE_MSGVER_1'),
				0,
				CBPTrackingType::Error
			);

			return CBPActivityExecutionStatus::Closed;
		}

		foreach ($this->TargetStatus as $ownerStatus)
		{
			$this->completeTasks($entityTypeName, $entityId, $ownerStatus);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	protected function getStages(): array
	{
		$factory = static::getFactory($this->getDocumentType());
		if (!isset($factory) || !$factory->isStagesSupported())
		{
			return [];
		}

		$categoryId = $this->TargetCategory;
		if ($factory->isCategoriesSupported() && isset($categoryId))
		{
			$categoryId = (int)$categoryId;
			$category = $factory->getCategory($categoryId);
			if (!isset($category))
			{
				$this->writeToTrackingService(
					Loc::getMessage('CRM_CTA_INCORRECT_CATEGORY'),
					0,
					CBPTrackingType::Error
				);
			}

			$stageIds = [];
			foreach ($factory->getStages($categoryId) as $status)
			{
				$stageIds[] = $status->getStatusId();
			}

			return $stageIds;
		}
		else
		{
			[$entityTypeId, $entityId] = CCrmBizProcHelper::resolveEntityId($this->getDocumentId());

			return $this->getEntityStages(CCrmOwnerType::ResolveName($entityTypeId), $entityId);
		}
	}

	private function getEntityStages(string $entityTypeName, int $entityId)
	{
		switch ($entityTypeName)
		{
			case CCrmOwnerType::LeadName:
				return $this->getLeadStages();

			case CCrmOwnerType::DealName:
				return $this->getDealStages($entityId);

			case CCrmOwnerType::OrderName:
				return array_keys(OrderStatus::getAllStatusesNames());

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
		$entityId = $item->getId();
		$target->setEntityId($entityId);
		$target->setDocumentId(\CCrmOwnerType::ResolveName($target->getEntityTypeId()) . '_' . $entityId);

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

		$dbResult = \CCrmActivity::GetList(
			[],
			[
				'TYPE_ID' => \CCrmActivityType::Provider,
				'PROVIDER_ID' => Task::getId(),
				'PROVIDER_TYPE_ID' => Task::getProviderTypeId(),
				'COMPLETED' => 'N',
				'CHECK_PERMISSIONS' => 'N',
				'OWNER_ID' => $ownerId,
				'OWNER_TYPE_ID' => CCrmOwnerType::ResolveID($ownerType)
			],
			false,
			false,
			['ID', 'ASSOCIATED_ENTITY_ID', 'SETTINGS']
		);

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
				'Name' => Loc::getMessage('CRM_CTA_COMPLETED_TASKS'),
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

		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, [
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $workflowTemplate,
			'workflowParameters' => $workflowParameters,
			'workflowVariables' => $workflowVariables,
			'currentValues' => $currentValues,
			'formName' => $formName,
			'siteId' => $siteId,
		]);

		$factory = static::getFactory($documentType);
		if (isset($factory))
		{
			$stages = [];
			foreach (static::getFullItemStatusesList($factory) as $categoryId => $statuses)
			{
				$stages[$categoryId] = [];
				foreach ($statuses as $statusId => $statusName)
				{
					$stages[$categoryId][] = [
						'id' => $statusId,
						'name' => $statusName,
					];
				}
			}

			$dialog->setRuntimeData(['stages' => $stages]);
			$dialog->setMapCallback([static::class, 'getPropertiesDialogMap']);
		}

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$workflowTemplate, &$workflowParameters, &$workflowVariables, $currentValues, &$errors)
	{
		$runtime = CBPRuntime::GetRuntime();
		$runtime->StartRuntime();
		$documentService = $runtime->GetService('DocumentService');

		$properties = [];

		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, [
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $workflowTemplate,
			'workflowParameters' => $workflowParameters,
			'workflowVariables' => $workflowVariables,
			'currentValues' => $currentValues,
		]);

		$map = static::getPropertiesDialogMap($dialog);
		$map['TargetStatus']['Options'] = static::getDocumentStatuses($documentType[2]);

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

		$errors = static::validateDocumentProperties(
			$dialog,
			$properties,
			new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser),
		);

		if ($errors)
		{
			return false;
		}

		$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($workflowTemplate, $activityName);
		$currentActivity['Properties'] = $properties;

		return true;
	}

	public static function validateDocumentProperties(
		PropertiesDialog $dialog,
		array $testProperties = [],
		?CBPWorkflowTemplateUser $user = null
	): array
	{
		$errors = [];

		$map = static::getPropertiesDialogMap($dialog);
		$categoryField = $map['TargetCategory'];
		if (
			is_array($categoryField['Options'] ?? null)
			&& $categoryField['Options']
			&& CBPHelper::isEmptyValue($testProperties['TargetCategory'])
		)
		{
			$errors[] = [
				'code' => 'NotExist',
				'parameter' => 'FieldValue',
				'message' => Loc::getMessage('CRM_CTA_ERROR_EMPTY_REQUIRED_FIELD', [
					'#PROPERTY#' => $categoryField['Name'],
				]),
			];
		}

		return array_merge($errors, static::ValidateProperties($testProperties, $user));
	}

	public static function ValidateProperties($testProperties = [], CBPWorkflowTemplateUser $user = null)
	{
		$errors = [];

		if (CBPHelper::isEmptyValue($testProperties['TargetStatus']))
		{
			$errors[] = [
				'code' => 'NotExist',
				'parameter' => 'FieldValue',
				'message' => Loc::getMessage('CRM_CTA_ERROR_EMPTY_REQUIRED_FIELD', ['#PROPERTY#' => Loc::getMessage('CRM_CTA_COMPLETE_TASK')]),
			];
		}

		return array_merge($errors, parent::ValidateProperties($testProperties, $user));
	}

	public static function getPropertiesDialogMap($dialog): array
	{
		$context = $dialog->getContext();
		$categoryId = isset($context['DOCUMENT_CATEGORY_ID']) ? (int)$context['DOCUMENT_CATEGORY_ID'] : null;

		$targetCategoryOptions = static::getPropertyCategoryOptions($dialog);
		if (!isset($categoryId))
		{
			$categoryId = array_key_first($targetCategoryOptions);
		}

		return [
			'TargetCategory' => [
				'Name' => Loc::getMessage('CRM_CTA_COMPLETE_TASK_CATEGORY'),
				'FieldName' => 'target_category',
				'Type' => \Bitrix\Bizproc\FieldType::SELECT,
				'Required' => (bool)$targetCategoryOptions,
				'Options' => $targetCategoryOptions,
				'Default' => $categoryId,
			],
			'TargetStatus' => [
				'Name' => GetMessage("CRM_CTA_COMPLETE_TASK"),
				'FieldName' => 'target_status',
				'Type' => \Bitrix\Bizproc\FieldType::SELECT,
				'Required' => true,
				'Multiple' => true,
				'Options' => static::getDocumentStatuses($dialog->getDocumentType()[2], $categoryId)
			]
		];
	}

	private static function getPropertyCategoryOptions(PropertiesDialog $dialog): array
	{
		$factory = static::getFactory($dialog->getDocumentType());

		if (!isset($factory) || !$factory->isCategoriesEnabled())
		{
			return [];
		}

		$categories = [];
		foreach ($factory->getCategories() as $category)
		{
			$categories[$category->getId()] = $category->getName();
		}

		return $categories;
	}

	private static function getFactory(array $documentType): ?Factory
	{
		$entityTypeId = CCrmOwnerType::ResolveID($documentType[2] ?? '');

		return Container::getInstance()->getFactory($entityTypeId);
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		$dialog = new PropertiesDialog('', [
			'documentType' => $documentType,
		]);

		return static::getPropertiesDialogMap($dialog);
	}

	public static function getDocumentStatuses(string $documentType, ?int $categoryId = null)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return [];
		}

		$documentTypeId = CCrmOwnerType::ResolveID($documentType);
		$factory = Container::getInstance()->getFactory($documentTypeId);

		if (!isset($factory))
		{
			return [];
		}
		if (!$factory->isCategoriesSupported())
		{
			$categoryId = null;
		}

		$statuses = [];
		foreach (static::getFullItemStatusesList($factory, $categoryId) as $categoryStatuses)
		{
			foreach ($categoryStatuses as $statusId => $statusName)
			{
				$statuses[$statusId] = $statusName;
			}
		}

		return $statuses;
	}

	protected static function getFullItemStatusesList(Factory $factory, ?int $categoryId = null): array
	{
		if (!$factory->isStagesEnabled())
		{
			return [];
		}

		if (!$factory->isCategoriesSupported())
		{
			$categories = [null];
		}
		elseif (isset($categoryId))
		{
			$currentCategory = $factory->getCategory($categoryId);
			$categories = isset($currentCategory) ? [$currentCategory] : [];
		}
		else
		{
			$categories = $factory->getCategories();
		}

		$statuses = [];

		foreach ($categories as $category)
		{
			$categoryId = isset($category) ? $category->getId() : null;
			$statuses[$categoryId] = [];

			if ($factory->getEntityTypeId() === CCrmOwnerType::Order)
			{
				$statuses[$categoryId] = OrderStatus::getAllStatusesNames();

				continue;
			}

			foreach ($factory->getStages($categoryId) as $status)
			{
				$statuses[$categoryId][$status->getStatusId()] = $status->getName();
			}
		}

		return $statuses;
	}
}