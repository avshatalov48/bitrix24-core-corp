<?php

namespace Bitrix\HumanResources\Repository;

use Bitrix\HumanResources\Exception\CreationFailedException;
use Bitrix\HumanResources\Exception\DeleteFailedException;
use Bitrix\HumanResources\Exception\UpdateFailedException;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Item\Role;
use Bitrix\HumanResources\Model;
use Bitrix\HumanResources\Type\RoleChildAffectionType;
use Bitrix\HumanResources\Type\RoleEntityType;
use Bitrix\Main;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

class RoleRepository implements \Bitrix\HumanResources\Contract\Repository\RoleRepository
{
	/**
	 * Create a new role.
	 *
	 * @param Item\Role $role The Role object to be created.
	 *
	 * @return Item\Role The created Role object.
	 *
	 * @throws CreationFailedException Thrown if the creation of the role fails.
	 */
	public function create(Item\Role $role): Item\Role
	{
		$model = new Model\Role();
		$model->setName($role->name);
		$model->setXmlId($role->xmlId);
		$model->setEntityType($role->entityType->value);
		$model->setChildAffectionType($role->childAffectionType->value);
		$model->setPriority($role->priority);

		$result = $model->save();

		if (!$result->isSuccess())
		{
			throw (new CreationFailedException())->setErrors($result->getErrorCollection());
		}

		return $this->convertModelToItem($model);
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws UpdateFailedException
	 */
	public function update(Item\Role $role): void
	{
		if ($role->id === null)
		{
			throw (new UpdateFailedException())->addError(new Main\Error('Role id is not set'));
		}

		$model = Model\RoleTable::getById($role->id)
			->fetchObject()
		;
		if (!$model)
		{
			throw (new UpdateFailedException())->addError(new Main\Error("Role with id $role->id dont exist"));
		}

		if (isset($role->name))
		{
			$model->setName($role->name);
		}

		if (isset($role->priority))
		{
			$model->setPriority($role->priority);
		}

		if (isset($role->xmlId))
		{
			$model->setXmlId($role->xmlId);
		}

		if (isset($role->entityType))
		{
			$model->setEntityType($role->entityType->value);
		}

		if (isset($role->childAffectionType))
		{
			$model->setChildAffectionType($role->childAffectionType->value);
		}

		$result = $model->save();
		if (!$result->isSuccess())
		{
			throw (new UpdateFailedException())->setErrors($result->getErrorCollection());
		}
	}

	/**
	 * Remove a role item.
	 *
	 * @param Item\Role $role The role item to be removed.
	 *
	 * @return void
	 * @throws \Bitrix\HumanResources\Exception\DeleteFailedException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function remove(Item\Role $role): void
	{
		$model = Model\RoleTable::getById($role->id)
			->fetchObject();

		if (!$model)
		{
			return;
		}

		$result = $model->delete();

		if (!$result->isSuccess())
		{
			throw (new DeleteFailedException())->setErrors($result->getErrorCollection());
		}
	}

	public function list(int $limit = 50, int $offset = 0): Item\Collection\RoleCollection
	{
		$roleCollection = new Item\Collection\RoleCollection();

		if ($offset < 0 || $limit < 1)
		{
			return $roleCollection;
		}

		$models = Model\RoleTable::query()
			->setSelect(['*'])
			->setLimit($limit)
			->setOffset($offset)
			->fetchAll()
		;

		foreach ($models as $model)
		{
			$roleCollection->add($this->convertModelArrayToItem($model));
		}

		return $roleCollection;
	}

	/**
	 * @param int $id
	 * @return Item\Role|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getById(int $id): ?Item\Role
	{
		$model = Model\RoleTable::getById($id)
			->fetchObject()
		;

		if (!$model)
		{
			return null;
		}

		return $this->convertModelToItem($model);
	}

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findByXmlId(string $xmlId): ?Item\Role
	{
		$model = Model\RoleTable::getList([
			'filter' => ['=XML_ID' => $xmlId],
			'cache' => ['ttl' => 86400],
			'limit' => 1
		])->fetchObject();

		if ($model)
		{
			return $this->convertModelToItem($model);
		}

		return null;
	}

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findByIds(array $ids): Item\Collection\RoleCollection
	{
		$collection = new Item\Collection\RoleCollection();
		if (empty($ids))
		{
			return $collection;
		}

		$modelsArray = Model\RoleTable::query()
			->setSelect(['*'])
			->whereIn('ID', $ids)
			->addOrder('PRIORITY')
			->fetchAll()
		;

		foreach ($modelsArray as $modelArray)
		{
			$collection->add($this->convertModelArrayToItem($modelArray));
		}

		return $collection;
	}

	private function convertModelArrayToItem(array $role): Item\Role
	{
		return new Item\Role(
			name: $role['NAME'],
			xmlId: $role['XML_ID'],
			entityType: RoleEntityType::tryFrom($role['ENTITY_TYPE']),
			childAffectionType: RoleChildAffectionType::tryFrom($role['CHILD_AFFECTION_TYPE']),
			id: (int)$role['ID'],
			priority: (int)$role['PRIORITY'],
		);
	}

	private function convertModelToItem(Model\Role $role): Item\Role
	{
		return new Item\Role(
			name: $role->getName(),
			xmlId: $role->getXmlId(),
			entityType: RoleEntityType::tryFrom($role->getEntityType()),
			childAffectionType: RoleChildAffectionType::tryFrom($role->getChildAffectionType()),
			id: $role->getId(),
			priority: $role->getPriority(),
		);
	}

	private function convertModelCollectionToItemCollection(
		Model\RoleCollection $modelCollection
	): Item\Collection\RoleCollection
	{
		$models = $modelCollection->getAll();
		$items = array_map([$this, 'convertModelToItem'], $models);

		$itemCollection = new Item\Collection\RoleCollection();
		foreach ($items as $item)
		{
			$itemCollection->add($item);
		}

		return $itemCollection;
	}
}