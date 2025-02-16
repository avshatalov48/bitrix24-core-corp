<?php

namespace Bitrix\Sign\Service;

use Bitrix\Main\Error;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\Result;
use Bitrix\Sign\Config;
use Bitrix\Sign\Contract\Chat\Message;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Service\Integration\Imbot\HrBot;
use Bitrix\Sign\Service\Sign\UrlGeneratorService;
use Bitrix\Sign\Type;
use Bitrix\Sign\Service\Integration\Im\ImService;
use Bitrix\Sign\Item\Integration\Im;
use Bitrix\Sign\Service\Sign\MemberService;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Type\ProviderCode;

class HrBotMessageService
{
	private ImService $imService;
	private MemberService $memberService;
	private MemberRepository $memberRepository;
	private Config\Storage $config;
	private UrlGeneratorService $urlGenerator;
	private UserService $userService;

	public function __construct(
		?ImService $imService = null,
		?MemberService $memberService = null,
		?MemberRepository $memberRepository = null,
		?Config\Storage $config = null,
		?UrlGeneratorService $urlGenerator = null,
		?UserService $userService = null,
	)
	{
		$this->imService = $imService ?? Container::instance()->getImService();
		$this->memberService = $memberService ?? Container::instance()->getMemberService();
		$this->memberRepository = $memberRepository ?? Container::instance()->getMemberRepository();
		$this->urlGenerator = $urlGenerator ?? Container::instance()->getUrlGeneratorService();
		$this->userService = $userService ?? Container::instance()->getUserService();
		$this->config = $config ?? Config\Storage::instance();
	}

	/**
	 * @throws ObjectNotFoundException
	 */
	public function sendInviteMessage(Document $document, Member $member, string $providerCode): Result
	{
		$message = $this->isByEmployee($document)
			? $this->createByEmployeeInviteMessage($document, $member)
			: $this->createByCompanyInviteMessage($document, $member, $providerCode)
		;

		if ($message !== null)
		{
			$message->setLang($this->userService->getUserLanguage($message->getUserTo()));
			return $this->imService->sendMessage($message);
		}

		return new Result();
	}

	public function handleDocumentStatusChangedMessage(Document $document, string $newStatus, ?Member $initiatorMember = null): Result
	{
		if ($this->isByEmployee($document))
		{
			switch ($newStatus)
			{
				case Type\DocumentStatus::STOPPED:
					return $this->handleByEmployeeDocumentStoppedStatus($document, $initiatorMember);
				case Type\DocumentStatus::DONE:
					$result = $this->byEmployeeSendDoneMessageToEmployee($document);
					$result->addErrors(
						$this->byEmployeeSendDoneMessageToCompany($document)->getErrors()
					);
					return $result;
			}

			return new Result();
		}

		switch ($newStatus)
		{
			case Type\DocumentStatus::STOPPED:
				return $this->handleByCompanyDocumentStoppedStatus($document, $initiatorMember);

			case Type\DocumentStatus::DONE:
				$signedDone = $this->memberService->countSuccessfulSigners($document->id);
				if ($signedDone)
				{
					$userFrom = $this->getBotUserId() ?? $document->representativeId;
					$userTo = $document->createdById;
					return $this->sendDoneMessageToCompany($userFrom, $userTo, $document);
				}
				return new Result();
		}

		return new Result();
	}

	public function handleMemberStatusChangedMessage(Document $document, Member $member): Result
	{
		if ($this->isByEmployee($document))
		{
			switch (true)
			{
				case $member->role === Role::SIGNER && $member->status === Type\MemberStatus::DONE:
					$userIdFrom = $this->getBotUserId() ?? $document->representativeId;
					$userIdTo = $this->memberService->getUserIdForMember($member);
					return $this->byEmployeeSendEmployeeSignedMessageToEmployee($userIdFrom, $userIdTo, $document, $member);
			}

			return new Result();
		}

		$memberUserId = $this->memberService->getUserIdForMember($member);

		switch ($member->role)
		{
			case Type\Member\Role::SIGNER:
				return match ($member->status) {
					Type\MemberStatus::STOPPED => $this->handleEmployeeStoppedStatus($document, $member),
					Type\MemberStatus::REFUSED => $memberUserId !== $document->createdById
						? $this->sendRefusedMessage($memberUserId, $document->createdById, $document)
						: new Result()
					,
					Type\MemberStatus::DONE => $this->sendDoneMessageToEmployee($this->getBotUserId() ?? $document->createdById, $member, $document),
					default => new Result(),
				};
		}

		return new Result();
	}

