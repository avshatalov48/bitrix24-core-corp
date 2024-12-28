<?php

namespace Bitrix\Sign\Service\B2e\MyDocumentsGrid;

use Bitrix\Main;
use Bitrix\Sign\Item;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Result\Result;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\PullService;
use Bitrix\Sign\Service\Sign\MemberService;
use Bitrix\Sign\Type\Document\InitiatedByType;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Type\MemberStatus;

class EventService
{
	private readonly PullService $pullService;
	private readonly MemberService $memberService;
	private readonly MemberRepository $memberRepository;

	public function __construct()
	{
		$this->pullService = Container::instance()->getPullService();
		$this->memberService = Container::instance()->getMemberService();
		$this->memberRepository = Container::instance()->getMemberRepository();
	}

	public function onDocumentStop(Item\Document $document, ?Item\Member $stopInitiatorMember): Main\Result
	{
		if ($document->id === null)
		{
			return new Result();
		}

		$userIds = $this->getUserIdsForGridMemberAndInitiatedEmployee($stopInitiatorMember, $document);
		if ($stopInitiatorMember === null)
		{
			$readyToSigningMembers = $this->memberRepository->listByDocumentIdWithStatuses(
				$document->id,
				MemberStatus::getReadyForSigning()
			);
			foreach ($readyToSigningMembers as $member)
			{
				$userIds[] = $this->memberService->getUserIdForMember($member);
			}
		}
		$userIds = array_unique($userIds);

		$isSuccess = $this->pullService->sendUpdateMyDocumentGrid($userIds);
		if (!$isSuccess)
		{
			return Result::createByErrorMessage('Error sending event to users');
		}

		return new Result();
	}

	public function onMemberStatusChanged(Item\Document $document, Item\Member $member): Main\Result
	{
		return $this->updateGridToMemberAndInitiatedEmployee($document, $member);
	}

	public function onMemberResultFileSave(Item\Document $document, Item\Member $member): Main\Result
	{
		return $this->updateGridToMemberAndInitiatedEmployee($document, $member);
	}

	private function updateGridToMemberAndInitiatedEmployee(Item\Document $document, ?Item\Member $member): Main\Result
	{
		$userIds = $this->getUserIdsForGridMemberAndInitiatedEmployee($member, $document);
		if (empty($userIds))
		{
			return new Result();
		}

		$isSuccess = $this->pullService->sendUpdateMyDocumentGrid($userIds);
		if (!$isSuccess)
		{
			return Result::createByErrorMessage('Error sending event to users');
		}

		return new Result();
	}

	private function getFirstSignerUserId(
		Item\Document $document,
		?Item\Member $member,
	): ?int
	{
		if ($member?->role === Role::SIGNER)
		{
			return $this->memberService->getUserIdForMember($member);
		}

		$signer = $this->memberRepository->getByDocumentIdWithRole($document->id, Role::SIGNER);
		if ($signer !== null)
		{
			return $this->memberService->getUserIdForMember($signer);
		}

		return null;
	}

	/**
	 * @return list<int>
	 */
	private function getUserIdsForGridMemberAndInitiatedEmployee(?Item\Member $member, Item\Document $document): array
	{
		$userIds = [];
		if ($member !== null)
		{
			$memberUserId = $this->memberService->getUserIdForMember($member);
			if ($memberUserId !== null)
			{
				$userIds[] = $memberUserId;
			}
		}

		if ($document->initiatedByType === InitiatedByType::EMPLOYEE)
		{
			$userIds[] = $this->getFirstSignerUserId($document, $member);
		}

		return array_unique(array_filter($userIds));
	}
}