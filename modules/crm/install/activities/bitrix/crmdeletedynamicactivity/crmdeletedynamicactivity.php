<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Bizproc\FieldType;
use Bitrix\Crm;
use Bitrix\Main\Localization\Loc;

class CBPCrmDeleteDynamicActivity extends \Bitrix\Bizproc\Activity\BaseActivity
{
	protected static $requiredModules = ['crm'];

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
			'EntityTypeId' => 0,
			'EntityId' => 0,
			'OnlyDynamicEntities' => 'N',
		];

		$this->SetPropertiesTypes([
			'EntityTypeId' => ['Type' => FieldType::INT],
		]);
	}

	protected function prepareProperties(): void
	{
		parent::prepareProperties();

		if ($this->workflow->isDebug())
		{
			$this->writeDebugInfo($this->getDebugInfo());
		}
	}

	protected function checkProperties(): \Bitrix\Main\ErrorCollection
	{
		$errors = parent::checkProperties();

		$factory = $this->EntityTypeId ? Crm\Service\Container::getInstance()->getFactory($this->EntityTypeId) : null;
		if (is_null($factory) || !CCrmBizProcHelper::ResolveDocumentName($this->EntityTypeId))
		{
			$errors->setError(new \Bitrix\Main\Error(Loc::getMessage('CRM_DDA_TYPE_ID_ERROR')));
		}
		elseif (is_null($this->EntityId) || is_null($factory->getItem($this->EntityId)))
		{
			$errors->setError(new \Bitrix\Main\Error(Loc::getMessage('CRM_DDA_ENTITY_ERROR')));
		}

		return $errors;
	}

	protected function internalExecute(): \Bitrix\Main\ErrorCollection
	{
		$errorCollection = parent::internalExecute();

		$documentId = CCrmBizProcHelper::ResolveDocumentId($this->EntityTypeId, $this->EntityId);

		$deletionResult = static::getDocumentService()->DeleteDocument($documentId);
		if (is_bool($deletionResult) && !$deletionResult)
		{
			$errorCollection->setError(new \Bitrix\Main\Error(Loc::getMessage('CRM_DDA_DELETE_ERROR')));
		}
		elseif ($deletionResult instanceof \Bitrix\Main\Result)
		{
			$errorCollection->add($deletionResult->getErrors());
		}

		[$currentEntityTypeId, $currentEntityId] = CCrmBizProcHelper::resolveEntityId($this->GetDocumentId());
		if ($currentEntityTypeId === $this->EntityTypeId && $currentEntityId === $this->EntityId)
		{
			$this->workflow->Terminate();
			throw new Exception('TerminateActivity');
		}

		return $errorCollection;
	}

	protected static function getFileName(): string
	{
		return __FILE__;
	}

	public static function getPropertiesDialogMap(?\Bitrix\Bizproc\Activity\PropertiesDialog $dialog = null): array
	{
		$typesMap = Crm\Service\Container::getInstance()->getTypesMap();

		$typeNames = [];
		foreach ($typesMap->getFactories() as $factory)
		{
			$entityTypeId = $factory->getEntityTypeId();
			$documentType = CCrmBizProcHelper::ResolveDocumentType($entityTypeId);

			if (isset($documentType))
			{
				$typeNames[$entityTypeId] = static::getDocumentService()->getDocumentTypeName($documentType);
			}
		}

		$showOnlyDynamicEntities = static::showOnlyDynamicEntities($dialog);

		return [
			'EntityTypeId' => [
				'Name' => Loc::getMessage('CRM_DDA_ELEMENT_TYPE'),
				'FieldName' => 'entity_type_id',
				'Type' => FieldType::SELECT,
				'Options' => $showOnlyDynamicEntities ? static::getOnlyDynamicEntities($typeNames) : $typeNames,
				'Required' => true,
				'AllowSelection' => false,
			],
			'EntityId' => [
				'Name' => Loc::getMessage('CRM_DDA_ELEMENT_ID'),
				'FieldName' => 'entity_id',
				'Type' => FieldType::INT,
				'Required' => true,
			],
			'OnlyDynamicEntities' => [
				'FieldName' => 'only_dynamic_entities',
				'Type' => 'bool',
				'Default' => $showOnlyDynamicEntities ? 'Y' : 'N',
				'Settings' => [
					'Hidden' => true,
				],
			],
		];
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		$map = static::getPropertiesDialogMap();
		unset($map['OnlyDynamicEntities']);

		return $map;
	}

	private static function showOnlyDynamicEntities(?\Bitrix\Bizproc\Activity\PropertiesDialog $dialog = null): bool
	{
		if (!$dialog)
		{
			return false;
		}

		$context = $dialog->getContext() ?? [];
		if (isset($context['addMenuGroup']) && $context['addMenuGroup'] === 'digitalWorkplace')
		{
			return true;
		}

		$workflowTemplate = $dialog->getWorkflowTemplate();
		$currentActivity = \CBPWorkflowTemplateLoader::FindActivityByName(
			$workflowTemplate,
			$dialog->getActivityName()
		);

		return (
			is_array($currentActivity)
			&& is_array($currentActivity['Properties'])
			&& $currentActivity['Properties']['OnlyDynamicEntities'] === 'Y'
		);
	}

	private static function getOnlyDynamicEntities(array $dynamicTypeIdOptions): array
	{
		return array_filter(
			$dynamicTypeIdOptions,
			static function($key) {
				return (CCrmOwnerType::isPossibleDynamicTypeId($key));
			},
			ARRAY_FILTER_USE_KEY
		);
	}
}