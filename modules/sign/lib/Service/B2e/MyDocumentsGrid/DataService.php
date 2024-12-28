<?php

namespace Bitrix\Sign\Service\B2e\MyDocumentsGrid;

use Bitrix\Sign\Type\MyDocumentsGrid\ActorRole;
use Bitrix\Sign\Type\MyDocumentsGrid\FilterStatus;
use Bitrix\Main\Type\DateTime;
use CFile;
use CSite;
use CUser;
use Bitrix\Main\UserTable;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Type\EntityType;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Type\EntityFileCode;
use Bitrix\Sign\Type\MemberStatus;
use Bitrix\Sign\Type\DocumentStatus;
use Bitrix\Sign\Item\MemberCollection;
use Bitrix\Sign\Item\DocumentCollection;
use Bitrix\Sign\Service\Sign\MemberService;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Type\MyDocumentsGrid\Action;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Type\Document\InitiatedByType;
use Bitrix\Sign\Operation\GetSignedB2eFileUrl;
use Bitrix\Sign\Service\Sign\DocumentService;
use Bitrix\Sign\Item\MyDocumentsGrid\MyDocumentsFilter;

class DataService
{
	private MemberRepository $memberRepository;
	private DocumentRepository $documentRepository;
	private MemberService $memberService;
	private DocumentService $documentService;

	public function __construct()
	{
		$this->memberRepository = Container::instance()->getMemberRepository();
		$this->documentRepository = Container::instance()->getDocumentRepository();
		$this->memberService = Container::instance()->getMemberService();
		$this->documentService = Container::instance()->getDocumentService();
	}

	public function getGridData(
		int $limit,
		int $offset,
		int $userId,
		?MyDocumentsFilter $filter = null,
	): array
	{
		$members = $this->getCurrentMemberCollection($userId, $limit, $offset, $filter);
		$documents = $this->getDocuments($members);

		$rows = [];
		$userIds = [];
		foreach ($members as $member)
		{
			$myMemberInProcess = $member;
			$document = $documents->getById((int)$member->documentId);
			if (!$document)
			{
				continue;
			}

			$userIdForMember = $this->memberService->getUserIdForMember($member, $document);

			if (
				$userIdForMember === $userId
				&& $document->initiatedByType === InitiatedByType::EMPLOYEE
				&& $member->status !== MemberStatus::STOPPABLE_READY
				&& $myMemberInProcess->role === Role::SIGNER
			)
			{

				$secondSideMember = $this->getSecondSideMember($userId, $document->id, $member->id);
				if ($secondSideMember != null)
				{
					$member = $secondSideMember;
				}
			}

			$fromEmployeeResultFileLink = $this->getFromEmployeeFileLink(
				$document,
				$myMemberInProcess,
				$member,
			);
			$memberData = $this->getMemberData($member, $document, $userId);

			$fileData = $this->getFileData(
				$document->initiatedByType !== InitiatedByType::EMPLOYEE || $document->status === DocumentStatus::DONE
					? $member
					: $myMemberInProcess,
				EntityFileCode::SIGNED
			);

			$fileData = is_array($fileData) ? $fileData : [];
			$fileData = $document->initiatedByType === InitiatedByType::EMPLOYEE && in_array($document->status, DocumentStatus::getFinalStatuses())
				? $fromEmployeeResultFileLink
				: $fileData;

			$dateSend = $member->dateSend ?? $myMemberInProcess->dateSend;
			$initiatorData = $this->getInitiatorDataByDocument($document, $userId, $member);


			if (
				$document->initiatedByType === InitiatedByType::EMPLOYEE
				&& $document->status === DocumentStatus::STOPPED
				&& $document->stoppedById != null
				&& $myMemberInProcess->role !== Role::REVIEWER
			)
			{
				$memberData = [
					'role' => null,
					'status' => null,
					'memberId' => null,
					'isCurrentUser' => $document->stoppedById === $userId,
					'isStopped' => true,
					...$this->getUserData($document->stoppedById),
				];

			}

			if (!in_array($memberData['id'], $userIds))
			{
				$userIds[] = $memberData['id'];
			}

			$rows[] = [
				'id' => $myMemberInProcess->id,
				'document' => [
					'id' => $document->id,
					'title' => $this->documentService->getComposedTitleByDocument($document),
					'providerCode' => $document->providerCode,
					'signDate' => $this->getSignDate($myMemberInProcess, $document),
					'sendDate' => $dateSend,
					'editDate' => $this->getEditDate($myMemberInProcess),
					'approvedDate' => $this->getApprovedDate($myMemberInProcess),
					'cancelledDate' => $this->getCancelledDate($myMemberInProcess, $document),
					'status' => $document->status,
					'initiator' => $initiatorData,
					'initiatedByType' => $document->initiatedByType,
					'stoppedById' => $document->stoppedById,
					'cancellationInitiatorId' =>  $this->memberRepository->getCancellationInitiatorIdByDocumentId($document->id),
				],
				'members' => [
					$memberData,
				],
				'myMemberInProcess' => $this->getMemberData($myMemberInProcess, $document, $userId),
				'file' => [
					...$fileData,
				],
				'action' => [
					'status' => $this->getActionStatus($document, $myMemberInProcess, $userId),
				],
			];
		}

		return [
			'rows' => $rows,
			'totalCountMembers' => $this->getTotalCountMembers($userId, $filter),
			'userIds' => $userIds,
		];
	}

