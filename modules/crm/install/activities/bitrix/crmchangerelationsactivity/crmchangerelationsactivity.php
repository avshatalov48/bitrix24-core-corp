<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Bizproc\FieldType;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

$runtime = CBPRuntime::GetRuntime();
$runtime->IncludeActivityFile('CrmGetRelationsInfoActivity');

class CBPCrmChangeRelationsActivity extends CBPCrmGetRelationsInfoActivity
{
	// protected const ADD_RELATION_ACTION = 'add';
	protected const REMOVE_RELATION_ACTION = 'remove';
	protected const REPLACE_RELATION_ACTION = 'replace';

	public function __construct($name)
	{
		parent::__construct($name);

		$this->arProperties = [
			'Title' => '',
			'Action' => '',
			'ParentTypeId' => 0,
			'ParentId' => 0,
		];

		$this->SetPropertiesTypes([
			'ParentId' => ['Type' => FieldType::INT]
		]);
	}

	protected function checkProperties(): \Bitrix\Main\ErrorCollection
	{
		$errors = parent::checkProperties();
		if (!$errors->isEmpty())
		{
			return $errors;
		}

		if ($this->Action === self::REPLACE_RELATION_ACTION && $this->ParentId <= 0)
		{
			$errors->setError(new Error(Loc::getMessage('CRM_CRA_ELEMENT_NOT_CHOSEN_ERROR')));
			return $errors;
		}

		$parentDocumentId = CCrmBizProcHelper::ResolveDocumentId($this->ParentTypeId, $this->ParentId);
		$parentDocument = static::getDocumentService()->GetDocument($parentDocumentId);
		if ($this->Action === self::REPLACE_RELATION_ACTION && is_null($parentDocument))
		{
			$errors->setError(new Error(Loc::getMessage('CRM_CRA_ELEMENT_EXISTENCE_ERROR')));
		}

		return $errors;
	}

	protected function internalExecute(): \Bitrix\Main\ErrorCollection
	{
		$errors = new \Bitrix\Main\ErrorCollection();

		[$entityTypeId, $entityId] = CCrmBizProcHelper::resolveEntityId($this->GetDocumentId());
		$childElement = new ItemIdentifier($entityTypeId, $entityId);

		if ($this->Action === self::REMOVE_RELATION_ACTION)
		{
			$errors->add($this->removeParentElement($childElement));
		}
		elseif ($this->Action === self::REPLACE_RELATION_ACTION)
		{
			$errors->add($this->replaceParentElement($childElement));
		}

		return $errors;
	}

	protected function removeParentElement(ItemIdentifier $childElement): array
	{
		$errors = [];
		$parentElements = $this->getParentElements($childElement);

		if (is_array($parentElements))
		{
			$registrar = Container::getInstance()->getRelationRegistrar();

			foreach ($parentElements as $element)
			{
				$unbindResult = Container::getInstance()->getRelationManager()->unbindItems($element, $childElement);
				if ($unbindResult->isSuccess())
				{
					$registrar->registerUnbind($element, $childElement);
				}
				$errors = array_merge($errors, $unbindResult->getErrors());
			}
		}
		else
		{
			$errors[] = new Error(Loc::getMessage('CRM_GRI_RELATION_EXISTENCE_ERROR'));
		}

		return $errors;
	}

	protected function replaceParentElement(ItemIdentifier $childElement): array
	{
		$errors = $this->removeParentElement($childElement);
		if ($errors)
		{
			return $errors;
		}

		$parentElement = new ItemIdentifier($this->ParentTypeId, $this->ParentId);
		$bindingResult = Container::getInstance()->getRelationManager()->bindItems($parentElement, $childElement);
		if ($bindingResult->isSuccess())
		{
			Container::getInstance()->getRelationRegistrar()->registerBind($parentElement, $childElement);
		}

		return $bindingResult->getErrors();
	}

	protected static function extractPropertiesValues(PropertiesDialog $dialog, array $fieldsMap): Result
	{
		return \Bitrix\Bizproc\Activity\BaseActivity::extractPropertiesValues($dialog, $fieldsMap);
	}

	protected static function getFileName(): string
	{
		return __FILE__;
	}

	public static function getPropertiesDialogMap(?\Bitrix\Bizproc\Activity\PropertiesDialog $dialog = null): array
	{
		$parentMap = parent::getPropertiesDialogMap($dialog);

		return [
			'Action' => [
				'Name' => Loc::getMessage('CRM_CRA_ACTION_TYPE'),
				'FieldName' => 'action',
				'Type' => FieldType::SELECT,
				'Options' => [
					// self::ADD_RELATION_ACTION => Loc::getMessage('CRM_CRA_ACTION_ADD'),
					self::REMOVE_RELATION_ACTION => Loc::getMessage('CRM_CRA_ACTION_REMOVE'),
					self::REPLACE_RELATION_ACTION => Loc::getMessage('CRM_CRA_ACTION_REPLACE'),
				],
				'Required' => true,
				'Default' => self::REPLACE_RELATION_ACTION,
			],
			'ParentTypeId' => $parentMap['ParentTypeId'],
			'ParentId' => [
				'Name' => Loc::getMessage('CRM_CRA_PARENT_ID'),
				'FieldName' => 'parent_id',
				'Type' => FieldType::INT,
			]
		];
	}
}
