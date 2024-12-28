<?php

namespace Bitrix\Sign\Service\Sign;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Sign\Callback\Messages\Member\MemberStatusChanged;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Repository\LegalLogRepository;
use Bitrix\Sign\Service\UserService;
use Bitrix\Sign\Type\Document\InitiatedByType;
use Bitrix\Sign\Type\DocumentScenario;
use Bitrix\Sign\Type\DocumentStatus;
use Bitrix\Sign\Type\LegalLogCode;
use Bitrix\Sign\Type\MemberStatus;
use Psr\Log\LoggerInterface;
use Bitrix\Sign\Debug\Logger;
use Bitrix\Sign\Item\B2e\LegalLog;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Type\Member\Role;

class LegalLogService
{
	private readonly LegalLogRepository $logRepository;
	private readonly UserService $userService;
	private readonly MemberService $memberService;
	private readonly LoggerInterface $logger;
	private readonly MemberRepository $memberRepository;

	public function __construct(
		?LegalLogRepository $logRepository = null,
		?UserService $userService = null,
		?MemberService $memberService = null,
		?LoggerInterface $logger = null,
		?MemberRepository $memberRepository = null,
	)
	{
		$this->logRepository = $logRepository ?? Container::instance()->getLegalLogRepository();
		$this->userService = $userService ?? Container::instance()->getUserService();
		$this->memberService = $memberService ?? Container::instance()->getMemberService();
		$this->logger = $logger ?? Logger::getInstance();
		$this->memberRepository = $memberRepository ?? Container::instance()->getMemberRepository();
	}

	public function registerDocumentStart(Document $document): void
	{
		$additionalInfo ="CompanyUid: $document->companyUid";
		$this->register(LegalLogCode::DOCUMENT_START, $document, null, $additionalInfo);
	}

	public function registerDocumentStop(Document $document, ?Member $member = null): void
	{
		$this->register(LegalLogCode::DOCUMENT_STOP, $document, $member, null, $document->stoppedById);
	}

	protected function registerDocumentDone(Document $document): void
	{
		$this->register(LegalLogCode::DOCUMENT_DONE, $document);
	}

	public function registerDocumentChangedStatus(Document $document, ?Member $member = null): void
	{
		match ($document->status)
		{
			DocumentStatus::SIGNING => $this->registerDocumentStart($document),
			DocumentStatus::STOPPED => $this->registerDocumentStop($document, $member),
			DocumentStatus::DONE => $this->registerDocumentDone($document),
			default => null,
		};
	}

	public function registerChatInviteDelivered(Document $document, Member $member): void
	{
		$this->register(LegalLogCode::CHAT_INVITE_DELIVERED, $document, $member);
	}

	public function registerChatInviteNotDelivered(Document $document, Member $member): void
	{
		$this->register(LegalLogCode::CHAT_INVITE_NOT_DELIVERED, $document, $member);
	}

	public function registerFromMemberStatusChanged(
		Document $document,
		Member $member,
		MemberStatusChanged $message,
	): void
	{
		if (!DocumentScenario::isB2EScenario($document->scenario))
		{
			return;
		}

		match ($member->role)
		{
			Role::SIGNER => $this->registerSignerChangedStatus($document, $member, $message),
			Role::REVIEWER => $this->registerReviewerChangedStatus($document, $member),
			Role::EDITOR => $this->registerEditorChangedStatus($document, $member),
			Role::ASSIGNEE => $this->registerAssigneeChangedStatus($document, $member),
			default => null,
		};
	}

	public function registerMemberFileSaved(Document $document, Member $member, int $fileId): void
	{
		$additionalInfo = "FileId: $fileId";
		$this->register(LegalLogCode::MEMBER_FILE_SAVED, $document, $member, $additionalInfo);
	}

	protected function registerSignerChangedStatus(
		Document $document,
		Member $member,
		MemberStatusChanged $message,
	): void
	{
		match ($member->status)
		{
			MemberStatus::REFUSED => $this->registerSignerRefuse($document, $member),
			MemberStatus::STOPPABLE_READY,
			MemberStatus::READY => $this->registerAssigneeSignedMember($document, $member),
			MemberStatus::PROCESSING => $this->registerSignerProcessing($document, $member),
			MemberStatus::DONE => $this->registerSignerSign($document, $member, $message),
			MemberStatus::WAIT => $this->registerSignError($document, $member, $message),
			MemberStatus::STOPPED => $this->registerSignerStopped($document, $member, $message),
			default => null,
		};
	}

	protected function registerSignerRefuse(Document $document, Member $member): void
	{
		$this->register(LegalLogCode::SIGNER_REFUSE, $document, $member);
	}

	protected function registerAssigneeSignedMember(Document $document, Member $member): void
	{
		if ($document->initiatedByType === InitiatedByType::EMPLOYEE)
		{
			return;
		}

		$additionalInfo = "Assignee: $document->representativeId, name: "
			. $this->getUserName($document->representativeId);
		$this->register(LegalLogCode::ASSIGNEE_SIGNED_MEMBER, $document, $member, $additionalInfo);
	}