	private function getFileData(
		?Member $member,
		string $entityFileCode,
	): ?array
	{
		if ($member === null)
		{
			return [];
		}

		$operation = new GetSignedB2eFileUrl(
			EntityType::MEMBER,
			$member->id,
			$entityFileCode
		);

		$result = $operation->launch();
		if (!$result->isSuccess())
		{
			return null;
		}

		return $result
			->setData([
				'entityFileCode' => (int)$entityFileCode,
				...$result->getData()
			])
			->getData();
	}


	private function getMemberData(
		Member $member,
		Document $document,
		int $currentUserId
	): array
	{
		$userIdForMember = $this->memberService->getUserIdForMember($member, $document);
		$isCurrentUser = $userIdForMember === $currentUserId;

		return [
			'role' => $member->role,
			'status' => $member->status,
			'memberId' => (int)$member->id,
			'isCurrentUser' => $isCurrentUser,
			'isStopped' => false,
			...$this->getUserData($userIdForMember),
		];
	}

	private function getInitiatorDataByDocument(
		Document $document,
		int $currentUserId,
		Member $member,
	): array
	{
		$userData = $this->getUserData($document->createdById);

		$userIdForMember = $this->memberService->getUserIdForMember($member, $document);
		$isCurrentUser = $userIdForMember === $currentUserId;

		return [
			'role' => 'initiator',
			'status' => null,
			'memberId' => null,
			'isCurrentUser' => $isCurrentUser,
			...$userData,
		];
	}

	private function getCurrentMemberCollection(
		int $userId,
		int $limit = 10,
		int $offset = 10,
		?MyDocumentsFilter $filter = null,
	): MemberCollection
	{
		return $this->memberRepository->listB2eMembersWithNotWaitStatus($userId, $limit, $offset, $filter);
	}

	private function getUserData(int $userId): array
	{
		static $userData = [];
		if (isset($userData[$userId]))
		{
			return $userData[$userId];
		}

		$userNameTemplate = empty($this->arParams['USER_NAME_TEMPLATE'])
			? CSite::GetNameFormat(false)
			: str_replace(["#NOBR#", "#/NOBR#"], ["", ""], $this->arParams["USER_NAME_TEMPLATE"])
		;

		$userTableQueryResult = UserTable::getRowById($userId);
		$userFullName = $userTableQueryResult === null
			? ''
			: CUser::FormatName(
				$userNameTemplate,
				[
					'LOGIN' => $userTableQueryResult['LOGIN'],
					'NAME' => $userTableQueryResult['NAME'],
					'LAST_NAME' => $userTableQueryResult['LAST_NAME'],
					'SECOND_NAME' => $userTableQueryResult['SECOND_NAME'],
				],
				true,
				false,
			);

		$userIconFileTmp = $userTableQueryResult === null
			? null
			: CFile::ResizeImageGet(
				$userTableQueryResult['PERSONAL_PHOTO'],
				15,
				BX_RESIZE_IMAGE_EXACT,
				false,
				false,
				true,
			);

		$userIcon = ($userIconFileTmp && isset($userIconFileTmp['src'])) ? $userIconFileTmp['src'] : null;

		$userData[$userId] = [
			'id' => $userId,
			'fullName' => $userFullName,
			'icon' => $userIcon,
		];

		return $userData[$userId];
	}

