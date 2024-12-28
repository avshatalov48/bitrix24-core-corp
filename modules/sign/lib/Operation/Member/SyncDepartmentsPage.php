<?php

namespace Bitrix\Sign\Operation\Member;

use Bitrix\HumanResources\Contract\Service\NodeMemberService;
use Bitrix\HumanResources\Item\Collection\NodeMemberCollection;
use Bitrix\HumanResources\Item\NodeMember;
use Bitrix\Main\Result;
use Bitrix\Sign\Integration\Bitrix24\B2eTariff;
use Bitrix\Sign\Item\MemberCollection;
use Bitrix\Sign\Repository\MemberNodeRepository;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\MemberService;
use Bitrix\Sign\Type;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;
use Bitrix\Main;
use Bitrix\Sign\Type\Member\Role;

final class SyncDepartmentsPage implements Contract\Operation
{
	private const DEPT_SYNC_PAGE_SIZE = 500;

	private readonly MemberService $memberService;
	private readonly MemberRepository $memberRepository;
	private readonly MemberNodeRepository $memberNodeRepository;

	public function __construct(
		private readonly Item\Document $document,
		private readonly int $party,
		private readonly NodeMemberService $hrNodeMemberService,
	)
	{
		$this->memberService = Container::instance()->getMemberService();
		$this->memberRepository = Container::instance()->getMemberRepository();
		$this->memberNodeRepository = Container::instance()->getMemberNodeRepository();
	}

	public function launch(): Main\Result
	{
		if (!Type\DocumentScenario::isB2EScenario($this->document->scenario))
		{
			return (new Main\Result())->addError(
				new Main\Error('Wrong document scenario'),
			);
		}

		$syncedEmployees = 0;
		while ($syncedEmployees < self::DEPT_SYNC_PAGE_SIZE)
		{
			$syncNode = $this->getDepartmentForSync();

			if ($syncNode === null)
			{
				return (new Main\Result())->setData(['syncFinished' => true]);
			}

			$offset = $syncNode->page * self::DEPT_SYNC_PAGE_SIZE;
			$employees = $this->hrNodeMemberService->getPagedEmployees(
				nodeId: $syncNode->nodeId,
				withAllChildNodes: !$syncNode->isFlat,
				offset: $offset,
				limit: self::DEPT_SYNC_PAGE_SIZE,
			);

			$fetchedEmployeesCount = $employees->count();
			$syncedEmployees += $fetchedEmployeesCount;

			if ($fetchedEmployeesCount === 0)
			{
				$updateResult = $this->memberNodeRepository->updateSyncStatus(
					$syncNode->createUpdated(Type\Hr\NodeSyncStatus::Done, $syncNode->page),
				);

				if (!$updateResult->isSuccess())
				{
					return $updateResult;
				}

				continue;
			}

			// start sync
			$syncNode = $syncNode->createUpdated(Type\Hr\NodeSyncStatus::Sync, $syncNode->page);
			$updateResult = $this->memberNodeRepository->updateSyncStatus($syncNode);

			if (!$updateResult->isSuccess())
			{
				return $updateResult;
			}

			// create a list of uniq userIds obtained from the department hierarchy
			$pageUniqueUserIds = $this->getUniqUserIdsFromNodeMemberCollection($employees);

			// separate into new and existing users
			$existingMembers = $this->memberNodeRepository->getSignerMembersByUsers($this->document->id, $pageUniqueUserIds);
			$usersToMember = (new Item\Hr\UsersToMembersMap())->addCollection($existingMembers);
			$existingUsers = array_keys($usersToMember->toArray());
			$newUniqUsersForDocument = array_diff($pageUniqueUserIds, $existingUsers);

			// HR API returns users along with nested departments, but we create links with the department selected in the UI
			// exclude member-node pairs that already have a mapping with selected department
			$nodeRelations = $this->memberNodeRepository->getRelationsByDocumentIdAndNodeSyncId($this->document->id, $syncNode->id);
			$alreadyMappedUsers = $nodeRelations->getUserIds();
			$usersForMapping = array_diff($pageUniqueUserIds, $alreadyMappedUsers);

			// create new Members
			$addResult = $this->createNewMembers($newUniqUsersForDocument);
			if (!$addResult->isSuccess())
			{
				$this->memberService->cleanByDocumentId($this->document->id);
				return $addResult;
			}

			// fetch id's for created members and feed them to the map
			$newMembers = $this->memberNodeRepository->getSignerMembersByUsers($this->document->id, $newUniqUsersForDocument);
			$usersToMember->addCollection($newMembers);

			// create relations
			$addResult = $this->createMemberNodeRelations($syncNode->id, $usersForMapping, $usersToMember);
			if (!$addResult->isSuccess())
			{
				$this->memberService->cleanByDocumentId($this->document->id);
				return $addResult;
			}

			// update current page
			$syncNode = $syncNode->createUpdated(Type\Hr\NodeSyncStatus::Sync, $syncNode->page + 1);
			$updateResult = $this->memberNodeRepository->updateSyncStatus($syncNode);
			if (!$updateResult->isSuccess())
			{
				$this->memberService->cleanByDocumentId($this->document->id);
				return $updateResult;
			}
		}

		return (new Main\Result)->setData(['syncFinished' => false]);
	}

