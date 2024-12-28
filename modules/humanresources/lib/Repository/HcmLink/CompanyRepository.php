<?php

namespace Bitrix\HumanResources\Repository\HcmLink;

use Bitrix\HumanResources\Contract;
use Bitrix\HumanResources\Exception\CreationFailedException;
use Bitrix\HumanResources\Exception\DeleteFailedException;
use Bitrix\HumanResources\Exception\UpdateFailedException;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Model;
use Bitrix\HumanResources\Model\HcmLink\CompanyTable;
use Bitrix\Main\Error;

class CompanyRepository implements Contract\Repository\HcmLink\CompanyRepository
{
	/**
	 * @throws CreationFailedException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function save(Item\HcmLink\Company $item): Item\HcmLink\Company
	{
		$model = $item->id
			? CompanyTable::getById($item->id)->fetchObject()
			: $this->getModelByUnique($item->code);

		$model = $this->fillModelFromItem($item, $model);

		$result = $model->save();

		if ($result->isSuccess() === false)
		{
			throw (new UpdateFailedException())
				->setErrors($result->getErrorCollection())
			;
		}

		return $this->getItemFromModel($model);
	}

	public function add(Item\HcmLink\Company $item): Item\HcmLink\Company
	{
		$model = $this->fillModelFromItem($item);
		$result = $model->save();
		if ($result->isSuccess() === false)
		{
			throw (new CreationFailedException())->setErrors($result->getErrorCollection());
		}

		return $this->getItemFromModel($model);
	}

	public function update(Item\HcmLink\Company $item): Item\HcmLink\Company
	{
		$model = CompanyTable::getById($item->id)->fetchObject();
		if ($model === null)
		{
			throw (new UpdateFailedException())->addError(new Error('Company not found'));
		}

		$model = $this->fillModelFromItem($item, $model);
		$result = $model->save();
		if ($result->isSuccess() === false)
		{
			throw (new UpdateFailedException())->setErrors($result->getErrorCollection());
		}

		return $this->getItemFromModel($model);
	}

	/** @throws DeleteFailedException */
	public function delete(int $id): void
	{
		$result = CompanyTable::delete($id);
		if ($result->isSuccess() === false)
		{
			throw (new DeleteFailedException())
				->setErrors($result->getErrorCollection())
			;
		}
	}

	public function getList(?int $limit = null, int $offset = 0): Item\Collection\HcmLink\CompanyCollection
	{
		$query = CompanyTable::query()
			->setSelect(['*'])
			->addOrder('ID')
			->setOffset($offset)
		;

		if ($limit !== null)
		{
			$query->setLimit($limit);
		}

		return $this->toItemCollection($query->fetchCollection());
	}

	public function getById(int $id): ?Item\HcmLink\Company
	{
		return $this->getBy(['=ID' => $id]);
	}

	public function getModelByUnique (string $code): ?Model\HcmLink\Company
	{
		$query = CompanyTable::query()
							->setSelect(['*'])
							->setFilter(['=CODE' => $code])
							->setLimit(1)
		;

		return $query->fetchObject();
	}


	public function getByUnique(string $code): ?Item\HcmLink\Company
	{
		$model = $this->getModelByUnique($code);

		return $model ? $this->getItemFromModel($model) : null;
	}

	public function getByCompanyId(int $myCompanyId): Item\Collection\HcmLink\CompanyCollection
	{
		$query = CompanyTable::query()
			->setSelect(['*'])
			->where('MY_COMPANY_ID', $myCompanyId)
			->addOrder('ID')
		;

		return $this->toItemCollection($query->fetchCollection());
	}

	public function getListByIds(array $ids, ?int $limit = null, int $offset = 0): Item\Collection\HcmLink\CompanyCollection
	{
		if (empty($ids))
		{
			return new Item\Collection\HcmLink\CompanyCollection();
		}

		$query = CompanyTable::query()
			->setSelect(['*'])
			->whereIn('ID', $ids)
			->addOrder('ID')
			->setOffset($offset)
		;

		if ($limit !== null)
		{
			$query->setLimit($limit);
		}

		return $this->toItemCollection($query->fetchCollection());
	}

	protected function fillModelFromItem(
		Item\HcmLink\Company $item,
		?Model\HcmLink\Company $model = null,
	): Model\HcmLink\Company
	{
		$model = $model ?? CompanyTable::createObject(true);
		$model->setCode($item->code)
			->setMyCompanyId($item->myCompanyId)
			->setTitle($item->title)
			->setData($item->data)
		;

		return $model;
	}

	protected function getItemFromModel(Model\HcmLink\Company $model): Item\HcmLink\Company
	{
		return new Item\HcmLink\Company(
			code: $model->getCode(),
			myCompanyId: $model->getMyCompanyId(),
			title: $model->getTitle(),
			data: $model->getData(),
			createdAt: $model->getCreatedAt(),
			id: $model->getId(),
		);
	}

	protected function getBy(array $filter): ?Item\HcmLink\Company
	{
		$query = CompanyTable::query()
			->setSelect(['*'])
			->setFilter($filter)
			->setLimit(1)
		;
		$model = $query->fetchObject();

		return  $model ? $this->getItemFromModel($model): null;
	}

	protected function toItemCollection(
		Model\HcmLink\CompanyCollection $collection,
	): Item\Collection\HcmLink\CompanyCollection
	{
		$result = [];
		foreach ($collection as $model) {
			$result[] = $this->getItemFromModel($model);
		}
		return new Item\Collection\HcmLink\CompanyCollection(...$result);
	}
}