	private function getActionStatus(
		Document $document,
		Member $member,
		int $userId,
	): ?Action
	{
		if ($document->status === DocumentStatus::DONE && $member->status !== MemberStatus::READY)
		{
			if ($document->initiatedByType == InitiatedByType::COMPANY && $member->role !== Role::SIGNER)
			{
				return null;
			}

			return Action::DOWNLOAD;
		}

		if ($document->initiatedByType === InitiatedByType::EMPLOYEE)
		{
			$userIdForMember = $this->memberService->getUserIdForMember($member, $document);

			if ($userIdForMember !== $userId || $member->status === MemberStatus::DONE)
			{
				if ($document->status === DocumentStatus::STOPPED)
				{
					return Action::DOWNLOAD;
				}
				return Action::VIEW;
			}

			if ($member->status === MemberStatus::REFUSED || $member->status === MemberStatus::STOPPED && $member->role === Role::SIGNER)
			{
				return Action::DOWNLOAD;
			}

			if ($member->status === MemberStatus::STOPPED || $document->status == DocumentStatus::STOPPED)
			{
				return null;
			}

			switch ($member->role)
			{
				case Role::REVIEWER:
					return Action::APPROVE;
				case Role::SIGNER:
				case Role::ASSIGNEE:
					return Action::SIGN;
			}
		}

		if ($document->initiatedByType === InitiatedByType::COMPANY)
		{
			if ($member->role === Role::SIGNER && $member->status === MemberStatus::DONE)
			{
				return Action::DOWNLOAD;
			}

			if (
				$member->status === MemberStatus::REFUSED
				||$member->status === MemberStatus::STOPPED
				|| $member->status === MemberStatus::DONE
			)
			{
				return null;
			}

			switch ($member->role)
			{
				case Role::REVIEWER:
					return Action::APPROVE;
				case Role::EDITOR:
					return Action::EDIT;
				case Role::SIGNER:
				case Role::ASSIGNEE:
					return Action::SIGN;
			}
		}

		return null;
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

	private function getSecondSideMember(int $userId, int $documentId, int $memberId): ?Member
	{
		return $this->memberRepository
			->getSecondSideMemberForEmployee(
				$userId,
				$documentId,
				1,
				0,
				$memberId,
			)
			->getFirst()
			;
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
	): array
	{
		$documentStatusIsFinal = in_array($document->status, DocumentStatus::getFinalStatuses());

		if ($document->initiatedByType == InitiatedByType::EMPLOYEE && $documentStatusIsFinal)
		{
			if ($initiator->role === Role::SIGNER)
			{
				$resultFile = $this->getFileData(
					$this->memberService->getAssignee($document),
					EntityFileCode::SIGNED,
				) ?? [];

				if ($resultFile === [])
				{
					return $this->getFileData(
						$this->memberService->getSigner($document),
						EntityFileCode::SIGNED,
					) ?? [];
				}

				return $resultFile;
			}

			if ($myMemberInProcess->status === MemberStatus::STOPPED && $document->status === DocumentStatus::STOPPED)
			{
				return $this->getFileData(
					$this->memberService->getSigner($document),
					EntityFileCode::SIGNED,
				) ?? [];
			}

			if ($myMemberInProcess->role === Role::ASSIGNEE)
			{
				return [];
			}
		}

		return [];
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
		return $document->isInitiatedByEmployee() && $member->role === Role::SIGNER	&& $document->dateSign
			? $document->dateSign
			: $member->dateSigned
			;
	}

}