<?php

namespace Bitrix\Sign\Service\Integration\Crm\Kanban\B2e;

use Bitrix\Sign\Access\Permission\PermissionDictionary;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\DocumentCollection;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Item\MemberCollection;
use Bitrix\Sign\Operation\CheckDocumentAccess;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Repository\UserRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\Document\EntityType;
use Bitrix\Sign\Type\DocumentStatus;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Type\MemberStatus;
use Bitrix\Main;

final class EntityService
{
	public function __construct(
		private ?MemberRepository $memberRepository = null,
		private ?DocumentRepository $documentRepository = null,
		private ?UserRepository $userRepository = null
	)
	{
		$this->memberRepository ??= Container::instance()->getMemberRepository();
		$this->documentRepository ??= Container::instance()->getDocumentRepository();
		$this->userRepository ??= Container::instance()->getUserRepository();
	}

	private function isDocumentStatusSigning(Document $document): bool
	{
		return $document->status === DocumentStatus::SIGNING;
	}

	public function isCurrentUserCanCancelDocument(Document $document): bool
	{
		$result = (new CheckDocumentAccess(
			$document,
			PermissionDictionary::SIGN_CRM_SMART_B2E_DOC_WRITE
		))->launch();

		return $result->isSuccess() && $this->isDocumentStatusSigning($document);
	}

	public function getSigningReadyUserListByDocumentId(int $documentId): ?MemberCollection
	{
		return $this->memberRepository?->listB2eSigningByDocumentIdAndStatuses(
			$documentId,
			[
				MemberStatus::READY,
				MemberStatus::STOPPABLE_READY,
			],
		);
	}

	public function getSigningWaitUserListByDocumentId(int $documentId): ?MemberCollection
	{
		$assignee = $this->memberRepository->getAssigneeByDocumentId($documentId);
		if ($assignee?->status === MemberStatus::DONE)
		{
			return new MemberCollection();
		}

		return $this->memberRepository?->listB2eSigningByDocumentIdAndStatuses(
			$documentId,
			[
				MemberStatus::WAIT,
			]
		);
	}

	public function getReviewerOrEditorMemberList(array $documentIds): ?MemberCollection
	{
		return $this->memberRepository?->listByDocumentIdListAndRoles(
			$documentIds,
			[Role::EDITOR, Role::REVIEWER]
		);
	}

	public function getDocumentListByEntityIds(array $entityIds): ?DocumentCollection
	{
		return $this->documentRepository?->listByEntityIdsAndType($entityIds, EntityType::SMART_B2E);
	}

	public function getStoppedUserListByDocumentId(int $documentId): ?MemberCollection
	{
		return $this->memberRepository?->listB2eStoppedByDocumentId($documentId, 4);
	}

	public function findCurrentUserReviewerItemByDocumentId(
		?int $documentId,
		?MemberCollection $memberCollection
	): ?Member {
		return $this->findCurrentUserMemberItemByDocumentId($documentId, Role::REVIEWER, $memberCollection);
	}

	public function findCurrentUserEditorItemByDocumentId(?int $documentId, ?MemberCollection $memberCollection): ?Member
	{
		return $this->findCurrentUserMemberItemByDocumentId($documentId, Role::EDITOR, $memberCollection);
	}

	public function getEditorMemberList(?int $documentId, ?MemberCollection $memberCollection): ?MemberCollection
	{
		return $this->filterMemberListByDocumentIdAndRole($documentId, Role::EDITOR, $memberCollection);
	}

	public function getReviewerMemberList(?int $documentId, ?MemberCollection $memberCollection): ?MemberCollection
	{
		return $this->filterMemberListByDocumentIdAndRole($documentId, Role::REVIEWER, $memberCollection);
	}

	private function filterMemberListByDocumentIdAndRole(
		?int $documentId,
		string $role,
		?MemberCollection $memberCollection
	): ?MemberCollection {
		if ($documentId < 1)
		{
			return null;
		}

		return $memberCollection?->filter(
			fn(Member $member): bool => $member->documentId === $documentId
				&& $member->role === $role
		);
	}

	private function findCurrentUserMemberItemByDocumentId(?int $documentId, string $role, ?MemberCollection $memberCollection): ?Member
	{
		if ($documentId < 1)
		{
			return null;
		}

		if ($memberCollection === null)
		{
			return null;
		}

		$currentUserId = (int)Main\Engine\CurrentUser::get()->getId();
		if ($currentUserId < 1)
		{
			return null;
		}

		return $memberCollection->findFirst(
			fn(Member $member): bool => $member->documentId === $documentId
				&& $member->entityId === $currentUserId
				&& $member->role === $role
		);
	}

	public function getCurrentUserReadyForSignListByDocumentId(array $documentIds): ?MemberCollection
	{
		if (empty($documentIds))
		{
			return null;
		}

		$currentUserId = (int)Main\Engine\CurrentUser::get()->getId();
		if($currentUserId < 1)
		{
			return null;
		}

		return $this->memberRepository?->listB2eMembersWithReadyStatusByDocumentIds($documentIds, $currentUserId);
	}
}
