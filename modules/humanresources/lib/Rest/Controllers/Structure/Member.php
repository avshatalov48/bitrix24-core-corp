<?php

namespace Bitrix\HumanResources\Rest\Controllers\Structure;

use Bitrix\HumanResources\Engine\RestController;
use Bitrix\HumanResources\Contract;
use Bitrix\HumanResources\Exception\UpdateFailedException;
use Bitrix\HumanResources\Model;
use Bitrix\HumanResources\Exception\DeleteFailedException;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Rest\Factory;
use Bitrix\Main;
use Bitrix\Main\Engine\Response\DataType\Page;

final class Member extends RestController
{
	private readonly Contract\Repository\NodeMemberRepository $repository;
	private readonly Factory\Item\Member $itemFactory;

	protected function init(): void
	{
		parent::init();
		$this->repository = Container::getNodeMemberRepository();
		$this->itemFactory = new Factory\Item\Member();
	}

	/**
	 * @restMethod humanresources.structure.member.getFields
	 */
	public function getFieldsAction(): array
	{
		return ['MEMBER' => $this->getViewFields()];
	}

	/**
	 * @restMethod humanresources.structure.member.add
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
		catch (DeleteFailedException $exception)
		{
			return $this->responseWithErrors($exception->getErrors());
		}

		if ($item->id === null)
		{
			return $this->responseWithError(new Main\Error('Failed to add member'));
		}

		return $item->id;
	}

	/**
	 * @restMethod humanresources.structure.member.get
	 */
	public function getAction(int $id): ?array
	{
		$existItem = $this->repository->findById($id);
		if ($existItem === null)
		{
			return $this->responseWithError($this->getNodeMemberDontExistError($id));
		}

		return [
			'MEMBER' => $this->get($id),
		];
	}

	/**
	 * @restMethod humanresources.structure.member.list
	 */
	public function listAction(
		Main\UI\PageNavigation $pageNavigation,
		array $select = [],
		array $filter = [],
		array $order = [],
	): Page
	{
		return new Page(
			'MEMBERS',
			$this->getList($select, $filter, $order, $pageNavigation),
			$this->count($filter)
		);
	}

	/**
	 * @restMethod humanresources.structure.member.update
	 */
	public function updateAction(int $id, array $fields): ?bool
	{
		$existItem = $this->repository->findById($id);
		if ($existItem === null)
		{
			return $this->responseWithError($this->getNodeMemberDontExistError($id));
		}

		$validationResult = $this->itemFactory->validateRestFields($fields);
		if (!$validationResult->isSuccess())
		{
			return $this->responseWithErrors($validationResult->getErrors());
		}

		$item = $this->itemFactory->createFromRestFields($fields);
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
	 * @restMethod humanresources.structure.member.delete
	 */
	public function deleteAction(int $id): ?bool
	{
		$nodeMemberItem = $this->repository->findById($id);
		if (!$nodeMemberItem)
		{
			return $this->responseWithError($this->getNodeMemberDontExistError($id));
		}

		try
		{
			$this->repository->remove($nodeMemberItem);
		}
		catch (DeleteFailedException $exception)
		{
			return $this->responseWithErrors($exception->getErrors());
		}

		return true;
	}

	protected function getEntityTable()
	{
		return new Model\NodeMemberTable();
	}

	private function getNodeMemberDontExistError(int $id): Main\Error
	{
		return new Main\Error("Node member with id: ${id} dont exist",'HR_NODE_MEMBER_DONT_EXIST');
	}
}