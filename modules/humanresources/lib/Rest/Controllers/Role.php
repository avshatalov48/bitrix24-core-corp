<?php

namespace Bitrix\HumanResources\Rest\Controllers;

use Bitrix\HumanResources\Engine\RestController;
use Bitrix\HumanResources\Exception\CreationFailedException;
use Bitrix\HumanResources\Exception\DeleteFailedException;
use Bitrix\HumanResources\Exception\UpdateFailedException;
use Bitrix\HumanResources\Contract;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Rest\Factory;
use Bitrix\HumanResources\Model;

use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main;

/* @todo Check access in all methods */
final class Role extends RestController
{
	private readonly Contract\Repository\RoleRepository $repository;
	private readonly Factory\Item\Role $itemFactory;

	public function init(): void
	{
		parent::init();

		$this->repository = Container::getRoleRepository();
		$this->itemFactory = new Factory\Item\Role();
	}

	/**
	 * @restMethod humanresources.role.getFields
	 */
	public function getFieldsAction(): array
	{
		return ['ROLE' => $this->getViewFields()];
	}

	/**
	 * @restMethod humanresources.role.add
	 */
	public function addAction(array $fields): ?int
	{
		$validationResult = $this->itemFactory->validateRestFields($fields);
		if (!$validationResult->isSuccess())
		{
			return $this->responseWithErrors($validationResult->getErrors());
		}

		$item = $this->itemFactory->createFromRestFields($fields);
		try
		{
			$item = $this->repository->create($item);
		}
		catch (CreationFailedException $exception)
		{
			return $this->responseWithErrors($exception->getErrors());
		}

		return $item->id;
	}

	/**
	 * @restMethod humanresources.role.delete
	 */
	public function deleteAction(int $id): ?bool
	{
		$roleItem = $this->repository->getById($id);
		if (!$roleItem)
		{
			return $this->responseWithError(
				error: $this->getRoleDontExistError($id)
			);
		}

		try
		{
			$this->repository->remove(new Item\Role(id: $id));
		}
		catch (DeleteFailedException $exception)
		{
			return $this->responseWithErrors($exception->getErrors());
		}

		return true;
	}

	/**
	 * @restMethod humanresources.role.get
	 */
	public function getAction(int $id): ?array
	{
		$roleItem = $this->repository->getById($id);
		if (!$roleItem)
		{
			return $this->responseWithError(
				error: $this->getRoleDontExistError($id)
			);
		}

		return [
			'ROLE' => $this->get($id),
		];
	}

	/**
	 * @restMethod humanresources.role.update
	 */
	public function updateAction(int $id, array $fields): ?bool
	{
		$roleItem = $this->repository->getById($id);
		if (!$roleItem)
		{
			return $this->responseWithError(
				error: $this->getRoleDontExistError($id)
			);
		}

		$validationResult = $this->itemFactory->validateRestFields($fields);
		if (!$validationResult->isSuccess())
		{
			return $this->responseWithErrors($validationResult->getErrors());
		}

		$item = $this->itemFactory->createFromRestFields($fields);
		$item->id = $id;

		try
		{
			$this->repository->update($item);
		}
		catch (UpdateFailedException $exception)
		{
			return $this->responseWithErrors($exception->getErrors());
		}

		return true;
	}

	/**
	 * @restMethod humanresources.role.list
	 */
	public function listAction(
		PageNavigation $pageNavigation,
		array $select = [],
		array $filter = [],
		array $order = []
	): Page
	{
		return new Page(
			'ROLES',
			$this->getList($select, $filter, $order, $pageNavigation),
			$this->count($filter)
		);
	}

	protected function getEntityTable()
	{
		return new Model\RoleTable();
	}

	private function getRoleDontExistError(int $id): Main\Error
	{
		return new Main\Error("Role with id: ${id} dont exist",'HR_ROLE_DONT_EXIST');
	}
}