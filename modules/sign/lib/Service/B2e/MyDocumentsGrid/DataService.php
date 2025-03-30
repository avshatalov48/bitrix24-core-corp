<?php

namespace Bitrix\Sign\Service\B2e\MyDocumentsGrid;

use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Item\Document;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Type\EntityType;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Type\EntityFileCode;
use Bitrix\Sign\Type\MemberStatus;
use Bitrix\Sign\Service\UserService;
use Bitrix\Sign\Type\DocumentStatus;
use Bitrix\Sign\Item\MemberCollection;
use Bitrix\Sign\Item\DocumentCollection;
use Bitrix\Sign\Service\Sign\MemberService;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Type\Document\InitiatedByType;
use Bitrix\Sign\Operation\GetSignedB2eFileUrl;
use Bitrix\Sign\Service\Sign\DocumentService;
use Bitrix\Sign\Item\MyDocumentsGrid\Row;
use Bitrix\Sign\Item\MyDocumentsGrid\File;
use Bitrix\Sign\Item\MyDocumentsGrid\Grid;
use Bitrix\Sign\Item\MyDocumentsGrid\MyDocumentsFilter;
use Bitrix\Sign\Item\MyDocumentsGrid\RowCollection;
use Bitrix\Sign\Type\MyDocumentsGrid\ActorRole;
use Bitrix\Sign\Type\MyDocumentsGrid\FilterStatus;

class DataService
{
	private readonly MemberRepository $memberRepository;
	private readonly DocumentRepository $documentRepository;
	private readonly MemberService $memberService;
	private readonly DocumentService $documentService;
	private readonly ActionStatusService $actionStatusService;
	private readonly UserService $userService;

	public function __construct()
	{
		$this->memberRepository = Container::instance()->getMemberRepository();
		$this->documentRepository = Container::instance()->getDocumentRepository();
		$this->memberService = Container::instance()->getMemberService();
		$this->documentService = Container::instance()->getDocumentService();
		$this->actionStatusService = Container::instance()->getActionStatusService();
		$this->userService = Container::instance()->getUserService();
	}

	public function getGridData(
		int $limit,
		int $offset,
		int $userId,
		?MyDocumentsFilter $filter = null,
	): Grid
	{
		$userIds = [];
		$rows = new RowCollection();
		$members = $this->getMembersForCurrentUser(
			$userId,
			$limit,
			$offset,
			$filter,
		);
		$documents = $this->getDocuments($members);
		$secondSideMembersMap = $this->getSecondSideMembersForEmployeeMap($members, $documents, $userId);

		foreach ($members as $member)
		{
			$myMemberInProcess = $member;
			$document = $documents->getById((int)$member->documentId);
			if (!$document)
			{
				continue;
			}

			if ($this->isSecondSideMemberForEmployee($member, $document, $userId))
			{
				$secondSideMember = $secondSideMembersMap[$document->id] ?? null;
				if ($secondSideMember != null)
				{
					$member = $secondSideMember[0];
				}
			}

			$fileData = null;
			if ($document->isInitiatedByEmployee() && DocumentStatus::isFinalByDocument($document))
			{
				$fileData = $this->getFromEmployeeFileLink(
					$document,
					$myMemberInProcess,
					$member,
				);
			}
			else
			{
				$relevantMember = !$document->isInitiatedByEmployee() || $document->status === DocumentStatus::DONE
					? $member
					: $myMemberInProcess
				;
				$fileData = $this->getFileData($relevantMember);
			}

			$dateSend = $member->dateSend ?? $myMemberInProcess->dateSend;
			$initiatorData = $this->buildMemberData(
				$document,
				$member,
				$userId,
				true,
			);

			$memberData = $this->buildMemberData($document, $member, $userId);
			if ($this->isDocumentStoppedForEmployee($myMemberInProcess, $document))
			{
				$stoppedUser = $this->userService->getUserById($document->stoppedById);
				$memberData = new \Bitrix\Sign\Item\MyDocumentsGrid\Member(
					isCurrentUser: $document->stoppedById === $userId,
					isStopped: true,
					userId: $document->stoppedById,
					fullName: $this->userService->getUserName($stoppedUser),
					icon: $this->userService->getUserAvatar($stoppedUser),
				);
			}

			if (!in_array($memberData->userId, $userIds, true))
			{
				$userIds[] = $memberData->userId;
			}

			if (!in_array($initiatorData->userId, $userIds, true))
			{
				$userIds[] = $initiatorData->userId;
			}

			$isDocumentHasSuccessfulSigners = $document->status === DocumentStatus::STOPPED
				? $this->memberService->isDocumentHasSuccessfulSigners($document->id)
				: null
			;
			$rowDocument = new \Bitrix\Sign\Item\MyDocumentsGrid\Document(
				$document->id,
				$this->documentService->getComposedTitleByDocument($document),
				$document->providerCode,
				$this->getSignDate($myMemberInProcess, $document),
				$dateSend,
				$this->getEditDate($myMemberInProcess),
				$this->getApprovedDate($myMemberInProcess),
				$this->getCancelledDate($myMemberInProcess, $document),
				$document->status,
				$initiatorData,
				$document->initiatedByType,
				$document->stoppedById,
				$isDocumentHasSuccessfulSigners
			);

			$rows->add(
				new Row(
					$myMemberInProcess->id,
					$rowDocument,
					new \Bitrix\Sign\Item\MyDocumentsGrid\MemberCollection(...[$memberData]),
					$this->buildMemberData(
						$document,
						$myMemberInProcess,
						$userId,
					),
					$fileData,
					$this->actionStatusService->getActionStatus(
						$document,
						$myMemberInProcess,
						$userId,
					),
				)
			);
		}

		return new Grid(
			$rows,
			$this->getTotalCountMembers($userId, $filter),
			$userIds,
		);
	}

