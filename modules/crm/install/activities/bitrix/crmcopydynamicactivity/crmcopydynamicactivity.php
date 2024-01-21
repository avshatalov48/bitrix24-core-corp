<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Crm;
use Bitrix\Crm\Service;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class CBPCrmCopyDynamicActivity extends CBPActivity
{
	protected static array $cycleCounter = [];
	const CYCLE_LIMIT = 150;

	protected array $preparedProperties = [];

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
			'ItemTitle' => '',
			'CategoryId' => 0,
			'StageId' => null,
			'Responsible' => null,

			//return
			'ItemId' => 0,
        ];
	}

	protected function ReInitialize()
	{
		parent::ReInitialize();
		$this->ItemId = 0;
	}

	public function Execute()
	{
		if (!Loader::includeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$this->checkCycling();

		$documentId = $this->GetDocumentId();
		[$sourceItemType, $sourceItemId] = mb_split('_(?=[^_]*$)', $documentId[2]);

		$factory = static::getFactoryByType($sourceItemType);
		$item = $factory?->getItem($sourceItemId);

		if (is_null($item))
		{
			$this->WriteToTrackingService(Loc::getMessage('CRM_CDA_NO_SOURCE_FIELDS'), 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		$this->prepareProperties($factory, $item);
		if ($this->checkPreparedProperties($factory))
		{
			$this->applyPreparedProperties($item);
			$this->internalExecute($factory, $item);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	protected function checkCycling()
	{
		$key = $this->GetName();

		if (!isset(static::$cycleCounter[$key]))
		{
			static::$cycleCounter[$key] = 0;
		}

		static::$cycleCounter[$key]++;
		if (static::$cycleCounter[$key] > static::CYCLE_LIMIT)
		{
			$this->WriteToTrackingService(
				Loc::getMessage('CRM_CDA_CYCLING_ERROR'),
				0,
				CBPTrackingType::Error
			);
			throw new Exception();
		}
	}

	protected function prepareProperties(Service\Factory $factory, Crm\Item $item)
	{
		$itemTitle = $this->ItemTitle;
		if ($itemTitle === '')
		{
			$itemTitle = $item->getTitle();
		}

		$categoryId = $this->CategoryId;
		if ($categoryId === 0)
		{
			$categoryId = $factory->createDefaultCategoryIfNotExist()->getId();
		}

		$stageId = $this->StageId;
		if ($stageId === '')
		{
			$stageId = $item->getStatusId();
		}

		$responsibles = CBPHelper::ExtractUsers($this->Responsible, $this->GetDocumentId());
		if ($responsibles)
		{
			shuffle($responsibles);
		}
		else
		{
			$responsibles = [(int)$item->getAssignedById()];
		}

		$this->preparedProperties = [
			'ItemTitle' => $itemTitle,
			'CategoryId' => $categoryId,
			'StageId' => $stageId,
			'Responsible' => (int)$responsibles[0],
		];
	}

	protected function checkPreparedProperties(Service\Factory $factory): bool
	{
		$stage = $factory->getStage($this->preparedProperties['StageId']);
		if (is_null($stage))
		{
			$this->WriteToTrackingService(
				Loc::getMessage('CRM_CDA_STAGE_EXISTENCE_ERROR'),
				0,
				CBPTrackingType::Error
			);

			return false;
		}

		$newStageEntityId = $stage->getEntityId();
		if (
			is_null($newStageEntityId)
			|| $newStageEntityId !== $factory->getStagesEntityId($this->preparedProperties['CategoryId'])
		)
		{
			$this->WriteToTrackingService(
				Loc::getMessage('CRM_CDA_STAGE_SELECTION_ERROR'),
				0,
				CBPTrackingType::Error
			);

			return false;
		}

		return true;
	}

	protected function applyPreparedProperties(Crm\Item $item)
	{
		$item->setTitle($this->preparedProperties['ItemTitle']);
		$item->setCategoryId($this->preparedProperties['CategoryId']);
		$item->setStageId($this->preparedProperties['StageId']);
		$item->setAssignedById($this->preparedProperties['Responsible']);
	}

	protected function internalExecute(Service\Factory $factory, Crm\Item $item)
	{
		$newItem = $this->copyItem($factory, $item);

		if (isset($newItem))
		{
			$this->ItemId = $newItem->getId();

			if (COption::GetOptionString('crm', 'start_bp_within_bp', 'N') === 'Y')
			{
				$CCrmBizProc = new CCrmBizProc($this->GetDocumentType()[2]);
				if ($CCrmBizProc->CheckFields(false, true))
				{
					$CCrmBizProc->StartWorkflow($this->ItemId);
				}
			}
		}
	}

	public function copyItem(Service\Factory $factory, Crm\Item $item): ?Crm\Item
	{
		$operation = $factory->getCopyOperation($item);
		$operation
			->disableCheckFields()
			->disableBizProc()
			->disableCheckAccess()
		;
		$copyResult = $operation->launch();

		$errorMessages = $copyResult->getErrorMessages();

		if ($copyResult->isSuccess())
		{
			return $copyResult->getCopy();
		}
		else
		{
			$this->WriteToTrackingService(end($errorMessages), 0, CBPTrackingType::Error);

			return null;
		}
	}

	public static function GetPropertiesDialog(
		$documentType,
		$activityName,
		$workflowTemplate,
		$workflowParameters,
		$workflowVariables,
		$currentValues = null,
		$formName = '',
		$popupWindow = null,
		$siteId = ''
	)
	{
		if (!Loader::includeModule('crm'))
		{
			return '';
		}

		$dialog = new PropertiesDialog(__FILE__, [
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

	public static function GetPropertiesDialogValues(
		$documentType,
		$activityName,
		&$workflowTemplate,
		&$workflowParameters,
		&$workflowVariables,
		$currentValues,
		&$errors
	)
	{
		$runtime = \CBPRuntime::GetRuntime();
		$runtime->StartRuntime();
		$documentService = $runtime->getDocumentService();

		$dialog = new PropertiesDialog(__FILE__, [
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $workflowTemplate,
			'workflowParameters' => $workflowParameters,
			'workflowVariables' => $workflowVariables,
			'currentValues' => $currentValues,
        ]);

		$properties = [];

		foreach (static::getPropertiesDialogMap($dialog) as $propertyKey => $fieldProperties)
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

		$errors = array_merge(
			$errors,
			static::ValidateProperties(
				$properties,
				new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser)
			)
		);

		$stageId = $properties['StageId'];
        if (!static::isExpression($stageId))
        {
            $categoryPrefixLength = mb_strpos($stageId, ':') + 1;
            $properties['StageId'] = mb_substr($stageId, $categoryPrefixLength);
        }

		if ($errors)
		{
			return false;
		}

		$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($workflowTemplate, $activityName);
		$currentActivity['Properties'] = $properties;

		return true;
	}

	public static function ValidateProperties($testProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$errors = [];

		if (
			array_key_exists('CategoryId', $testProperties)
			&& CBPHelper::isEmptyValue($testProperties['CategoryId'])
		)
		{
			$errors[] = [
				'code' => 'NotExist',
				'parameter' => 'FieldValue',
				'message' => Loc::getMessage(
					'CRM_CDA_EMPTY_PROP',
					['#PROPERTY#' => Loc::getMessage('CRM_CDA_MOVE_TO_CATEGORY')]
				)
			];
		}
		if (CBPHelper::isEmptyValue($testProperties['StageId']))
		{
			$errors[] = [
				'code' => 'NotExist',
				'parameter' => 'FieldValue',
				'message' => Loc::getMessage(
					'CRM_CDA_EMPTY_PROP',
					['#PROPERTY#' => Loc::getMessage('CRM_CDA_CHANGE_STAGE')]
				)
			];
		}

		return array_merge($errors, parent::ValidateProperties($testProperties, $user));
	}

	public static function getPropertiesDialogMap(PropertiesDialog $dialog): array
	{
		if (!Loader::includeModule('crm'))
		{
			return [];
		}

		$defaultTitle = Loc::getMessage('CRM_CDA_NEW_ITEM_TITLE', ['#SOURCE_TITLE#' => '{=Document:TITLE}']);
		if ($dialog->getFormName() === 'bizproc_automation_robot_dialog')
		{
			$defaultTitle = Crm\Automation\Helper::convertExpressions($defaultTitle, $dialog->getDocumentType());
		}

		$factory = static::getFactoryByType($dialog->getDocumentType()[2]);

		$map = [
			'ItemTitle' => [
				'Name' => Loc::getMessage('CRM_CDA_ITEM_TITLE'),
				'FieldName' => 'item_title',
				'Type' => 'string',
				'Default' => $defaultTitle
			]
		];

		if ($factory->isCategoriesEnabled())
		{
			$options = [];
			foreach ($factory->getCategories() as $category)
			{
				$options[$category->getId()] = $category->getName();
			}

			$map['CategoryId'] = [
				'Name' => Loc::getMessage('CRM_CDA_MOVE_TO_CATEGORY'),
				'FieldName' => 'category_id',
				'Type' => 'select',
				'Options' => $options,
				'Required' => true,
				'Default' => key($options),
			];
		}
		if ($factory->isStagesEnabled())
		{
			$options = static::getStages($factory);
			$map['StageId'] = [
				'Name' => Loc::getMessage('CRM_CDA_CHANGE_STAGE'),
				'FieldName' => 'stage_id',
				'Type' => 'select',
				'Options' => $options,
				'Default' => key($options),
				'Getter' => function($dialog, $property, $currentActivity, $compatible) {
					if (CBPActivity::isExpression($currentActivity['Properties']['StageId']))
					{
						return $currentActivity['Properties']['StageId'];
					}

					if (!array_key_exists('CategoryId', $currentActivity['Properties']))
					{
						$defaultStageId = $property['Default'];
						$categoryPrefixLength = mb_strpos($defaultStageId, ':') + 1;
						$categoryPrefix = mb_substr($defaultStageId, 0, $categoryPrefixLength);
					}
					else
					{
						$categoryPrefix = "C{$currentActivity['Properties']['CategoryId']}:";
					}

					return $categoryPrefix . $currentActivity['Properties']['StageId'];
				}
			];
		}

		$map['Responsible'] = [
			'Name' => Loc::getMessage('CRM_CDA_CHANGE_RESPONSIBLE'),
			'FieldName' => 'responsible',
			'Type' => 'user',
		];

		return $map;
	}

	protected static function getFactoryByType(string $type): ?Service\Factory
	{
		$typeId = CCrmOwnerType::ResolveID($type);
		return Service\Container::getInstance()->getFactory($typeId);
	}

	protected static function getStages(Service\Factory $factory): array
	{
		$stages = [];
		foreach ($factory->getCategories() as $category)
		{
			foreach ($factory->getStages($category->getId()) as $stage)
			{
				$categoryPrefix = "C{$category->getId()}:";
				$stages[$categoryPrefix . $stage['STATUS_ID']] = $stage['NAME'];
			}
		}
		return $stages;
	}
}