	public function repeatSigningOnErrors(Document $document, Member $assignee): Result
	{
		$result = new Result();

		$assigneeUserId = $this->memberService->getUserIdForMember($assignee);
		$botUserId = $this->getBotUserId();

		// invite assignee to repeat signing
		$userIdFrom = $botUserId ?? $document->createdById;
		$result->addErrors(
			($this->sendErrorMessageToAssignee($userIdFrom, $assigneeUserId, $assignee, $document))->getErrors()
		);

		// notify initiator if it is another user
		if ($document->createdById !== $assigneeUserId)
		{
			$userIdFrom = $botUserId ?? $assigneeUserId;
			$result->addErrors(
				($this->sendErrorMessageToInitiator($userIdFrom, $document->createdById, $assignee, $document))->getErrors()
			);
		}

		return $result;
	}

	/**
	 * @throws ObjectNotFoundException
	 */
	private function createByCompanyInviteMessage(Document $document, Member $member, string $providerCode): ?Message
	{
		$userIdFrom = $this->getBotUserId() ?? $document->createdById;
		$userIdTo = $this->memberService->getUserIdForMember($member);
		$signingLink = $this->urlGenerator->makeSigningUrl($member);

		if (!$userIdTo || !$userIdFrom)
		{
			throw new ObjectNotFoundException('hrbot: no such user');
		}

		$initiatorUserId = $document->createdById;
		$initiatorName = $this->memberService->getUserRepresentedName($initiatorUserId);

		return match ($member->role)
		{
			Role::ASSIGNEE => $userIdTo !== $document->createdById
				? new Im\Messages\InviteToSign\CompanyWithInitiator($userIdFrom, $userIdTo, $initiatorUserId, $initiatorName, $document, $signingLink)
				: new Im\Messages\InviteToSign\Company($userIdFrom, $userIdTo, $document, $signingLink)
		,
			Role::REVIEWER => $userIdTo !== $document->createdById
				? new Im\Messages\InviteToSign\ReviewerWithInitiator($userIdFrom, $userIdTo, $initiatorUserId, $initiatorName, $document, $signingLink)
				: new Im\Messages\InviteToSign\Reviewer($userIdFrom, $userIdTo, $document, $signingLink)
		,
			Role::EDITOR => $userIdTo !== $document->createdById
				? new Im\Messages\InviteToSign\EditorWithInitiator($userIdFrom, $userIdTo, $initiatorUserId, $initiatorName, $document, $signingLink)
				: new Im\Messages\InviteToSign\Editor($userIdFrom, $userIdTo, $document, $signingLink)
		,
			Role::SIGNER => match ($providerCode)
			{
				ProviderCode::GOS_KEY => new Im\Messages\InviteToSign\Goskey($userIdFrom, $userIdTo, $initiatorUserId, $initiatorName, $document, $signingLink),
				default => new Im\Messages\InviteToSign\EmployeeSes($userIdFrom, $userIdTo, $initiatorUserId, $initiatorName, $document, $signingLink),
			},
			default => null,
		};
	}

	/**
	 * @throws ObjectNotFoundException
	 */
	private function createByEmployeeInviteMessage(Document $document, Member $member): ?Message
	{
		$userIdFrom = $this->getBotUserId() ?? $document->createdById;
		$userIdTo = $this->memberService->getUserIdForMember($member);

		$link = $this->urlGenerator->makeSigningUrl($member);

		if (!$userIdTo || !$userIdFrom)
		{
			throw new ObjectNotFoundException('hrbot: no such user');
		}

		$initiatorUserId = $document->createdById;
		$initiatorName = $this->memberService->getUserRepresentedName($initiatorUserId);

		return match ($member->role)
		{
			Role::ASSIGNEE => new Im\Messages\ByEmployee\InviteCompany($userIdFrom, $userIdTo, $initiatorUserId, $initiatorName, $document, $link),
			Role::REVIEWER => new Im\Messages\ByEmployee\InviteReviewer($userIdFrom, $userIdTo, $initiatorUserId, $initiatorName, $document, $link),
			Role::SIGNER => new Im\Messages\ByEmployee\InviteEmployee($userIdFrom, $userIdTo, $document, $link),
			default => null,
		};
	}

