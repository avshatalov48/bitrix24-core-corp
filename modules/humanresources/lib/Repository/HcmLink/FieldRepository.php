<?php

namespace Bitrix\HumanResources\Repository\HcmLink;

use Bitrix\HumanResources\Contract;
use Bitrix\HumanResources\Exception\CreationFailedException;
use Bitrix\HumanResources\Exception\UpdateFailedException;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Model;
use Bitrix\HumanResources\Model\HcmLink\FieldTable;
use Bitrix\HumanResources\Type\HcmLink\FieldEntityType;
use Bitrix\HumanResources\Type\HcmLink\FieldType;

class FieldRepository implements Contract\Repository\HcmLink\FieldRepository
{
	/**
	 * @throws CreationFailedException
	 */
	public function save(Item\HcmLink\Field $item): Item\HcmLink\Field
	{
		$model = $item->id
			? FieldTable::getById($item->id)->fetchObject()
			: $this->getModelByUnique($item->companyId, $item->field);

		$model = $this->fillModelFromItem($item, $model);

		$saveResult = $model->save();
		if ($saveResult->isSuccess() === false)
		{
			throw (new CreationFailedException())
				->setErrors($saveResult->getErrorCollection())
			;
		}

		return $this->getItemFromModel($model);
	}

	public function add(Item\HcmLink\Field $field): Item\HcmLink\Field
	{
		$model = $this->fillModelFromItem($field);
		$result = $model->save();

		if ($result->isSuccess() === false)
		{
			throw (new CreationFailedException())->setErrors($result->getErrorCollection());
		}

		return $this->getItemFromModel($model);
	}

	public function update(Item\HcmLink\Field $field): Item\HcmLink\Field
	{
		$model = $this->fillModelFromItem(
			$field,
			FieldTable::getById($field->id)->fetchObject()
		);
		$result = $model->save();

		if ($result->isSuccess() === false)
		{
			throw (new UpdateFailedException())->setErrors($result->getErrorCollection());
		}

		return $field;
	}

	public function deleteByCompany(int $companyId): void
	{
		FieldTable::deleteByFilter(['=COMPANY_ID' => $companyId]);
	}

	public function delete(int $id): void
	{
		FieldTable::delete($id);
	}

	public function getByCompany(
		int $companyId
	): Item\Collection\HcmLink\FieldCollection
	{
		return $this->getAllBy([['=COMPANY_ID' => $companyId]]);
	}

	protected function getModelByUnique(
		int $companyId,
		string $code
	): ?Model\HcmLink\Field
	{
		$query = FieldTable::query()
			->setSelect(['*'])
			->setFilter(['=COMPANY_ID' => $companyId, '=CODE' => $code])
			->setLimit(1)
		;

		return $query->fetchObject();
	}

	public function getByUnique(
		int $companyId,
		string $code
	): ?Item\HcmLink\Field
	{
		$model = $this->getModelByUnique($companyId, $code);

		return  $model ? $this->getItemFromModel($model): null;
	}

	protected function getBy(array $filter): ?Item\HcmLink\Field
	{
		$model = FieldTable::query()
			->setSelect(['*'])
			->setFilter($filter)
			->setLimit(1)
			->fetchObject()
		;

		return  $model ? $this->getItemFromModel($model): null;
	}
	
	protected function getAllBy(
		array $filter
	): Item\Collection\HcmLink\FieldCollection
	{
		$query = FieldTable::query()
			->setSelect(['*'])
			->setFilter($filter)
			->addOrder('TITLE')
			->addOrder('ID')
		;

		return $this->toItemCollection($query->fetchCollection());
	}

	protected function toItemCollection(
		Model\HcmLink\FieldCollection $collection
	): Item\Collection\HcmLink\FieldCollection
	{
		$result = [];
		foreach ($collection as $model)
		{
			$result[] = $this->getItemFromModel($model);
		}

		return new Item\Collection\HcmLink\FieldCollection(...$result);
	}

	protected function fillModelFromItem(
		Item\HcmLink\Field $item,
		?Model\HcmLink\Field $model = null
	): Model\HcmLink\Field
	{
		$model = $model ?? FieldTable::createObject(true);
		$model->setCompanyId($item->companyId)
			->setTitle($item->title)
			->setType($item->type->value)
			->setEntityType($item->entityType->value)
			->setCode($item->field)
			->setTtl($item->ttl)
		;

		return $model;
	}

	protected function getItemFromModel(
		Model\HcmLink\Field $model
	): Item\HcmLink\Field
	{
		return new Item\HcmLink\Field(
			companyId: $model->getCompanyId(),
			field: $model->getCode(),
			title: $model->getTitle(),
			type: FieldType::tryFrom($model->getType()) ?? FieldType::UNKNOWN,
			entityType: FieldEntityType::tryFrom($model->getType()) ?? FieldEntityType::UNKNOWN,
			ttl: $model->getTtl(),
			id: $model->getId(),
		);
	}

	public function getByIds(array $fieldIds): Item\Collection\HcmLink\FieldCollection
	{
		$collection = FieldTable::query()
			->setSelect(['*'])
			->whereIn('ID', $fieldIds)
			->fetchCollection()
		;

		return $this->toItemCollection($collection);
	}

	public function getById(int $id): ?Item\HcmLink\Field
	{
		$model = FieldTable::query()
		   ->setSelect(['*'])
		   ->where('ID', $id)
		   ->setLimit(1)
		   ->fetchObject()
		;

		return $model ? $this->getItemFromModel($model): null;
	}
}