	protected function registerSignerSign(
		Document $document,
		Member $member,
		MemberStatusChanged $message,
	): void
	{
		$rows = [];
		if ($message->getProvider())
		{
			$rows[] = "Provider: {$message->getProvider()}";
		}
		if ($message->getSesSign())
		{
			$rows[] = "SesSign: {$message->getSesSign()}";
		}
		if ($message->getSesUsername())
		{
			$rows[] = "SesUsername: {$message->getSesUsername()}";
		}

		$additionalInfo = implode(PHP_EOL, $rows);

		$this->register(LegalLogCode::SIGNER_SIGN, $document, $member, $additionalInfo);
	}

	protected function registerSignError(
		Document $document,
		Member $member,
		MemberStatusChanged $message,
	): void
	{
		$additionalInfo = "ErrorCode: {$message->getErrorCode()}";
		$this->register(LegalLogCode::SIGN_ERROR, $document, $member, $additionalInfo);
	}

	protected function registerSignerStopped(
		Document $document,
		Member $member,
		MemberStatusChanged $message,
	): void
	{
		if ($message->getInitiatorUid())
		{
			$initiator = $this->memberService->getMemberOfDocument($document, $message->getInitiatorUid());
			if ($initiator)
			{
				$this->register(LegalLogCode::INITIATE_MEMBER_STOP, $document, $initiator);
			}
		}
		$this->register(LegalLogCode::MEMBER_STOPPED, $document, $member);
	}

	protected function registerReviewerChangedStatus(Document $document, Member $member): void
	{
		match ($member->status)
		{
			MemberStatus::DONE => $this->registerReviewerAccept($document, $member),
			default => null,
		};
	}

	protected function registerEditorChangedStatus(Document $document, Member $member): void
	{
		match ($member->status)
		{
			MemberStatus::DONE => $this->registerEditorAccept($document, $member),
			default => null,
		};
	}

	protected function registerReviewerAccept(Document $document, Member $member): void
	{
		$this->register(LegalLogCode::REVIEWER_ACCEPT, $document, $member);
	}

	protected function registerEditorAccept(Document $document, Member $member): void
	{
		$this->register(LegalLogCode::EDITOR_ACCEPT, $document, $member);
	}

	protected function register(
		string $code,
		Document $document,
		?Member $member = null,
		?string $additionalInfo = null,
		?int $userId = null,
	): void
	{
		if ($member)
		{
			$userId ??= $this->memberService->getUserIdForMember($member, $document);
		}

		if (empty($userId))
		{
			$userId = CurrentUser::get()->getId();
		}

		$item = new LegalLog(
			code: $code,
			documentId: $document->id,
			documentUid: $document->uid,
			description: $this->getDescription($code, $document, $member, $userId, $additionalInfo),
			memberId: $member?->id,
			memberUid: $member?->uid,
			userId: $userId,
		);

		$addLogResult = $this->logRepository->add($item);

		if (!$addLogResult->isSuccess())
		{
			$this->logger->error('Cannot log to DB legal log: '
				. print_r($addLogResult->getErrors() , true));
		}
	}

	protected function getDescription(
		string $code,
		Document $document,
		?Member $member = null,
		?int $userId = null,
		?string $additionalInfo = null,
	): ?string
	{
		$rows = [
			"Document: $document->uid",
			"Action: $code",
		];

		if ($member)
		{
			$rows[] = "Member: $member->uid, role: {$member->role}";
		}
		if ($userId)
		{
			$rows[] = "UserId: $userId, name: " . $this->getUserName($userId);
		}

		if ($additionalInfo)
		{
			$rows[] = $additionalInfo;
		}

		return implode(PHP_EOL, $rows);
	}

	private function getUserName(int $userId): string
	{
		$userName = $this->memberService->getUserRepresentedName($userId);
		if (!empty(trim($userName)))
		{
			return $userName;
		}

		$user = $this->userService->getUserById($userId);
		if ($user)
		{
			return $this->userService->getUserName($user);
		}

		return '';
	}

	private function registerSignerProcessing(Document $document, Member $member): void
	{
		$this->register(LegalLogCode::SIGNER_PROCESSING, $document, $member);
	}

	private function registerAssigneeChangedStatus(Document $document, Member $member): void
	{
		match ($member->status)
		{
			MemberStatus::DONE => $this->registerAssigneeDone($document),
			default => null,
		};
	}

	private function registerAssigneeDone(Document $document): void
	{
		if (
			$document->initiatedByType !== InitiatedByType::EMPLOYEE
			|| $document->id === null
			|| $document->representativeId === null
		)
		{
			return;
		}

		$signer = $this->memberRepository->getByDocumentIdWithRole($document->id, Role::SIGNER);
		if (!$signer)
		{
			return;
		}

		$additionalInfo = "Assignee: $document->representativeId, name: "
			. $this->getUserName($document->representativeId);
		$this->register(LegalLogCode::ASSIGNEE_SIGNED_MEMBER, $document, $signer, $additionalInfo);
	}
}