	private function handleByEmployeeDocumentStoppedStatus(Document $document, ?Member $stopInitiatorMember = null): Result
	{
		$result = new Result();

		$stopInitiatorMemberUserId = $stopInitiatorMember
			? $this->memberService->getUserIdForMember($stopInitiatorMember)
			: $document->stoppedById
		;

		$userFrom =
			$this->getBotUserId()
			?? $stopInitiatorMemberUserId
			?? $document->representativeId
			?? $this->memberService->getUserIdForMember($this->memberService->getAssignee($document))
		;

		if ($stopInitiatorMemberUserId !== $document->createdById)
		{
			$userTo =
				$document->createdById
				?? $this->memberService->getUserIdForMember($this->memberService->getSigner($document))
			;

			// message to employee
			$result->addErrors($this->sendByEmployeeStoppedToEmployeeMessage(
				$userFrom,
				$userTo,
				$document,
				$stopInitiatorMemberUserId,
			)->getErrors());
		}

		// message to active member from company side
		$memberFromCompanySide = $this->memberService->getCurrentParticipantFromCompanySide($document);
		if ($memberFromCompanySide && $this->isMemberStillDoingHisJob($memberFromCompanySide, $document))
		{
			$userTo = $this->getActiveReviewerOrAssigneeUserId($document, $memberFromCompanySide);
			if ($userTo && $userTo !== $stopInitiatorMemberUserId && $userTo !== $document->createdById)
			{
				$result->addErrors(
					$this->sendStoppedMessageToCompany($userFrom, $userTo, $document, $stopInitiatorMemberUserId, $memberFromCompanySide?->role)->getErrors(),
				);
			}
		}

		return $result;
	}

	private function handleByCompanyDocumentStoppedStatus(Document $document, ?Member $stopInitiatorMember = null): Result
	{
		$result = new Result();

		// $stopInitiatorMember always known when stopped on portal side (by initiator)
		$stopInitiatorMemberUserId = $stopInitiatorMember
			? $this->memberService->getUserIdForMember($stopInitiatorMember)
			: ($document->stoppedById ?? $this->getActiveReviewerOrAssigneeUserId($document))
		;

		$userFrom =
			$this->getBotUserId()
			?? $stopInitiatorMemberUserId
			?? $document->createdById
		;

		// message to initiator
		if ($document->createdById !== $stopInitiatorMemberUserId)
		{
			$result->addErrors(
				$this->sendStoppedMessageToCompany(
					$userFrom,
					$document->createdById,
					$document,
					$stopInitiatorMemberUserId,
					null
				)->getErrors()
			);
		}

		// message to active member from company side
		$memberFromCompanySide = $this->memberService->getCurrentParticipantFromCompanySide($document);
		if ($memberFromCompanySide && $this->isMemberStillDoingHisJob($memberFromCompanySide, $document))
		{
			$userTo = $this->getActiveReviewerOrAssigneeUserId($document, $memberFromCompanySide);
			if ($userTo && $userTo !== $stopInitiatorMemberUserId && $userTo !== $document->createdById)
			{
				$result->addErrors(
					$this->sendStoppedMessageToCompany($userFrom, $userTo, $document, $stopInitiatorMemberUserId, $memberFromCompanySide?->role)->getErrors(),
				);
			}
		}

		// message for employees
		$signers = $this->memberRepository->listByDocumentIdAndRoleAndStatus(
			$document->id,
			Role::SIGNER,
			0,
			[Type\MemberStatus::STOPPABLE_READY],
		);
		foreach ($signers as $member)
		{
			$userTo = $this->memberService->getUserIdForMember($member);

			if ($userTo === $stopInitiatorMemberUserId)
			{
				continue;
			}

			$result->addErrors(
				$this->sendStoppedToEmployeeMessage(
					$userFrom,
					$userTo,
					$document,
					$stopInitiatorMemberUserId,
				)->getErrors()
			);
		}

		return $result;
	}

	private function isMemberStillDoingHisJob(Member $member, Document $document): bool
	{
		// logic for asignee with goskey
		if (
			$document->providerCode === ProviderCode::GOS_KEY
			&& $member->role === Role::ASSIGNEE
			&& $member->status === Type\MemberStatus::READY
		)
		{
			return $this->memberService->countWaitingSigners($document->id) > 0;
		}

		return in_array(
			$member->status,
			Type\MemberStatus::getStatusesNotFinished(),
			true
		);
	}

	private function sendErrorMessageToInitiator(int $userIdFrom, int $userIdTo, Member $assignee, Document $document): Result
	{
		return $this->imService->sendMessage(
			(new Im\Messages\Failure\SigningError(
				fromUser: $userIdFrom,
				toUser: $userIdTo,
				document: $document,
				member: $assignee,
				link: $this->urlGenerator->getSigningProcessLink($document),
			))->setLang($this->userService->getUserLanguage($userIdTo))
		);
	}

