<?php

namespace Bitrix\Sign\Service;

use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\Result;
use Bitrix\Sign\Config;
use Bitrix\Sign\Item\SignersList;
use Bitrix\Sign\Item\SignersListUser;
use Bitrix\Sign\Item\SignersListUserCollection;
use Bitrix\Sign\Repository\SignersList\SignersListRepository;
use Bitrix\Sign\Repository\SignersList\SignersListUserRepository;

class SignersListService
{
	private readonly SignersListRepository $signersListRepository;
	private readonly SignersListUserRepository $signersListUserRepository;
	private readonly Config\Storage $config;

	public function __construct(
		?SignersListRepository $signersListRepository = null,
		?SignersListUserRepository $signersListUserRepository = null,
		?Config\Storage $config = null,
	)
	{
		$container = Container::instance();
		$this->signersListRepository = $signersListRepository ?? $container->getSignersListRepository();
		$this->signersListUserRepository = $signersListUserRepository ?? $container->getSignersListUserRepository();
		$this->config = $config ?? Config\Storage::instance();
	}

	public function addUsersToList(int $listId, array $userIds, int $createdById, bool $ignoreDuplicates = false): Result
	{
		$dateCreate = new \Bitrix\Sign\Type\DateTime();

		if ($ignoreDuplicates)
		{
			$filter =
				(new ConditionTree())
					->where('LIST_ID', $listId)
					->whereIn('USER_ID', $userIds)
			;
			$existingSigners = $this->signersListUserRepository->list($filter);
			$existingUserIds = array_map(
				static fn(SignersListUser $signer): int => $signer->userId,
				$existingSigners->toArray()
			);
			$userIds = array_diff($userIds, $existingUserIds);
		}

		if (empty($userIds))
		{
			return new Result();
		}

		$signers = new SignersListUserCollection();
		foreach ($userIds as $userId)
		{
			$signers->add(
				new SignersListUser(
					listId: $listId,
					userId: $userId,
					createdById: $createdById,
					dateCreate: $dateCreate,
				)
			);
		}

		$result = $this->signersListUserRepository->add($signers);
		if (!$result->isSuccess())
		{
			return $result;
		}

		return $this->signersListRepository->updateModificationTime([$listId], $createdById, $dateCreate);
	}

	public function deleteUsersFromList(int $listId, array $userIds, int $modifiedById): Result
	{
		$dateModify = new \Bitrix\Sign\Type\DateTime();
		$delResult = $this->signersListUserRepository->deleteSignersFromList($listId, $userIds);
		$updateResult = $this->signersListRepository->updateModificationTime([$listId], $modifiedById, $dateModify);

		return $delResult->addErrors($updateResult->getErrors());
	}

	public function deleteUserFromAllLists(int $userId, int $modifiedById): Result
	{
		$dateModify = new \Bitrix\Sign\Type\DateTime();

		$filter = new ConditionTree();
		$filter->where('USER_ID', $userId);

		$signers = $this->signersListUserRepository->list($filter);
		$listIds = $signers->getListIds();

		$delResult = $this->signersListUserRepository->deleteSignerFromLists($userId, $listIds);
		$updateResult = $this->signersListRepository->updateModificationTime($listIds, $modifiedById, $dateModify);

		return $delResult->addErrors($updateResult->getErrors());
	}

	public function renameList(int $listId, string $title, int $modifiedById): Result
	{
		$signersList = $this->signersListRepository->getById($listId);

		if (!$signersList)
		{
			return (new Result())->addError(
				new \Bitrix\Main\Error('List not found')
			);
		}

		$title = trim($title);

		if ($signersList->title === $title)
		{
			return new Result();
		}

		$signersList->title = $title;
		$signersList->modifiedById = $modifiedById;
		$signersList->dateModify = new \Bitrix\Sign\Type\DateTime();
		return $this->signersListRepository->update($signersList);
	}

	public function createList(string $title, int $createdById): \Bitrix\Main\ORM\Data\AddResult
	{
		$newList = new SignersList(
			title: trim($title),
			createdById: $createdById,
			id: null,
			dateCreate: new \Bitrix\Sign\Type\DateTime(),
		);

		return $this->signersListRepository->add($newList);
	}

	public function getById(int $listId): ?SignersList
	{
		return $this->signersListRepository->getById($listId);
	}

	public function list($limit = 0): \Bitrix\Sign\Item\SignersListCollection
	{
		return $this->listWithFilter(new ConditionTree(), $limit);
	}

	public function listWithFilter(
		ConditionTree $filter,
		int $limit = 0,
		int $offset = 0,
	): \Bitrix\Sign\Item\SignersListCollection
	{
		$filter = $this->prepareListsFilter($filter);
		return $this->signersListRepository->list($filter, $limit, $offset);
	}

	public function listSigners(int $listId): SignersListUserCollection
	{
		return $this->signersListUserRepository->list((new ConditionTree())->where('LIST_ID', $listId));
	}

	public function listRejectedSigners(): SignersListUserCollection
	{
		$listId = $this->config->getSignersListRejectedId();

		if (!$listId)
		{
			return new SignersListUserCollection();
		}

		return $this->listSigners($listId);
	}

	public function listSignersWithFilter(
		int $listId,
		ConditionTree $filter,
		int $limit = 0,
		int $offset = 0,
	): SignersListUserCollection
	{
		$filter->where('LIST_ID', $listId);
		return $this->signersListUserRepository->list($filter, $limit, $offset);
	}

	public function countListsWithFilter(ConditionTree $filter): int
	{
		$filter = $this->prepareListsFilter($filter);
		return $this->signersListRepository->count($filter);
	}

	public function countSignersWithFilter(int $listId, ConditionTree $filter): int
	{
		$filter->where('LIST_ID', $listId);
		return $this->signersListUserRepository->count($filter);
	}

	public function installRejectedList(?int $createdById = null, ?string $title = null): Result
	{
		if ($this->config->getSignersListRejectedId())
		{
			return new Result();
		}

		if ($createdById === null || $title === null)
		{
			return (new Result())->addError(
				new \Bitrix\Main\Error('CreatedById and title are required'),
			);
		}

		$result = $this->createList($title, $createdById);

		if ($result->isSuccess() && is_int($result->getId()))
		{
			$this->config->setSignersListRejectedId($result->getId());
		}

		return $result;
	}

	public function deleteListById(int $listId): Result
	{
		if ($listId === $this->config->getSignersListRejectedId())
		{
			return (new Result())->addError(
				new \Bitrix\Main\Error('You cannot delete the rejected list'),
			);
		}

		return $this->signersListRepository->delete($listId);
	}

	/**
	 * @param int[] $ids
	 */
	public function listByIds(array $ids): \Bitrix\Sign\Item\SignersListCollection
	{
		if (empty($ids))
		{
			return new \Bitrix\Sign\Item\SignersListCollection();
		}

		$filter = (new ConditionTree())->whereIn('ID', $ids);

		return $this->listWithFilter($filter);
	}

	public function search(string $query): \Bitrix\Sign\Item\SignersListCollection
	{
		if (trim($query) === '')
		{
			return new \Bitrix\Sign\Item\SignersListCollection();
		}

		$filter = (new ConditionTree())->whereLike('TITLE', "%$query%");

		return $this->listWithFilter($filter);
	}

	private function prepareListsFilter(?ConditionTree $filter = null): ConditionTree
	{
		$filter = $filter ?? new ConditionTree();

		if ($rejectedList = $this->config->getSignersListRejectedId())
		{
			// exclude special list
			$filter->where('ID', '!=', $rejectedList);
		}

		return $filter;
	}
}