	private function getFileData(?Member $member): ?File
	{
		if ($member === null)
		{
			return null;
		}

		$entityFileCode = EntityFileCode::SIGNED;

		$operation = new GetSignedB2eFileUrl(
			EntityType::MEMBER,
			$member->id,
			$entityFileCode,
		);

		$result = $operation->launch();
		if (!$result->isSuccess())
		{
			return null;
		}

		$data = [
			'entityFileCode' => $entityFileCode,
			...$result->getData()
		];

		return new File(
			$data['entityFileCode'],
			$data['ext'],
			$data['url'],
		);
	}


	private function buildMemberData(
		Document $document,
		Member $member,
		int $currentUserId,
		bool $isInitiator = false,
	): ?\Bitrix\Sign\Item\MyDocumentsGrid\Member
	{
		$userIdForMember = $this->memberService->getUserIdForMember($member, $document);
		$user = $isInitiator
			? $this->userService->getUserById($document->createdById)
			: $this->userService->getUserById($userIdForMember);
		$isCurrentUser = $userIdForMember === $currentUserId;

		return new \Bitrix\Sign\Item\MyDocumentsGrid\Member(
			isCurrentUser: $isCurrentUser,
			isStopped: $isInitiator ? null : false,
			userId: $isInitiator ? $document->createdById : $userIdForMember,
			fullName: $this->userService->getUserName($user),
			icon: $this->userService->getUserAvatar($user),
			id: $isInitiator ? null : $member->id,
			role: $isInitiator ? 'initiator' : $member->role,
			status: $isInitiator ? null : $member->status,
		);
	}

	private function getMembersForCurrentUser(
		int $userId,
		int $limit = 10,
		int $offset = 10,
		?MyDocumentsFilter $filter = null,
	): MemberCollection
	{
		return $this->memberRepository->listB2eMembersForMyDocumentsGrid(
			$userId,
			$limit,
			$offset,
			$filter,
		);
	}

	public function getTotalCountMembers(
		int $userId,
		?MyDocumentsFilter $filter = null,
	): int
	{
		return $this->memberRepository->getTotalMemberCollectionCountWithNotWaitStatus($userId, $filter);
	}

	private function getDocuments(MemberCollection $members): DocumentCollection
	{
		$documentIds = [];
		foreach ($members as $member)
		{
			$documentIds[$member->documentId] = $member->documentId;
		}

		if (empty($documentIds))
		{
			return new DocumentCollection();
		}

		return $this->documentRepository->listByIds($documentIds);
	}

