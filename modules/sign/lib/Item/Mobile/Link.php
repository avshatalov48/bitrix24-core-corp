<?php

namespace Bitrix\Sign\Item\Mobile;

use Bitrix\Sign\Contract\Item;
use Bitrix\Sign\Type\Document\InitiatedByType;
use Bitrix\Sign\Type\DocumentStatus;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Type\MemberStatus;
use Bitrix\Sign\Type\ProviderCode;

class Link implements Item
{
	public const STATE_CONTINUE_WEB_ASSIGNEE = 'GO_TO_WEB';
	public const STATE_CONTINUE_WEB_EDITOR = 'GO_TO_WEB_EDITOR';
	public const STATE_MEMBER_SIGNED = 'PROCESSING';
	public const STATE_DOCUMENT_STOPPED = 'REFUSED';
	public const STATE_MEMBER_READY_FOR_DOWNLOAD_DOCUMENT_STOPPED = 'REFUSED_BY_ASSIGNEE';
	public const STATE_MEMBER_STOPPED = 'REFUSED_SELF';
	public const STATE_MEMBER_REVIEWER_SIGNED = 'REVIEW_SUCCESS';
	public const STATE_MEMBER_READY_FOR_DOWNLOAD = 'SIGNED';
	public const STATE_MEMBER_ASSIGNEE_SIGNED = 'SIGNED_BY_ASSIGNEE';
	public const STATE_MEMBER_EDITOR_SIGNED = 'SIGNED_BY_EDITOR';
	public const STATE_MEMBER_PROCESSING_WAITING = 'PROCESSING_WAITING';

	// mobile ui depends on member role
	public const ROLE_SIGNER = 'signer';
	public const ROLE_ASSIGNEE = 'assignee';
	public const ROLE_EDITOR = 'editor';
	public const ROLE_REVIEWER = 'reviewer';

	private bool $goskeyAssigneeAlmostDone = false;

	public function __construct(
		public ?string $url,
		public ?string $documentTitle = null,
		public ?int $memberId = null,
		private readonly ?string $role = null, /** @see \Bitrix\Sign\Type\Member\Role */
		private readonly ?string $status = null, /** @see \Bitrix\Sign\Type\MemberStatus */
		private readonly ?string $documentStatus = null, /** @see \Bitrix\Sign\Type\DocumentStatus */
		private readonly ?string $providerCode = null, /** @see \Bitrix\Sign\Type\ProviderCode */
		private readonly bool $readyForDownload = false,
		private readonly ?InitiatedByType $initiatedByType = null,
	) {}

	/**
	 * Verify that the deferred confirmation is still relevant or needs to be skipped
	 */
	public function canBeConfirmed(): bool
	{
		return
			in_array($this->role, [Role::SIGNER, Role::ASSIGNEE], true)
			&& in_array($this->status, [MemberStatus::READY, MemberStatus::STOPPABLE_READY], true)
			&& !in_array($this->documentStatus, [DocumentStatus::STOPPED, DocumentStatus::DONE], true)
		;
	}

	/**
	 * Verify that this signing can be opened in mobile app
	 */
	public function isReadyForSigningOnMobile(): bool
	{
		return $this->getDocumentSigningState() === null;
	}

	public function isGoskey(): bool
	{
		return $this->providerCode === ProviderCode::GOS_KEY;
	}

	public function isExternal(): bool
	{
		return $this->providerCode === ProviderCode::EXTERNAL;
	}

	/**
	 * Used to display texts by role
	 */
	public function getRole(): string
	{
		return match ($this->role) {
			Role::ASSIGNEE => self::ROLE_ASSIGNEE,
			Role::EDITOR => self::ROLE_EDITOR,
			Role::REVIEWER => self::ROLE_REVIEWER,
			default => self::ROLE_SIGNER,
		};
	}

	/**
	 * Various stubs and empty states
	 */
	public function getDocumentSigningState(): ?string
	{
		return match (true) {
			$this->readyForDownload => match ($this->documentStatus) {
				DocumentStatus::STOPPED => self::STATE_MEMBER_READY_FOR_DOWNLOAD_DOCUMENT_STOPPED,
				default => self::STATE_MEMBER_READY_FOR_DOWNLOAD,
			},

			// TODO merge with sign/grid logic
			$this->documentStatus === DocumentStatus::STOPPED || $this->status === MemberStatus::STOPPED => self::STATE_DOCUMENT_STOPPED,
			$this->status === MemberStatus::REFUSED => self::STATE_MEMBER_STOPPED,

			$this->isGoskeyAssigneeAlmostDone() => self::STATE_MEMBER_ASSIGNEE_SIGNED,

			!$this->isAvailableOnMobile() && $this->status !== MemberStatus::DONE => match ($this->role) {
				Role::EDITOR => self::STATE_CONTINUE_WEB_EDITOR,
				default => self::STATE_CONTINUE_WEB_ASSIGNEE,
			},

			$this->status === MemberStatus::DONE => match ($this->role) {
				Role::ASSIGNEE => self::STATE_MEMBER_ASSIGNEE_SIGNED,
				Role::REVIEWER => self::STATE_MEMBER_REVIEWER_SIGNED,
				Role::EDITOR => self::STATE_MEMBER_EDITOR_SIGNED,
				default => self::STATE_MEMBER_SIGNED,
			},

			default => null,
		};
	}

	public function getInitiatedByType(): string
	{
		return $this->initiatedByType->value;
	}

	public function isGoskeyAssigneeAlmostDone(): bool
	{
		return $this->goskeyAssigneeAlmostDone;
	}

	public function setGoskeyAssigneeAlmostDone(): self
	{
		$this->goskeyAssigneeAlmostDone = true;
		return $this;
	}

	private function isAvailableOnMobile(): bool
	{
		return in_array($this->role, [Role::SIGNER, Role::REVIEWER], true);
	}
}
