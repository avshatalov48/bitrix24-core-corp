<?php

namespace Bitrix\Crm\Item\FieldImplementation;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Item\FieldImplementation;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\FileUploader;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\Result;

final class File implements FieldImplementation
{
	private EntityObject $entityObject;
	private Field\Collection $fileFields;
	/** @var Array<string, string> [$commonFieldName => $entityFieldName] */
	private array $fieldsMap;
	private FileUploader $fileUploader;

	/** @var Array<string, int[]> */
	private array $previousFileIds = [];

	public function __construct(EntityObject $entityObject, Field\Collection $fileFields, array $fieldsMap)
	{
		$this->entityObject = $entityObject;
		$this->fileFields = $fileFields;
		$this->fieldsMap = $fieldsMap;
		$this->fileUploader = Container::getInstance()->getFileUploader();
	}

	public function getHandledFieldNames(): array
	{
		return $this->fileFields->getFieldNameList();
	}

	public function get(string $commonFieldName)
	{
		return $this->entityObject->get($commonFieldName);
	}

	public function set(string $commonFieldName, $value): void
	{
		$this->entityObject->set($commonFieldName, $value);
		$this->previousFileIds[$commonFieldName] = (array)$this->remindActual($commonFieldName);
	}

	public function isChanged(string $commonFieldName): bool
	{
		return $this->entityObject->isChanged($commonFieldName);
	}

	public function remindActual(string $commonFieldName)
	{
		return $this->entityObject->remindActual($commonFieldName);
	}

	public function reset(string $commonFieldName): void
	{
		$this->entityObject->reset($commonFieldName);
		unset($this->previousFileIds[$commonFieldName]);
	}

	public function unset(string $commonFieldName): void
	{
		$this->entityObject->unset($commonFieldName);
		unset($this->previousFileIds[$commonFieldName]);
	}

	public function getDefaultValue(string $commonFieldName)
	{
		return $this->entityObject->entity->getField($commonFieldName)->getDefaultValue();
	}

	public function afterSuccessfulItemSave(Item $item, EntityObject $entityObject): void
	{
	}

	public function save(): Result
	{
		foreach ($this->fileFields as $field)
		{
			$fileIds = (array)$this->get($field->getName());
			foreach ($fileIds as $fileId)
			{
				if ($fileId > 0)
				{
					$this->fileUploader->markFileAsPersistent((int)$fileId);
				}
			}
		}

		foreach ($this->previousFileIds as $commonFieldName => $arrayOfPreviousFileIds)
		{
			$currentFileIds = (array)$this->get($commonFieldName);
			$deletedFiles = array_diff($arrayOfPreviousFileIds, $currentFileIds);

			foreach ($deletedFiles as $deletedFileId)
			{
				$this->fileUploader->deleteFilePersistently((int)$deletedFileId);
			}
		}
		$this->previousFileIds = [];

		return new Result();
	}

	public function getSerializableFieldNames(): array
	{
		return $this->getHandledFieldNames();
	}

	public function getExternalizableFieldNames(): array
	{
		return $this->getHandledFieldNames();
	}

	public function transformToExternalValue(string $commonFieldName, $value, int $valuesType)
	{
		return $value;
	}

	public function setFromExternalValues(array $externalValues): void
	{
		foreach ($this->fileFields as $field)
		{
			// replacement for isset($value), since null is also a valid case
			$isValueProvided = false;

			if (array_key_exists($field->getName(), $externalValues))
			{
				$isValueProvided = true;
				$value = $externalValues[$field->getName()];

				if (is_array($value))
				{
					$internalizedValue = $this->internalizeArrayValue($field, $value);
					if (is_null($internalizedValue))
					{
						continue;
					}

					$value = $internalizedValue;
				}
			}

			$entityFieldName = $this->fieldsMap[$field->getName()] ?? $field->getName();
			$deleteKey = $entityFieldName . '_del';
			if (array_key_exists($deleteKey, $externalValues))
			{
				$fileIdToDelete = $externalValues[$deleteKey];
				if (is_numeric($fileIdToDelete))
				{
					$fileIdToDelete = (int)$fileIdToDelete;
					$this->fileUploader->deleteFilePersistently($fileIdToDelete);

					if (
						($isValueProvided && $fileIdToDelete === (int)$value)
						|| (!$isValueProvided && $fileIdToDelete === (int)$this->get($field->getName()))
					)
					{
						$value = 0;
						$isValueProvided = true;
					}
				}
			}

			if ($isValueProvided)
			{
				$this->set($field->getName(), $value);
			}
		}
	}

	/**
	 * @param Field $field
	 * @param array $externalValue
	 * @return int|int[]|null - returns null if something went wrong
	 */
	private function internalizeArrayValue(Field $field, array $externalValue)
	{
		if (!$field->isMultiple())
		{
			return $this->fileArrayToFileId($field, $externalValue);
		}

		$files = [];
		foreach ($externalValue as $singleValue)
		{
			if (is_numeric($singleValue))
			{
				$files[] = (int)$singleValue;
			}
			elseif (is_array($singleValue))
			{
				$fileId = $this->fileArrayToFileId($field, $singleValue);
				if ($fileId > 0)
				{
					$files[] = $fileId;
				}
			}
		}

		return $files;
	}

	private function fileArrayToFileId(Field $field, array $value): ?int
	{
		if (!$this->fileUploader->checkFile($field, $value)->isSuccess())
		{
			return null;
		}

		return $this->fileUploader->saveFileTemporary($field, $value);
	}

	public function afterItemClone(Item $item, EntityObject $entityObject): void
	{
		$this->entityObject = $entityObject;
	}

	public function getFieldNamesToFill(): array
	{
		return $this->getHandledFieldNames();
	}
}