	/**
	 * @psalm-return array<int, array<int, Member>>
	 */
	private function getSecondSideMembersForEmployeeMap(
		MemberCollection $membersForCurrentUser,
		DocumentCollection $documents,
		int $userId,
	): array
	{
		$memberIds = [];
		$documentIds = [];

		foreach ($membersForCurrentUser as $member)
		{
			$document = $documents->getById((int)$member->documentId);
			if (!$document)
			{
				continue;
			}

			if ($this->isSecondSideMemberForEmployee($member, $document, $userId))
			{
				$documentIds[] = $document->id;
				$memberIds[] = $member->id;
			}
		}

		if ($documentIds === [])
		{
			return [];
		}

		$secondSideMembers = $this->memberRepository->getSecondSideMembersForMyDocumentsGrid(
			$documentIds,
			$memberIds
		);

		$secondSideMembersMap = [];
		foreach ($secondSideMembers as $member)
		{
			$documentId = $member->documentId;
			$secondSideMembersMap[$documentId][] = $member;
		}

		return $secondSideMembersMap;
	}

	public function isSignedExists(int $userId): bool
	{
		return $this->memberRepository->isAnyMyDocumentsGridInSignedStatus($userId);
	}

	public function isInProgressExists(int $userId): bool
	{
		return $this->memberRepository->isAnyMyDocumentsGridInProgressStatus($userId);
	}

	public function getTotalCountNeedAction(int $userId): int
	{
		$filter = new MyDocumentsFilter(
			statuses: [FilterStatus::NEED_ACTION],
		);

		return $this->getTotalCountMembers($userId, $filter);
	}

	public function getCountNeedActionForSentDocumentsByEmployee(int $userId): int
	{
		$filter = new MyDocumentsFilter(
			role: ActorRole::INITIATOR,
			statuses: [FilterStatus::NEED_ACTION],
		);
		return $this->getTotalCountMembers($userId, $filter);
	}

	private function getFromEmployeeFileLink(
		Document $document,
		Member $initiator,
		Member $myMemberInProcess,
	): ?File
	{
		$documentStatusIsFinal = DocumentStatus::isFinalByDocument($document);

		if ($document->initiatedByType == InitiatedByType::EMPLOYEE && $documentStatusIsFinal)
		{
			if ($initiator->role === Role::SIGNER)
			{
				$resultFile = $this->getFileData($this->memberService->getAssignee($document));

				if ($resultFile === null)
				{
					return $this->getFileData($this->memberService->getSigner($document));
				}

				return $resultFile;
			}

			if ($myMemberInProcess->status === MemberStatus::STOPPED && $document->status === DocumentStatus::STOPPED)
			{
				return $this->getFileData($this->memberService->getSigner($document));
			}

			if ($myMemberInProcess->role === Role::ASSIGNEE)
			{
				return null;
			}
		}

		return null;
	}

	private function getEditDate(Member $member): ?DateTime
	{
		if ($member->status === MemberStatus::DONE && $member->role === Role::EDITOR)
		{
			return $member->dateStatusChanged ?? $member->dateSigned;
		}

		return null;
	}

	private function getApprovedDate(Member $member): ?DateTime
	{
		if ($member->status === MemberStatus::DONE && $member->role === Role::REVIEWER)
		{
			return $member->dateStatusChanged ?? $member->dateSigned;
		}

		return null;
	}

	private function getCancelledDate(Member $member, Document $document): ?DateTime
	{
		return match ($member->status)
		{
			MemberStatus::REFUSED, MemberStatus::STOPPED => $member->dateStatusChanged,
			MemberStatus::DONE => $document->isInitiatedByEmployee() ? $this->getDocumentStopDate($document) : null,
			default => $this->getDocumentStopDate($document),
		};
	}

	private function getDocumentStopDate(Document $document): ?DateTime
	{
		return $document->status === DocumentStatus::STOPPED ? $document->dateStatusChanged : null;
	}

	private function getSignDate(Member $member, Document $document): ?DateTime
	{
		return $document->isInitiatedByEmployee() && $member->role === Role::SIGNER && $document->dateSign
			? $document->dateSign
			: $member->dateSigned
			;
	}

	private function isSecondSideMemberForEmployee(
		Member $member,
		Document $document,
		int $userId,
	): bool
	{
		return $this->memberService->getUserIdForMember($member, $document) === $userId
			&& $document->isInitiatedByEmployee()
			&& $member->status !== MemberStatus::STOPPABLE_READY
			&& $member->role === Role::SIGNER;
	}

	private function isDocumentStoppedForEmployee(
		Member $myMemberInProcess,
		Document $document,
	): bool
	{
		return $document->isInitiatedByEmployee()
			&& $document->status === DocumentStatus::STOPPED
			&& $document->stoppedById != null
			&& $myMemberInProcess->role !== Role::REVIEWER;
	}
}