	/**
	 * invite to re-sign
	 */
	private function sendErrorMessageToAssignee(int $userIdFrom, int $userIdTo, Member $assignee, Document $document): Result
	{
		return $this->imService->sendMessage(
			(new Im\Messages\Failure\RepeatSigning(
				fromUser: $userIdFrom,
				toUser: $userIdTo,
				document: $document,
				link: $this->urlGenerator->makeSigningUrl($assignee),
			))->setLang($this->userService->getUserLanguage($userIdTo))
		);
	}

	private function sendStoppedMessageToCompany(int $userIdFrom, int $userIdTo, Document $document, ?int $whoStoppedUserId, ?string $role): Result
	{
		$message = $whoStoppedUserId === null
			? new Im\Messages\Failure\DocumentCancelled(
				fromUser: $userIdFrom,
				toUser: $userIdTo,
				document: $document,
				link: $this->urlGenerator->getSigningProcessLink($document),
			)
			: new Im\Messages\Failure\DocumentStopped(
				fromUser: $userIdFrom,
				toUser: $userIdTo,
				initiatorUserId: $whoStoppedUserId,
				initiatorName: $this->memberService->getUserRepresentedName($whoStoppedUserId),
				initiatorGender: $this->userService->getGender($whoStoppedUserId),
				document: $document,
				link: $this->urlGenerator->getSigningProcessLink($document),
				role: $role,
			)
		;

		return $this->imService->sendMessage(
			$message->setLang($this->userService->getUserLanguage($userIdTo))
		);
	}

	private function sendDoneMessageToEmployee(int $userIdFrom, Member $memberTo, Document $document): Result
	{
		// TODO Im\Messages\Done\ToEmployeeGoskey for goskey
		$userIdTo = $this->memberService->getUserIdForMember($memberTo);
		return $this->imService->sendMessage(
			(new Im\Messages\Done\ToEmployee(
				fromUser: $userIdFrom,
				toUser: $userIdTo,
				document: $document,
				link: $this->urlGenerator->makeSigningUrl($memberTo)
			))->setLang($this->userService->getUserLanguage($userIdTo))
		);
	}

	private function byEmployeeSendEmployeeSignedMessageToEmployee(
		int $userIdFrom,
		int $userIdTo,
		Document $document,
		Member $employee,
	): Result
	{
		return $this->imService->sendMessage(
			(new Im\Messages\ByEmployee\SignedByEmployee(
				fromUser: $userIdFrom,
				toUser: $userIdTo,
				document: $document,
				link: $this->urlGenerator->makeSigningUrl($employee),
			))->setLang($this->userService->getUserLanguage($userIdTo))
		);
	}

	private function byEmployeeSendDoneMessageToEmployee(Document $document): Result
	{
		$assignee = $this->memberService->getAssignee($document);

		if (!$assignee)
		{
			return (new Result())->addError(new Error('Assignee not found'));
		}

		$employee = $this->memberService->getSigner($document);

		if (!$employee)
		{
			return (new Result())->addError(new Error('Employee not found'));
		}

		$userFrom = $this->getBotUserId() ?? $document->representativeId ?? $this->memberService->getUserIdForMember($assignee);
		$userTo = $document->createdById ?? $this->memberService->getUserIdForMember($employee);

		return $this->imService->sendMessage(
			(new Im\Messages\ByEmployee\DoneEmployee(
				fromUser: $userFrom,
				toUser: $userTo,
				initiatorUserId: $document->representativeId,
				initiatorName: $this->memberService->getUserRepresentedName($document->representativeId),
				initiatorGender: $this->userService->getGender($document->representativeId),
				document: $document,
				link: $this->urlGenerator->makeSigningUrl($employee),
			))->setLang($this->userService->getUserLanguage($userTo))
		);
	}

	private function byEmployeeSendDoneMessageToCompany(Document $document): Result
	{
		$userFrom = $this->getBotUserId() ?? $document->representativeId ?? $this->memberService->getUserIdForMember(
			$this->memberService->getAssignee($document)
		);
		$userTo = $document->representativeId ?? $this->memberService->getUserIdForMember(
			$this->memberService->getAssignee($document)
		);

		return $this->imService->sendMessage(
			(new Im\Messages\ByEmployee\DoneCompany(
				fromUser: $userFrom,
				toUser: $userTo,
				initiatorUserId: $document->createdById,
				initiatorName: $this->memberService->getUserRepresentedName($document->createdById),
				document: $document,
			))->setLang($this->userService->getUserLanguage($userTo))
		);
	}

