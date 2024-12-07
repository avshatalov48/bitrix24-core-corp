<?php

namespace Bitrix\HumanResources\Rest\Controllers\Structure;

use Bitrix\HumanResources\Engine\RestController;
use Bitrix\HumanResources\Contract;
use Bitrix\HumanResources\Model;
use Bitrix\HumanResources\Exception\DeleteFailedException;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Rest\Factory;
use Bitrix\Main;
use Bitrix\Main\Engine\Response\DataType\Page;

final class Node extends RestController
{
	private readonly Contract\Repository\NodeRepository $repository;
	private readonly Contract\Service\NodeService $service;
	private readonly Factory\Item\Node $itemFactory;

	protected function init(): void
	{
		parent::init();
		$this->repository = Container::getNodeRepository();
		$this->service = Container::getNodeService();
		$this->itemFactory = new Factory\Item\Node();
	}

	/**
	 * @restMethod humanresources.structure.node.getFields
	 */
	public function getFieldsAction(): array
	{
		return ['NODE' => $this->getViewFields()];
	}

	/**
	 * @restMethod humanresources.structure.node.add
	 */
	public function addAction(array $fields): ?int
	{
		$validationResult = $this->itemFactory->validateRestFields($fields);
		if (!$validationResult->isSuccess())
		{
			return $this->responseWithErrors($validationResult->getErrors());
		}

		$item = $this->service->insertAndMoveNode(
			node: $this->itemFactory->createFromRestFields($fields),
		);
		if ($item->id === null)
		{
			return $this->responseWithError(new Main\Error('Failed to add node'));
		}

		return $item->id;
	}

	/**
	 * @restMethod humanresources.structure.node.get
	 */
	public function getAction(int $id): ?array
	{
		$item = $this->repository->getById($id);
		if (!$item)
		{
			return $this->responseWithError($this->getNodeDontExistError($id));
		}

		return [
			'NODE' => $this->get($id),
		];
	}

	/**
	 * @restMethod humanresources.structure.node.list
	 */
	public function listAction(
		Main\UI\PageNavigation $pageNavigation,
		array $select = [],
		array $filter = [],
		array $order = [],
	): Page
	{
		return new Page(
			'NODES',
			$this->getList($select, $filter, $order, $pageNavigation),
			$this->count($filter)
		);
	}

	/**
	 * @restMethod humanresources.structure.node.update
	 */
	public function updateAction(int $id, array $fields): ?bool
	{
		$existItem = $this->repository->getById($id);
		if ($existItem === null)
		{
			return $this->responseWithError($this->getNodeDontExistError($id));
		}

		$validationResult = $this->itemFactory->validateRestFields($fields);
		if (!$validationResult->isSuccess())
		{
			return $this->responseWithErrors($validationResult->getErrors());
		}

		$item = $this->repository->update(
			node: $this->itemFactory->createFromRestFields($fields),
		);

		return true;
	}

	/**
	 * @restMethod humanresources.structure.node.delete
	 */
	public function deleteAction(int $id): ?bool
	{
		$node = $this->repository->getById($id);
		if (!$node)
		{
			return $this->responseWithError($this->getNodeDontExistError($id));
		}

		try
		{
			$this->service->removeNode($node);
		}
		catch (DeleteFailedException $exception)
		{
			return $this->responseWithErrors($exception->getErrors());
		}

		return true;
	}

	protected function getEntityTable()
	{
		return new Model\NodeTable();
	}

	private function getNodeDontExistError(int $id): Main\Error
	{
		return new Main\Error("Node with id: ${id} dont exist",'HR_NODE_DONT_EXIST');
	}
}