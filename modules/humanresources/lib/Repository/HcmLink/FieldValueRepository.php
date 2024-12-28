<?php

namespace Bitrix\HumanResources\Repository\HcmLink;

use Bitrix\HumanResources\Contract;
use Bitrix\HumanResources\Exception\CreationFailedException;
use Bitrix\HumanResources\Exception\UpdateFailedException;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Item\Collection\HcmLink\FieldValueCollection;
use Bitrix\HumanResources\Model;
use Bitrix\HumanResources\Model\HcmLink\FieldValueTable;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\Type\DateTime;

class FieldValueRepository implements Contract\Repository\HcmLink\FieldValueRepository
{
	public function add(Item\HcmLink\FieldValue $item): Item\HcmLink\FieldValue
	{
		$model = $this->fillModelFromItem($item);
		$saveResult = $model->save();
		if ($saveResult->isSuccess() === false)
		{
			throw (new CreationFailedException())->setErrors($saveResult->getErrorCollection());
		}

		return $this->getItemFromModel($model);
	}

	public function update(Item\HcmLink\FieldValue $item): Item\HcmLink\FieldValue
	{
		$model = FieldValueTable::getById($item->id)->fetchObject();
		$model = $this->fillModelFromItem($item, $model);
		$saveResult = $model->save();
		if ($saveResult->isSuccess() === false)
		{
			throw (new UpdateFailedException())->setErrors($saveResult->getErrorCollection());
		}

		return $this->getItemFromModel($model);
	}

	protected function getModelByUnique(
		int $employeeId,
		int $fieldId,
	): ?Model\HcmLink\FieldValue
	{
		$query = FieldValueTable::query()
		   ->setSelect(['*'])
		   ->setFilter(['=EMPLOYEE_ID' => $employeeId, '=FIELD_ID' => $fieldId])
		   ->setLimit(1)
		;

		return $query->fetchObject();
	}

	public function getByUnique(
		int $employeeId,
		int $fieldId,
	): ?Item\HcmLink\FieldValue
	{
		$model = $this->getModelByUnique($employeeId, $fieldId);

		return  $model ? $this->getItemFromModel($model): null;
	}

	protected function getAllBy(array $filter): Item\Collection\HcmLink\FieldValueCollection
	{
		$query = FieldValueTable::query()
			->setSelect(['*'])
			->setFilter($filter)
		;

		$result = [];
		foreach ($query->fetchCollection()->getAll() as $item)
		{
			$result[] = $this->getItemFromModel($item);
		}

		return new Item\Collection\HcmLink\FieldValueCollection(...$result);
	}

	protected function fillModelFromItem(
		Item\HcmLink\FieldValue $item,
		?Model\HcmLink\FieldValue $model = null,
	): Model\HcmLink\FieldValue
	{
		$model = $model ?? FieldValueTable::createObject(true);
		$model->setEmployeeId($item->employeeId)
			->setFieldId($item->fieldId)
			->setValue($item->value)
			->setExpiredAt($item->expiredAt)
		;

		if ($item->createdAt)
		{
			$model->setCreatedAt($item->createdAt);
		}

		return $model;
	}

	protected function getItemFromModel(
		Model\HcmLink\FieldValue $model,
	): Item\HcmLink\FieldValue
	{
		return new Item\HcmLink\FieldValue(
			employeeId: $model->getEmployeeId(),
			fieldId: $model->getFieldId(),
			value: $model->getValue(),
			createdAt: $model->getCreatedAt(),
			expiredAt: $model->getExpiredAt(),
			id: $model->hasId() ? $model->getId() : null,
		);
	}

	public function getByFieldAndEmployee(
		Item\HcmLink\Field $field,
		Item\HcmLink\Employee $employee,
	): ?Item\HcmLink\FieldValue
	{
		return $this->getByUnique($employee->id, $field->id);
	}

	protected function toItemCollection(
		Model\HcmLink\FieldValueCollection $collection,
	): Item\Collection\HcmLink\FieldValueCollection
	{
		$result = [];
		foreach ($collection as $model)
		{
			$result[] = $this->getItemFromModel($model);
		}

		return new Item\Collection\HcmLink\FieldValueCollection(...$result);
	}

	public function getByFieldIdsAndEmployeeIds(array $fieldIds, array $employeeIds): FieldValueCollection
	{
		$modelCollection = FieldValueTable::query()
			->setSelect(['*'])
			->whereIn('EMPLOYEE_ID', $employeeIds)
			->whereIn('FIELD_ID', $fieldIds)
			->fetchCollection()
		;

		return $this->toItemCollection($modelCollection);
	}

	public function listExpiredIds(int $limit = 100): array
	{
		$modelCollection = FieldValueTable::query()
			->setSelect(['ID'])
			->where('EXPIRED_AT', '<',  new DateTime())
			->setLimit($limit)
			->fetchCollection()
		;

		$ids = [];
		foreach ($modelCollection->getAll() as $model)
		{
			$ids[] = $model->getId();
		}

		return $ids;
	}

	public function removeByIds(array $ids): void
	{
		if (empty($ids))
		{
			return;
		}

		FieldValueTable::deleteByFilter(['@ID' => $ids]);
	}
}
