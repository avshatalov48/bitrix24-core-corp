<?php

namespace Bitrix\HumanResources\Rest\Controllers;

use Bitrix\HumanResources\Engine\RestController;
use Bitrix\HumanResources\Exception\CreationFailedException;
use Bitrix\HumanResources\Exception\DeleteFailedException;
use Bitrix\HumanResources\Exception\UpdateFailedException;
use Bitrix\HumanResources\Contract;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Rest\Factory;
use Bitrix\HumanResources\Model;
use Bitrix\Main;
use Bitrix\Main\Engine\Response\DataType\Page;

final class Structure extends RestController
{
	private readonly Contract\Repository\StructureRepository $repository;
	private readonly Factory\Item\Structure $itemFactory;

	protected function init(): void
	{
		parent::init();

		$this->repository = Container::getStructureRepository();
		$this->itemFactory = new Factory\Item\Structure();
	}

	/**
	 * @restMethod humanresources.structure.getFields
	 */
	public function getFieldsAction(): array
	{
		return ['STRUCTURE' => $this->getViewFields()];
	}

	/**
	 * @restMethod humanresources.structure.add
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
	 * @restMethod humanresources.structure.delete
	 */
	public function deleteAction(int $id): ?bool
	{
		$structure = $this->repository->getById($id);
		if (!$structure)
		{
			return $this->responseWithError($this->getStructureDontExistError($id));
		}

		try
		{
			$this->repository->delete($structure);
		}
		catch (DeleteFailedException $exception)
		{
			return $this->responseWithErrors($exception->getErrors());
		}

		return true;
	}

	/**
	 * @restMethod humanresources.structure.update
	 */
	public function updateAction(int $id, array $fields): ?bool
	{
		$existItem = $this->repository->getById($id);
		if (!$existItem)
		{
			return $this->responseWithError($this->getStructureDontExistError($id));
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
	 * @restMethod humanresources.structure.list
	 */
	public function listAction(
		Main\UI\PageNavigation $pageNavigation,
		array $select = [],
		array $filter = [],
		array $order = [],
	): Page
	{
		return new Page(
			'STRUCTURES',
			$this->getList($select, $filter, $order, $pageNavigation),
			$this->count($filter)
		);
	}

	protected function getEntityTable()
	{
		return new Model\StructureTable();
	}

	private function getStructureDontExistError(int $id): Main\Error
	{
		return new Main\Error("Structure with id: ${id} dont exist",'HR_STRUCTURE_DONT_EXIST');
	}
}