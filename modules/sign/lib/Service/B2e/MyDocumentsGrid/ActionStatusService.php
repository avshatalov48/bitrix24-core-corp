<?php

namespace Bitrix\Sign\Service\B2e\MyDocumentsGrid;

use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\MemberService;
use Bitrix\Sign\Type\Document\InitiatedByType;
use Bitrix\Sign\Type\DocumentStatus;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Type\MemberStatus;
use Bitrix\Sign\Type\MyDocumentsGrid\Action;

class ActionStatusService
{
	private readonly MemberService $memberService;

	public function __construct()
	{
		$this->memberService = Container::instance()->getMemberService();
	}

	public function getActionStatus(
		Document $document,
		Member $member,
		int $currentUserId,
	): ?Action
	{
		if (!$this->isDocumentDoneAndMemberNotReady($document, $member))
		{
			return match ($document->initiatedByType)
			{
				InitiatedByType::EMPLOYEE => $this->getActionForEmployeeInitiated($document, $member, $currentUserId),
				InitiatedByType::COMPANY => $this->getActionForMemberInitiatedByCompany($member),
			};
		}

		if ($this->isNotSignerAndFromCompany($document, $member))
		{
			return null;
		}

		return Action::DOWNLOAD;
	}

	private function isNotSignerAndFromCompany(
		Document $document,
		Member $member,
	): bool
	{
		return $document->initiatedByType === InitiatedByType::COMPANY && $member->role !== Role::SIGNER;
	}

	private function isDocumentDoneAndMemberNotReady(
		Document $document,
		Member $member,
	): bool
	{
		return $document->status === DocumentStatus::DONE && $member->status !== MemberStatus::READY;
	}

	private function getActionForEmployeeInitiated(
		Document $document,
		Member $member,
		int $currentUserId,
	): ?Action
	{
		$userIdForMember = $this->memberService->getUserIdForMember($member, $document);

		if ($userIdForMember !== $currentUserId || $member->status === MemberStatus::DONE)
		{
			return $document->status === DocumentStatus::STOPPED ? Action::DOWNLOAD : Action::VIEW;
		}

		if ($this->isSignerRefusedOrStopped($member))
		{
			return Action::DOWNLOAD;
		}

		if ($this->isDocumentOrMemberStopped($member, $document))
		{
			return null;
		}

		return $this->getActionByRole($member->role);
	}

	private function isSignerRefusedOrStopped(Member $member): bool
	{
		return $member->status === MemberStatus::REFUSED || $member->status === MemberStatus::STOPPED
			&& $member->role === Role::SIGNER;
	}

	private function isDocumentOrMemberStopped(
		Member $member,
		Document $document,
	): bool
	{
		return $member->status === MemberStatus::STOPPED || $document->status === DocumentStatus::STOPPED;
	}

	private function getActionForMemberInitiatedByCompany(Member $member): ?Action
	{
		if ($member->role === Role::SIGNER && $member->status === MemberStatus::DONE)
		{
			return Action::DOWNLOAD;
		}

		if (MemberStatus::isFinishForSigning($member->status))
		{
			return null;
		}

		return $this->getActionByRole($member->role);
	}

	private function getActionByRole(string $role): ?Action
	{
		return match ($role)
		{
			Role::REVIEWER => Action::APPROVE,
			Role::EDITOR => Action::EDIT,
			Role::SIGNER, Role::ASSIGNEE => Action::SIGN,
			default => null,
		};
	}
}