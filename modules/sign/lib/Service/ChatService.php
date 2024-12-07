<?php

namespace Bitrix\Sign\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\UserTable;
use Bitrix\Sign\Config;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Service\Sign\UrlGeneratorService;
use Bitrix\Sign\Type;
use Bitrix\Sign\Service\Integration\Im\ImService;
use Bitrix\Sign\Item\Integration\Im;
use Bitrix\Sign\Service\Sign\MemberService;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Type\ProviderCode;

class ChatService
{
	private ImService $imService;
	private MemberService $memberService;
	private MemberRepository $memberRepository;
	private Config\Storage $config;
	private UrlGeneratorService $urlGenerator;

	public function __construct(
		?ImService $imService = null,
		?MemberService $memberService = null,
		?MemberRepository $memberRepository = null,
		?Config\Storage $config = null,
		?UrlGeneratorService $urlGenerator = null,
	)
	{
		$this->imService = $imService ?? Container::instance()->getImService();
		$this->memberService = $memberService ?? Container::instance()->getMemberService();
		$this->memberRepository = $memberRepository ?? Container::instance()->getMemberRepository();
		$this->urlGenerator = $urlGenerator ?? Container::instance()->getUrlGeneratorService();
		$this->config = $config ?? Config\Storage::instance();
	}

	public function isAvailable(): bool
	{
		return $this->imService->isAvailable();
	}

	public function sendInviteMessage(Document $document, Member $member, string $providerCode): Result
	{
		$userIdFrom = $this->getBotUserId() ?? $document->createdById;
		$userIdTo = $this->memberService->getUserIdForMember($member);
		$signingLink = $this->urlGenerator->makeSigningUrl($member);

		if (!$userIdTo || !$userIdFrom)
		{
			return (new Result())->addError(new Error('no such user'));
		}

		$initiatorUserId = $document->createdById;
		$initiatorName = $this->memberService->getUserRepresentedName($initiatorUserId);

		$message = match ($member->role)
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
		};

		$message->setLang(self::getUserLanguage($userIdTo));

		return $this->imService->sendMessage($message);
	}

	public function handleDocumentStatusChangedMessage(Document $document, string $newStatus, ?Member $stopInitiatorMember = null): Result
	{
		switch ($newStatus)
		{
			case Type\DocumentStatus::STOPPED:
				return $this->handleDocumentStoppedStatus($document, $stopInitiatorMember);

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

	private function handleDocumentStoppedStatus(Document $document, ?Member $stopInitiatorMember = null): Result
	{
		$result = new Result();

		$stopInitiatorMemberUserId = $stopInitiatorMember
			? $this->memberService->getUserIdForMember($stopInitiatorMember)
			: $document->stoppedById
		;

		$userFrom =
			$this->getBotUserId()
			?? $stopInitiatorMemberUserId
			?? $document->stoppedById
			?? $this->getActiveReviewerOrAssigneeUserId($document)
		;

		// message to initiator
		if ($document->createdById !== $stopInitiatorMemberUserId)
		{
			$result->addErrors(
				$this->sendStoppedMessageToCompany($userFrom, $document->createdById, $document, $stopInitiatorMemberUserId, null)->getErrors()
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

		// message for users
		$signers = $this->memberRepository->listByDocumentIdAndRoleAndStatus(
			$document->id,
			Role::SIGNER,
			0,
			[Type\MemberStatus::STOPPABLE_READY],
		);
		foreach ($signers as $member)
		{
			$result->addErrors(
				$this->sendStoppedToEmployeeMessage(
					$userFrom,
					$this->memberService->getUserIdForMember($member),
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
			))->setLang(self::getUserLanguage($userIdTo))
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
			))->setLang(self::getUserLanguage($userIdTo))
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
				initiatorGender: $this->getGender($whoStoppedUserId),
				document: $document,
				link: $this->urlGenerator->getSigningProcessLink($document),
				role: $role,
			)
		;

		return $this->imService->sendMessage(
			$message->setLang(self::getUserLanguage($userIdTo))
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
			))->setLang(self::getUserLanguage($userIdTo))
		);
	}

	public function sendDoneMessageToCompany(int $userIdFrom, int $userIdTo, Document $document): Result
	{
		return $this->imService->sendMessage(
			(new Im\Messages\Done\AllSignedToCompany(
				fromUser: $userIdFrom,
				toUser: $userIdTo,
				document: $document,
				link: $this->config->getB2eMySafeUrl(),
			))->setLang(self::getUserLanguage($userIdTo))
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
				initiatorGender: $this->getGender($whoStoppedUserId),
				document: $document,
			))->setLang(self::getUserLanguage($userIdTo))
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
				initiatorGender: $this->getGender($whoStoppedUserId),
				document: $document,
				member: $memberSigner,
				link: $this->urlGenerator->getSigningProcessLink($document),
			))->setLang(self::getUserLanguage($userIdTo))
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
				initiatorGender: $this->getGender($userIdFrom),
				link: $this->urlGenerator->getSigningProcessLink($document),
			))->setLang(self::getUserLanguage($userIdTo))
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

	private static function getUserLanguage(null|int $userId): null|string
	{
		if (!$userId)
		{
			return null;
		}

		$res = UserTable::query()
			->where('ID', $userId)
			->setSelect([
							'NOTIFICATION_LANGUAGE_ID',
						])
			->exec()
			->fetchObject()
		;

		return ($res)
			? $res->getNotificationLanguageId()
			: null
		;
	}

	private function getBotUserId(): ?int
	{
		if (!\Bitrix\Main\Loader::includeModule('imbot'))
		{
			return null;
		}

		if (!class_exists(\Bitrix\Imbot\Bot\HrBot::class))
		{
			return null;
		}

		return \Bitrix\Imbot\Bot\HrBot::getBotIdOrRegister() ?: null;
	}

	private function getGender(int $userId): Type\User\Gender
	{
		$profileGender = UserTable::getById($userId)?->fetchObject()?->getPersonalGender();
		// known (supported) genders
		return match ($profileGender)
		{
			'M' => Type\User\Gender::MALE,
			'F' => Type\User\Gender::FEMALE,
			default => Type\User\Gender::DEFAULT,
		};
	}
}
