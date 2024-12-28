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

	public function beforeItemSave(Item $item, EntityObject $entityObject): void
	{
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
			if ($field->isMultiple())
			{
				$this->internalizeMultipleField($field, $externalValues);
			}
			else
			{
				$this->internalizeSingleField($field, $externalValues);
			}
		}
	}

	private function internalizeMultipleField(Field $field, array $externalValues): void
	{
		$newValue = null;

		$toDelete = $this->extractFileIdsMarkedToBeDeleted($field, $externalValues);

		if (isset($externalValues[$field->getName()]) && is_array($externalValues[$field->getName()]))
		{
			if (empty($externalValues[$field->getName()]))
			{
				// they want to delete all files
				$newValue = [];
			}
			else
			{
				foreach ($externalValues[$field->getName()] as $singleValue)
				{
					if (is_numeric($singleValue) && (int)$singleValue > 0)
					{
						$newValue[] = (int)$singleValue;
					}
					elseif (is_array($singleValue))
					{
						$fileId = $this->fileArrayToFileId($field, $singleValue);
						if ($fileId > 0)
						{
							$newValue[] = (int)$fileId;
						}
					}
				}
			}
		}

		if (is_array($newValue))
		{
			$this->set($field->getName(), array_diff($newValue, $toDelete));
		}
		elseif (!empty($toDelete))
		{
			$currentFiles = array_filter((array)$this->get($field->getName()));

			$this->set($field->getName(), array_diff($currentFiles, $toDelete));
		}
	}

	private function internalizeSingleField(Field $field, array $externalValues): void
	{
		$isNewValueProvided = false;
		$newValue = null;

		$toDelete = $this->extractFileIdsMarkedToBeDeleted($field, $externalValues);

		if (array_key_exists($field->getName(), $externalValues))
		{
			$isNewValueProvided = true;
			$newValue = $externalValues[$field->getName()];

			if (is_array($newValue))
			{
				$fileId = $this->fileArrayToFileId($field, $newValue);
				if ($fileId > 0)
				{
					$newValue = (int)$fileId;
				}
				else
				{
					$isNewValueProvided = false;
					$newValue = null;
				}
			}
			else
			{
				$newValue = (int)$newValue;
			}
		}

		if ($isNewValueProvided)
		{
			if (in_array($newValue, $toDelete, true))
			{
				$newValue = 0;
			}

			$this->set($field->getName(), $newValue);
		}
		elseif (!empty($toDelete))
		{
			$this->set($field->getName(), 0);
		}
	}

	private function extractFileIdsMarkedToBeDeleted(Field $field, array $externalValues): array
	{
		$fieldName = $this->fieldsMap[$field->getName()] ?? $field->getName();

		$toDelete = [];

		if (isset($externalValues[$fieldName]))
		{
			$value = $externalValues[$fieldName];
			if (!$field->isMultiple())
			{
				$value = [$value];
			}

			if (is_array($value))
			{
				foreach ($value as $singleValue)
				{
					if (
						isset($singleValue['del'])
						&& $singleValue['del'] === true
						&& isset($singleValue['old_id'])
						&& is_numeric($singleValue['old_id'])
						&& $this->isFileBoundToItem($field, (int)$singleValue['old_id'])
					)
					{
						$toDelete[] = (int)$singleValue['old_id'];
					}
				}
			}
		}

		$entityFieldName = $this->fieldsMap[$field->getName()] ?? $field->getName();
		$deleteKey = $entityFieldName . '_del';
		if (array_key_exists($deleteKey, $externalValues))
		{
			$deleteValue = $externalValues[$deleteKey];
			if (is_numeric($deleteValue))
			{
				// $toDelete is fileId
				$deleteValue = (int)$deleteValue;

				if ($this->isFileBoundToItem($field, $deleteValue))
				{
					$toDelete[] = $deleteValue;
				}
			}
			// delete current file
			elseif ($deleteValue === 'Y' && !$field->isMultiple())
			{
				$toDelete[] = (int)$this->get($field->getName());
			}
		}

		return $toDelete;
	}

	private function isFileBoundToItem(Field $field, int $fileId): bool
	{
		$ids = array_filter((array)$this->get($field->getName()));

		return in_array($fileId, $ids, true);
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