	private function getDepartmentForSync(): ?Item\Hr\NodeSync
	{
		$current = $this->memberNodeRepository->getNodeForSync(
			$this->document->id,
			Type\Hr\NodeSyncStatus::Sync,
		);

		if ($current)
		{
			return $current;
		}

		$next = $this->memberNodeRepository->getNodeForSync(
			$this->document->id,
			Type\Hr\NodeSyncStatus::Waiting,
		);

		if ($next)
		{
			return $next;
		}

		return null;
	}

	private function checkTariffLimitations(Item\Document $document, MemberCollection $memberCollection): Main\Result
	{
		$result = new Main\Result();
		$currentSignersCount = $this->memberRepository->countMembersByDocumentIdAndRoleAndStatus($document->id, [], Role::SIGNER);
		$uniqSignersCount = $currentSignersCount + $memberCollection->count();
		if (B2eTariff::instance()->isB2eSignersCountRestricted($uniqSignersCount))
		{
			$result->addError(B2eTariff::instance()->getSignersCountAccessError());
		}
		return $result;
	}

	/**
	 * @param int[] $userIds
	 * @param int $documentId
	 * @param int $party
	 *
	 * @return Result
	 */
	private function createNewMembers(array $userIds): Main\Result
	{
		$memberCollection = new MemberCollection();
		foreach ($userIds as $userId)
		{
			$memberCollection->add(
				new \Bitrix\Sign\Item\Member(
					documentId: $this->document->id,
					party: $this->party,
					channelType: Type\Member\ChannelType::IDLE,
					channelValue: 'stub@at.com',
					entityType: Type\Member\EntityType::USER,
					entityId: $userId,
					role: Role::SIGNER,
				),
			);
		}

		$tariffRestrictionCheckResult = $this->checkTariffLimitations($this->document, $memberCollection);
		if (!$tariffRestrictionCheckResult->isSuccess())
		{
			$this->memberService->cleanByDocumentId($this->document->id);
			return $tariffRestrictionCheckResult;
		}

		Container::instance()
			 ->getHcmLinkService()
			 ->fillOneLinkedMembersWithEmployeeId(
				 $this->document,
				 $memberCollection,
				 $this->document->representativeId,
			 )
		;

		return $this->memberRepository->addMany($memberCollection);
	}

	/**
	 * @param int $nodeId
	 * @param int[] $userIds
	 * @param Item\Hr\UsersToMembersMap $userMember
	 *
	 * @return Main\Result
	 */
	private function createMemberNodeRelations(int $nodeSyncId, array $userIds, Item\Hr\UsersToMembersMap $userMember): Main\Result
	{
		// prepare relations for department members
		$memberRelations = new Item\Hr\MemberNodeCollection();
		foreach ($userIds as $userId)
		{
			$memberId = $userMember->getMemberId($userId);

			if ($memberId === null)
			{
				return (new Main\Result())->addError(
					new Main\Error('Department synchronization error: member not found by userId'),
				);
			}

			$memberRelations->add(
				new Item\Hr\MemberNode(
					documentId: $this->document->id,
					memberId: $memberId,
					nodeSyncId: $nodeSyncId,
					userId: $userId,
				),
			);
		}

		// create relations
		return $this->memberNodeRepository->addRelationMultiple($memberRelations);
	}

	/**
	 * @return int[]
	 */
	private function getUniqUserIdsFromNodeMemberCollection(NodeMemberCollection $employees): array
	{
		$pageUniqueUserIds = [];
		/** @var NodeMember $employee */
		foreach ($employees->getIterator() as $employee)
		{
			$pageUniqueUserIds[$employee->entityId] = true;
		}
		return array_keys($pageUniqueUserIds);
	}
}
