<?php

namespace Bitrix\Sign\Operation\Member;

use Bitrix\Main\SystemException;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Operation\Member\Validation\ValidateRequiredFields;
use Bitrix\Sign\Result\Operation\Member\DepartmentSyncMembersResult;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\MemberService;
use Bitrix\Sign\Service\SignersListService;
use Bitrix\Sign\Type\Hr\EntitySelector;
use Bitrix\Sign\Item\Hr\EntitySelector\Entity;
use Bitrix\Sign\Item\Hr\EntitySelector\EntityCollection;
use Bitrix\Sign\Item\MemberCollection;
use Bitrix\Sign\Type\Member\EntityType;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Item\Member\SelectorEntityCollection;

/**
 * @see SyncDepartmentsPage
 */
class GetMembersFromUserPartyEntities implements Operation
{
	private readonly MemberService $memberService;
	private readonly SignersListService $signersListService;

	public function __construct(
		private readonly SelectorEntityCollection $entities,
		private readonly bool $excludeRejectedSigners = true,
		MemberService $memberService = null,
		SignersListService $signersListService = null,
	)
	{
		$this->memberService = $memberService ?? Container::instance()->getMemberService();
		$this->signersListService = $signersListService ?? Container::instance()->getSignersListService();
	}

	/**
	 * @throws SystemException
	 */
	public function launch(): DepartmentSyncMembersResult
	{
		$assigneeEntityType = '';
		$memberCollection = new MemberCollection();
		$departmentEntities = new EntityCollection();
		foreach ($this->entities as $member)
		{
			$entityType = EntitySelector\EntityType::fromEntityIdAndType(
				$member->entityId,
				$member->entityType,
			);

			if ($entityType->isDepartment())
			{
				$departmentEntities->add(Entity::createFromStrings(
					entityId: $member->entityId,
					entityType: $member->entityType,
				));
			}

			$role = $member->role ?? \Bitrix\Sign\Compatibility\Role::createByParty($member->party);

			if ($role === null)
			{
				throw new SystemException('Role must be defined');
			}

			if ($role === Role::ASSIGNEE)
			{
				$assigneeEntityType = $member->entityType;
			}

			$memberCollection->add($this->createUserPartyMember(
				$member->party,
				$entityType->getMemberEntityType($member->entityType),
				(int)$member->entityId,
				$role,
			));
		}

		// prepare signers from document entities and lists
		$signersFromDocumentEntities = $this->getSignersFromDocumentEntities($memberCollection);
		$signersFromLists = $this->getSignersFromLists($memberCollection);
		$memberCollection = $memberCollection->filterExcludeEntityTypes(EntityType::DOCUMENT, EntityType::SIGNERS_LIST);
		$memberCollection = $this->addUniqueSignersToCollection($memberCollection, $signersFromLists, $signersFromDocumentEntities);

		if ($this->excludeRejectedSigners)
		{
			$memberCollection = $this->excludeRejectedSigners($memberCollection);
		}

		return new DepartmentSyncMembersResult(
			members: $memberCollection,
			departments: $departmentEntities,
			assigneeEntityType: $assigneeEntityType,
		);
	}

	private function excludeRejectedSigners(MemberCollection $collection): MemberCollection
	{
		$rejectedMembers = $this->signersListService->listRejectedSigners();
		$newCollection = new MemberCollection();

		foreach ($collection as $member)
		{
			if (
				$member->entityType === EntityType::USER
				&& $member->role === Role::SIGNER
				&& in_array($member->entityId, $rejectedMembers->getUserIds(), true)
			)
			{
				continue;
			}

			$newCollection->add($member);
		}

		return $newCollection;
	}

	/**
	 * @throws SystemException
	 */
	private function getSignersFromDocumentEntities(MemberCollection $memberCollection): MemberCollection
	{
		$newCollection = new MemberCollection();

		$documentMembers = $memberCollection->filterByEntityTypes(EntityType::DOCUMENT);
		foreach ($documentMembers as $member)
		{
			if ($member->entityId === null)
			{
				throw new SystemException('Signer member is invalid: entityId is null');
			}

			$signersFromDocument = $this->memberService->listByDocumentId($member->entityId)->filterByRole(Role::SIGNER);
			$signersFromDocument = $this->memberService->filterFiredSigners($signersFromDocument);
			foreach ($signersFromDocument as $signer)
			{

				$role = $signer->role ?? \Bitrix\Sign\Compatibility\Role::createByParty($member->party);

				if ($role===null)
				{
					throw new SystemException('Role must be defined');
				}

				if (in_array(null, [$signer->party, $signer->entityId, $signer->entityType], true))
				{
					throw new SystemException('Signer member is invalid: missing required fields');
				}

				$newCollection->add($this->createUserPartyMember(
					$signer->party,
					$signer->entityType,
					$signer->entityId,
					$role,
				));
			}
		}

		return $newCollection;
	}

	/**
	 * @throws SystemException
	 */
	private function getSignersFromLists(MemberCollection $memberCollection): MemberCollection
	{
		$newCollection = new MemberCollection();

		$lists = $memberCollection->filterByEntityTypes(EntityType::SIGNERS_LIST);
		foreach ($lists as $member)
		{
			if ($member->entityId === null)
			{
				throw new SystemException('Signer member is invalid: entityId is null');
			}

			$listUsers = $this->signersListService->listSigners($member->entityId);

			foreach ($listUsers as $user)
			{
				$role = $member->role ?? \Bitrix\Sign\Compatibility\Role::createByParty($member->party);

				if ($role === null)
				{
					throw new SystemException('Role must be defined');
				}

				if ($member->party === null)
				{
					throw new SystemException('Signer member is invalid: party is null');
				}

				$newCollection->add($this->createUserPartyMember(
					$member->party,
					EntityType::USER,
					$user->userId,
					$role,
				));
			}
		}

		return $newCollection;
	}

	private function addUniqueSignersToCollection(MemberCollection $memberCollection, MemberCollection ...$collections): MemberCollection
	{
		$signerUserIds = $this->memberService->getUserIdsForMembers($memberCollection->filterByRole(Role::SIGNER));

		foreach ($collections as $collection)
		{
			foreach ($collection as $signer)
			{
				if (in_array($signer->entityId, $signerUserIds, true))
				{
					continue;
				}

				$signerUserIds[] = $signer->entityId;
				$memberCollection->add($signer);
			}
		}

		return $memberCollection;
	}

	private function createUserPartyMember(
		int $party,
		string $entityType,
		int $entityId,
		string $role,
	): Member
	{
		return new Member(
			party: $party,
			entityType: $entityType,
			entityId: $entityId,
			role: $role,
		);
	}
}