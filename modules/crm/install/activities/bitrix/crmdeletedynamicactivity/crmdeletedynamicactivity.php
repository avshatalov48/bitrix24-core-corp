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
		];

		$this->SetPropertiesTypes([
			'EntityTypeId' => ['Type' => FieldType::INT],
		]);
	}

	protected function checkProperties(): \Bitrix\Main\ErrorCollection
	{
		$errors = parent::checkProperties();

		$factory = Crm\Service\Container::getInstance()->getFactory($this->EntityTypeId);
		if (!CCrmOwnerType::isPossibleDynamicTypeId($this->EntityTypeId) || is_null($factory))
		{
			$errors->setError(new \Bitrix\Main\Error(Loc::getMessage('CRM_DDA_TYPE_ID_ERROR')));
		}
		if (is_null($factory->getItem($this->EntityId)))
		{
			$errors->setError(new \Bitrix\Main\Error(Loc::getMessage('CRM_DDA_ENTITY_ERROR')));
		}

		return $errors;
	}

	protected function internalExecute(): \Bitrix\Main\ErrorCollection
	{
		$errorCollection = parent::internalExecute();

		$documentId = CCrmBizProcHelper::ResolveDocumentId($this->EntityTypeId, $this->EntityId);

		if (!Crm\Integration\BizProc\Document\Dynamic::DeleteDocument($documentId[2]))
		{
			$errorCollection->setError(new \Bitrix\Main\Error('CRM_DDA_DELETE_ERROR'));
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
		$typesMap = Crm\Service\Container::getInstance()->getDynamicTypesMap();
		$typesMap->load([
			'isLoadStages' => false,
			'isLoadCategories' => false,
		]);

		$typeNames = [];
		foreach ($typesMap->getTypes() as $typeId => $type)
		{
			$typeNames[$typeId] = $type->getTitle();
		}

		return [
			'EntityTypeId' => [
				'Name' => Loc::getMessage('CRM_DDA_DYNAMIC_TYPE'),
				'FieldName' => 'entity_type_id',
				'Type' => FieldType::SELECT,
				'Options' => $typeNames,
				'Required' => true,
			],
			'EntityId' => [
				'Name' => Loc::getMessage('CRM_DDA_DYNAMIC_ID'),
				'FieldName' => 'entity_id',
				'Type' => FieldType::INT,
				'Required' => true,
			],
		];
	}
}