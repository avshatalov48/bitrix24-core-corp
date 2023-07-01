<?php

namespace Bitrix\Crm\Entity\Compatibility\Adapter;

use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\Entity\Compatibility\Adapter;
use Bitrix\Crm\Service;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class Operation extends Adapter
{
	/** @var Service\Factory */
	private $factory;

	private $checkPermissions = true;
	private $runAutomation = true;
	private $runBizProc = true;

	/** @var string - reference to a string that holds error messages */
	private $errorMessageContainer = '';
	/** @var array - reference to an array that holds check exceptions */
	private $checkExceptionsContainer = [];
	/** @var ErrorCollection|null - reference to a variable that holds errors */
	private $errorCollectionContainer;

	/** @var string[] */
	private $alwaysExposedFields = [];
	/** @var string[] */
	private $exposedOnlyAfterAddFields = [];
	/** @var string[] */
	private $exposedOnlyAfterUpdateFields = [];

	public function __construct(Service\Factory $factory)
	{
		$this->factory = $factory;
		Container::getInstance()->getLocalization()->loadMessages();
	}

	public function setCheckPermissions(bool $checkPermissions): self
	{
		$this->checkPermissions = $checkPermissions;

		return $this;
	}

	public function setRunAutomation(bool $runAutomation): self
	{
		$this->runAutomation = $runAutomation;

		return $this;
	}

	public function setRunBizProc(bool $runBizProc): self
	{
		$this->runBizProc = $runBizProc;

		return $this;
	}

	public function setErrorMessageContainer(string &$errorMessageContainer): self
	{
		$this->errorMessageContainer = &$errorMessageContainer;

		return $this;
	}

	public function setCheckExceptionsContainer(array &$checkExceptionsContainer): self
	{
		$this->checkExceptionsContainer = &$checkExceptionsContainer;

		return $this;
	}

	public function setErrorCollectionContainer(&$errorCollectionContainer): self
	{
		$this->errorCollectionContainer = &$errorCollectionContainer;

		return $this;
	}

	/**
	 * @param string[] $fieldNames
	 * @return $this
	 */
	public function setAlwaysExposedFields(array $fieldNames): self
	{
		$this->alwaysExposedFields = $fieldNames;

		return $this;
	}

	/**
	 * @param string[] $fieldNames
	 * @return $this
	 */
	public function setExposedOnlyAfterAddFields(array $fieldNames): self
	{
		$this->exposedOnlyAfterAddFields = $fieldNames;

		return $this;
	}

	/**
	 * @param string[] $fieldNames
	 * @return $this
	 */
	public function setExposedOnlyAfterUpdateFields(array $fieldNames): self
	{
		$this->exposedOnlyAfterUpdateFields = $fieldNames;

		return $this;
	}

	protected function doPerformAdd(array &$fields, array $compatibleOptions): Result
	{
		$this->beforeStart();

		$item = $this->factory->createItem();

		$this->prepareFields($fields, true);

		$item->setFromCompatibleData($fields);

		if (isset($compatibleOptions['IS_RESTORATION']) && $compatibleOptions['IS_RESTORATION'])
		{
			$operation = $this->factory->getRestoreOperation($item);
		}
		elseif (isset($fields['PERMISSION']) && $fields['PERMISSION'] === 'IMPORT')
		{
			$operation = $this->factory->getImportOperation($item);
		}
		else
		{
			$operation = $this->factory->getAddOperation($item);
		}

		$this->prepareOperation($operation, $compatibleOptions);

		$result = $operation->launch();
		if (!$result->isSuccess())
		{
			return $this->returnError($result, $fields);
		}

		$fields = $this->exposeFieldsAfterAdd($item->getCompatibleData(), $fields);

		return $this->returnSuccess($item->getId());
	}

	private function beforeStart(): void
	{
		$this->errorMessageContainer = '';
		$this->checkExceptionsContainer = [];
		$this->errorCollectionContainer = null;
	}

	/**
	 * @param Array<string, mixed> $fields
	 * @param bool $isNew
	 */
	private function prepareFields(array &$fields, bool $isNew): void
	{
		global $USER_FIELD_MANAGER;

		$crmUserType = new \CCrmUserType($USER_FIELD_MANAGER, $this->factory->getUserFieldEntityId());

		// use the same workarounds as old api
		$crmUserType->PrepareUpdate($fields, ['IS_NEW' => $isNew]);

		foreach ($this->factory->getFieldsCollection() as $field)
		{
			if (!$field->isUserField() || !isset($fields[$field->getName()]))
			{
				continue;
			}

			$fieldValue = $fields[$field->getName()];
			// previously it was applied only for fields with type 'crm', expand it on other types for consistency
			if (!is_iterable($fieldValue) && $field->isMultiple())
			{
				$fields[$field->getName()] = [$fieldValue];
			}
		}

		if (array_key_exists('CATEGORY_ID', $fields) && $this->factory->isCategoriesSupported())
		{
			$categoryId = (int)$fields['CATEGORY_ID'];
			if (!$this->factory->isCategoryAvailable($categoryId))
			{
				$fields['CATEGORY_ID'] = $this->factory->createDefaultCategoryIfNotExist()->getId();
			}
		}
	}

	private function prepareOperation(Service\Operation $operation, array $compatibleOptions): void
	{
		\CCrmEntityHelper::prepareOperationByOptions($operation, $compatibleOptions, $this->checkPermissions);

		//todo remove when all old entities use Operation.
		// We will have to remove automation start in a number of different places

		//since in old api automation is always started by hand when needed,
		// there is a chance that workflows will be started twice
		//when we move to Operation completely, it will be possible to remove this calls at all.
		// but now it is just extra if-branches
		if (!$this->runAutomation)
		{
			$operation->disableAutomation();
		}

		if (!$this->runBizProc)
		{
			$operation->disableBizProc();
		}
	}

	/**
	 * @param Array<string, mixed> $compatibleData
	 * @param Array<string, mixed> $providedFields
	 * @return Array<string, mixed>
	 */
	private function exposeFieldsAfterAdd(array $compatibleData, array $providedFields): array
	{
		$fieldsToExposeAdditionally = array_merge(
			$this->alwaysExposedFields,
			$this->exposedOnlyAfterAddFields,
		);

		return $this->exposeFields($compatibleData, $providedFields, $fieldsToExposeAdditionally, true);
	}

	/**
	 * @param Array<string, mixed> $previousCompatibleData
	 * @param Array<string, mixed> $currentCompatibleData
	 * @param Array<string, mixed> $providedFields
	 * @return Array<string, mixed>
	 */
	private function exposeFieldsAfterUpdate(
		array $previousCompatibleData,
		array $currentCompatibleData,
		array $providedFields
	): array
	{
		$changedFields = [];
		$difference = ComparerBase::compareEntityFields($previousCompatibleData, $currentCompatibleData);
		foreach ($currentCompatibleData as $fieldName => $value)
		{
			if ($difference->isChanged($fieldName))
			{
				$changedFields[] = $fieldName;
			}
		}

		$fieldsToExposeAdditionally = array_merge(
			$this->alwaysExposedFields,
			$this->exposedOnlyAfterUpdateFields,
			$changedFields,
		);

		return $this->exposeFields($currentCompatibleData, $providedFields, $fieldsToExposeAdditionally, false);
	}

	/**
	 * @param Array<string, mixed> $compatibleData
	 * @param Array<string, mixed> $providedFields
	 * @param string[] $fieldsToExposeAdditionally
	 * @return Array<string, mixed>
	 */
	private function exposeFields(
		array $compatibleData,
		array $providedFields,
		array $fieldsToExposeAdditionally,
		bool $exposeAllUserFields
	): array
	{
		$result = [];
		foreach ($providedFields as $providedFieldName => $providedValue)
		{
			if (array_key_exists($providedFieldName, $compatibleData))
			{
				$result[$providedFieldName] = $compatibleData[$providedFieldName];
			}
			else
			{
				$result[$providedFieldName] = $providedValue;
			}
		}

		foreach ($compatibleData as $fieldName => $value)
		{
			if (
				in_array($fieldName, $fieldsToExposeAdditionally, true)
				|| ($exposeAllUserFields && mb_strpos($fieldName, 'UF_') === 0)
			)
			{
				$result[$fieldName] = $value;
			}
		}

		return $result;
	}

	private function returnError(Result $result, array &$fields = []): Result
	{
		if ($result->isSuccess())
		{
			throw new InvalidOperationException('To return error $result should be unsuccessful');
		}

		$this->checkExceptionsContainer = \CCrmEntityHelper::transformOperationErrorsToCheckExceptions($result->getErrors());
		$this->errorMessageContainer = implode(', ', $result->getErrorMessages());
		$this->errorCollectionContainer = $result->getErrorCollection();

		$fields['RESULT_MESSAGE'] = &$this->errorMessageContainer;

		$result->setData([
			'return' => false,
		]);

		return $result;
	}

	private function returnNotFoundError(array &$fields = []): Result
	{
		$result = new Result();
		$result->addError(new Error($this->getNotFoundErrorMessage()));

		return $this->returnError($result, $fields);
	}

	private function returnSuccess($valueToReturn): Result
	{
		$result = new Result();
		$result->setData([
			'return' => $valueToReturn,
		]);

		return $result;
	}

	private function getNotFoundErrorMessage(): string
	{
		return (string)Loc::getMessage('CRM_TYPE_ITEM_NOT_FOUND');
	}

	protected function doPerformUpdate(int $id, array &$fields, array $compatibleOptions): Result
	{
		$this->beforeStart();

		$item = $this->factory->getItem($id);
		if (!$item)
		{
			return $this->returnNotFoundError($fields);
		}
		$previousFields = $item->getCompatibleData();

		$this->prepareFields($fields, false);

		$item->setFromCompatibleData($fields);

		$operation = $this->factory->getUpdateOperation($item);

		$this->prepareOperation($operation, $compatibleOptions);

		$result = $operation->launch();
		if (!$result->isSuccess())
		{
			return $this->returnError($result, $fields);
		}

		$fields = $this->exposeFieldsAfterUpdate($previousFields, $item->getCompatibleData(), $fields);

		return $this->returnSuccess(true);
	}

	protected function doPerformDelete(int $id, array $compatibleOptions): Result
	{
		global $APPLICATION;
		$APPLICATION->ResetException();

		$this->beforeStart();

		$item = $this->factory->getItem($id);
		if(!$item)
		{
			// only 'delete' throws application exception if the item is not found
			$APPLICATION->ThrowException($this->getNotFoundErrorMessage());

			return $this->returnNotFoundError();
		}

		$operation = $this->factory->getDeleteOperation($item);

		$this->prepareOperation($operation, $compatibleOptions);
		// force bizproc deletion regardless of adapter settings
		$processBizproc = (bool)($compatibleOptions['PROCESS_BIZPROC'] ?? true);
		if ($processBizproc)
		{
			$operation->enableBizProc();
			$operation->enableAutomation();
		}
		else
		{
			$operation->disableBizProc();
			$operation->disableAutomation();
		}

		$result = $operation->launch();
		if (!$result->isSuccess())
		{
			return $this->returnError($result);
		}

		return $this->returnSuccess(true);
	}
}