	private function sendDoneMessageToCompany(int $userIdFrom, int $userIdTo, Document $document): Result
	{
		return $this->imService->sendMessage(
			(new Im\Messages\Done\AllSignedToCompany(
				fromUser: $userIdFrom,
				toUser: $userIdTo,
				document: $document,
				link: $this->config->getB2eMySafeUrl(),
			))->setLang($this->userService->getUserLanguage($userIdTo))
		);
	}

	private function sendStoppedToEmployeeMessage(int $userIdFrom, int $userIdTo, Document $document, ?int $whoStoppedUserId): Result
	{
		if ($whoStoppedUserId === null)
		{
			// no need to notify if signing finished
			return new Result();
		}

		return $this->imService->sendMessage(
			(new Im\Messages\Failure\StoppedToEmployee(
				fromUser: $userIdFrom,
				toUser: $userIdTo,
				initiatorUserId: $whoStoppedUserId,
				initiatorName: $this->memberService->getUserRepresentedName($whoStoppedUserId),
				initiatorGender: $this->userService->getGender($whoStoppedUserId),
				document: $document,
			))->setLang($this->userService->getUserLanguage($userIdTo))
		);
	}

	private function sendByEmployeeStoppedToEmployeeMessage(int $userIdFrom, int $userIdTo, Document $document, int $whoStoppedUserId): Result
	{
		return $this->imService->sendMessage(
			(new Im\Messages\ByEmployee\StoppedToEmployee(
				fromUser: $userIdFrom,
				toUser: $userIdTo,
				initiatorUserId: $whoStoppedUserId,
				initiatorName: $this->memberService->getUserRepresentedName($whoStoppedUserId),
				initiatorGender: $this->userService->getGender($whoStoppedUserId),
				document: $document,
				link: $this->urlGenerator->makeSigningUrl($this->memberService->getSigner($document)),
			))->setLang($this->userService->getUserLanguage($userIdTo))
		);
	}

	private function sendEmployeeStoppedMessage(int $userIdFrom, int $userIdTo, Document $document, Member $memberSigner, int $whoStoppedUserId): Result
	{
		return $this->imService->sendMessage(
			(new Im\Messages\Failure\EmployeeStoppedToCompany(
				fromUser: $userIdFrom,
				toUser: $userIdTo,
				initiatorUserId: $whoStoppedUserId,
				initiatorName: $this->memberService->getUserRepresentedName($whoStoppedUserId),
				initiatorGender: $this->userService->getGender($whoStoppedUserId),
				document: $document,
				member: $memberSigner,
				link: $this->urlGenerator->getSigningProcessLink($document),
			))->setLang($this->userService->getUserLanguage($userIdTo))
		);
	}

	private function sendRefusedMessage(int $userIdFrom, int $userIdTo, Document $document): Result
	{
		return $this->imService->sendMessage(
			(new Im\Messages\Failure\RefusedToCompany(
				fromUser: $userIdFrom,
				toUser: $userIdTo,
				document: $document,
				initiatorUserId: $userIdFrom,
				initiatorName: $this->memberService->getUserRepresentedName($userIdFrom),
				initiatorGender: $this->userService->getGender($userIdFrom),
				link: $this->urlGenerator->getSigningProcessLink($document),
			))->setLang($this->userService->getUserLanguage($userIdTo))
		);
	}

	private function handleEmployeeStoppedStatus(Document $document, Member $member): Result
	{
		$whoStoppedUserId = $this->getActiveReviewerOrAssigneeUserId($document);

		if ($whoStoppedUserId === null)
		{
			return (new Result())->addError(new Error('Initiator of employee_stopped message not found'));
		}

		$userTo = $document->createdById;

		if ($whoStoppedUserId !== $userTo)
		{
			$userFrom = $this->getBotUserId() ?? $whoStoppedUserId;

			return $this->sendEmployeeStoppedMessage(
				userIdFrom: $userFrom,
				userIdTo: $userTo,
				document: $document,
				memberSigner: $member,
				whoStoppedUserId: $whoStoppedUserId,
			);
		}

		return new Result();
	}

	private function getActiveReviewerOrAssigneeUserId(Document $document, ?Member $member = null): ?int
	{
		if ($member === null)
		{
			$member = $this->memberService->getCurrentParticipantFromCompanySide($document);
		}

		if ($member && $userId = $this->memberService->getUserIdForMember($member))
		{
			return $userId;
		}

		return $document->representativeId;
	}

	private function getBotUserId(): ?int
	{
		return (new HrBot())->getBotUserId();
	}

	private function isByEmployee(Document $document): bool
	{
		return $document->initiatedByType === Type\Document\InitiatedByType::EMPLOYEE;
	}
}
