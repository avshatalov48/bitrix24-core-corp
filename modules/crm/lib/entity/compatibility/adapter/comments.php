<?php

namespace Bitrix\Crm\Entity\Compatibility\Adapter;

use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\Entity\CommentsHelper;
use Bitrix\Crm\Entity\Compatibility\Adapter;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Model\FieldContentTypeTable;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

final class Comments extends Adapter
{
	private int $entityTypeId;

	private array $flexibleFields;

	/** @var Array<int, array> */
	private array $previousEntities = [];

	public function __construct(int $entityTypeId)
	{
		$this->entityTypeId = $entityTypeId;

		$this->flexibleFields = CommentsHelper::getFieldsWithFlexibleContentType($entityTypeId);
	}

	public function setPreviousFields(int $id, array $previousFields): self
	{
		$this->previousEntities[$id] = $previousFields;

		return $this;
	}

	public function normalizeFields(?int $id, array &$fields): void
	{
		$previousFields = [];
		if ($id > 0)
		{
			$previousFields = $this->previousEntities[$id] ?? [];
		}

		$diff = ComparerBase::compareEntityFields($previousFields, $fields);

		foreach ($this->flexibleFields as $fieldName)
		{
			if ($diff->isChanged($fieldName))
			{
				$fields[$fieldName] = CommentsHelper::normalizeComment($diff->getCurrentValue($fieldName), ['p']);
			}
		}
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
		$this->normalizeFields($id, $fields);

		$contentTypes = FieldContentTypeTable::loadForItem(new ItemIdentifier($this->entityTypeId, $id));
		$diff = ComparerBase::compareEntityFields($this->previousEntities[$id] ?? [], $fields);

		foreach ($this->flexibleFields as $fieldName)
		{
			if (
				$diff->isChanged($fieldName)
				|| (empty($diff->getPreviousValue($fieldName)) && empty($diff->getCurrentValue($fieldName)))
			)
			{
				$contentTypes[$fieldName] = \CCrmContentType::BBCode;
			}
		}

		return FieldContentTypeTable::saveForItem(
			new ItemIdentifier($this->entityTypeId, $id),
			$contentTypes,
		);
	}

	protected function doPerformDelete(int $id, array $compatibleOptions): Result
	{
		return FieldContentTypeTable::deleteByItem(new ItemIdentifier($this->entityTypeId, $id));
	}
}
