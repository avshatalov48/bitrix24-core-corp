<?php

namespace Bitrix\Crm\Entity\Compatibility\Adapter;

use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\Entity\Compatibility\Adapter;
use Bitrix\Crm\Entity\FieldContentType;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Model\FieldContentTypeTable;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

final class ContentTypeId extends Adapter
{
	private int $entityTypeId;

	private array $flexibleFields;

	/** @var Array<int, array> */
	private array $previousEntities = [];

	public function __construct(int $entityTypeId)
	{
		$this->entityTypeId = $entityTypeId;

		$this->flexibleFields = FieldContentType::getFieldsWithFlexibleContentType($entityTypeId);
	}

	public function setPreviousFields(int $id, array $previousFields): self
	{
		$this->previousEntities[$id] = $previousFields;

		return $this;
	}

	protected function doPerformAdd(array &$fields, array $compatibleOptions): Result
	{
		$id = (int)($fields['ID'] ?? 0);
		if ($id <= 0)
		{
			return (new Result())->addError(new Error('ID is required for further operations'));
		}

		return $this->doPerformUpdate($id, $fields, $compatibleOptions);
	}

	protected function doPerformUpdate(int $id, array &$fields, array $compatibleOptions): Result
	{
		$diff = ComparerBase::compareEntityFields($this->previousEntities[$id] ?? [], $fields);

		$oldContentTypes = FieldContentTypeTable::loadForItem(new ItemIdentifier($this->entityTypeId, $id));
		$preserveContentType = $compatibleOptions['PRESERVE_CONTENT_TYPE'] ?? false;

		$newContentTypes = [];
		foreach ($this->flexibleFields as $fieldName)
		{
			// always true for new items
			if ($diff->isChanged($fieldName))
			{
				// content is updated
				if ($preserveContentType === true)
				{
					// allow content type to be set
					$newContentTypes[$fieldName] =
						$fields[FieldContentType::compileContentTypeIdFieldName($fieldName)]
						?? $oldContentTypes[$fieldName]
						?? \CCrmContentType::Undefined
					;

					if (!\CCrmContentType::IsDefined($newContentTypes[$fieldName]))
					{
						$newContentTypes[$fieldName] = \CCrmContentType::Html;
					}
				}
				else
				{
					// reset content type to default value
					$newContentTypes[$fieldName] = \CCrmContentType::Html;
				}
			}
			else
			{
				// content not changed - do nothing
				$newContentTypes[$fieldName] = $oldContentTypes[$fieldName] ?? \CCrmContentType::Html;
			}
		}

		return FieldContentTypeTable::saveForItem(
			new ItemIdentifier($this->entityTypeId, $id),
			$newContentTypes,
		);
	}

	protected function doPerformDelete(int $id, array $compatibleOptions): Result
	{
		return FieldContentTypeTable::deleteByItem(new ItemIdentifier($this->entityTypeId, $id));
	}
}
