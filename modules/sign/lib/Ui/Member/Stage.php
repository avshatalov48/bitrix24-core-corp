<?php

namespace Bitrix\Sign\Ui\Member;

use Bitrix\Main\Grid\Cell\Label\Color;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Item;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\Document\InitiatedByType;
use Bitrix\Sign\Type\DocumentStatus;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Type\MemberStatus;

class Stage
{
	/**
	 * @var array<int, bool>
	 */
	private static array $documentHasSignedSigner = [];
	private static array $documentHasAllSignersStopped = [];
	private readonly MemberRepository $memberRepository;

	public static function createInstance(
		Item\Member $member,
		Item\Document $document,
		?MemberRepository $memberRepository = null
	): static
	{
		return new static($member, $document, $memberRepository);
	}

	public function __construct(
		private readonly Item\Member $member,
		private readonly Item\Document $document,
		?MemberRepository $memberRepository = null
	)
	{
		$this->memberRepository = $memberRepository ?? Container::instance()->getMemberRepository();
	}

	/** @return array{text: string, color: string} */
	public function getInfo(): array
	{
		return match ($this->member->role)
		{
			Role::ASSIGNEE => $this->getAssigneeLabelInfo(),
			Role::EDITOR => $this->getEditorLabelInfo(),
			Role::REVIEWER => $this->getReviewerLabelInfo(),
			Role::SIGNER => $this->getSignerLabelInfo(),
			default => $this->getDefaultLabelInfo()
		};
	}

	private function getStoppedLabelInfo(): array
	{
		return [
			'color' => Color::DEFAULT,
			'text' => Loc::getMessage('SIGN_MEMBER_STATUS_STAGE_CAPTION_STOPPED'),
		];
	}

	private function getDefaultLabelInfo(): array
	{
		if ($this->document->status === DocumentStatus::STOPPED)
		{
			return $this->getStoppedLabelInfo();
		}

		return [
			'color' => Color::DEFAULT,
			'text' => Loc::getMessage('SIGN_MEMBER_STATUS_STAGE_CAPTION_UNKNOWN'),
		];
	}

	private function getAssigneeDoneInfo(): array
	{
		return [
			'color' => Color::SUCCESS,
			'text' => Loc::getMessage('SIGN_MEMBER_STATUS_STAGE_CAPTION_ASSIGNEE_DONE'),
		];
	}

	private function getAssigneeLabelInfo(): array
	{
		if (
			$this->document->status === DocumentStatus::STOPPED
			&& !MemberStatus::isFinishForSigning($this->member->status)
		)
		{
			if ($this->isByEmployee())
			{
				return $this->getStoppedLabelInfo();
			}

			return $this->hasSignedSigner() ? $this->getAssigneeDoneInfo() : $this->getStoppedLabelInfo();
		}

		if (
			$this->document->status === DocumentStatus::STOPPED
			&& $this->member->role === Role::ASSIGNEE
			&& $this->isAllSignersStopped()
		)
		{
			return $this->getStoppedLabelInfo();
		}

		return match($this->member->status)
		{
			MemberStatus::WAIT => [
				'color' => Color::DEFAULT,
				'text' => Loc::getMessage('SIGN_MEMBER_STATUS_STAGE_CAPTION_ASSIGNEE_WAIT'),
			],
			MemberStatus::PROCESSING,
			MemberStatus::STOPPABLE_READY,
			MemberStatus::READY => [
				'color' => Color::WARNING,
				'text' => Loc::getMessage('SIGN_MEMBER_STATUS_STAGE_CAPTION_ASSIGNEE_READY'),
			],
			MemberStatus::DONE => [
				'color' => Color::SUCCESS,
				'text' => Loc::getMessage('SIGN_MEMBER_STATUS_STAGE_CAPTION_ASSIGNEE_DONE'),
			],
			MemberStatus::REFUSED => [
				'color' => Color::DANGER,
				'text' => Loc::getMessage('SIGN_MEMBER_STATUS_STAGE_CAPTION_ASSIGNEE_REFUSED'),
			],
			MemberStatus::STOPPED => $this->getStoppedLabelInfo(),
			default => $this->getDefaultLabelInfo(),
		};
	}

	private function getEditorLabelInfo(): array
	{
		if (
			$this->document->status === DocumentStatus::STOPPED
			&& !MemberStatus::isFinishForSigning($this->member->status)
		)
		{
			return $this->getStoppedLabelInfo();
		}

		return match($this->member->status)
		{
			MemberStatus::WAIT => [
				'color' => Color::DEFAULT,
				'text' => Loc::getMessage('SIGN_MEMBER_STATUS_STAGE_CAPTION_EDITOR_WAIT'),
			],
			MemberStatus::PROCESSING,
			MemberStatus::STOPPABLE_READY,
			MemberStatus::READY => [
				'color' => Color::WARNING,
				'text' => Loc::getMessage('SIGN_MEMBER_STATUS_STAGE_CAPTION_EDITOR_READY'),
			],
			MemberStatus::DONE => [
				'color' => Color::SUCCESS,
				'text' => Loc::getMessage('SIGN_MEMBER_STATUS_STAGE_CAPTION_EDITOR_DONE'),
			],
			MemberStatus::REFUSED => [
				'color' => Color::DANGER,
				'text' => Loc::getMessage('SIGN_MEMBER_STATUS_STAGE_CAPTION_EDITOR_REFUSED'),
			],
			MemberStatus::STOPPED => $this->getStoppedLabelInfo(),
			default => $this->getDefaultLabelInfo(),
		};
	}

	private function getReviewerLabelInfo(): array
	{
		if (
			$this->document->status === DocumentStatus::STOPPED
			&& !MemberStatus::isFinishForSigning($this->member->status)
		)
		{
			return $this->getStoppedLabelInfo();
		}

		return match($this->member->status)
		{
			MemberStatus::WAIT => [
				'color' => Color::DEFAULT,
				'text' => Loc::getMessage('SIGN_MEMBER_STATUS_STAGE_CAPTION_REVIEWER_WAIT'),
			],
			MemberStatus::PROCESSING,
			MemberStatus::STOPPABLE_READY,
			MemberStatus::READY => [
				'color' => Color::WARNING,
				'text' => Loc::getMessage('SIGN_MEMBER_STATUS_STAGE_CAPTION_REVIEWER_READY'),
			],
			MemberStatus::DONE => [
				'color' => Color::SUCCESS,
				'text' => Loc::getMessage('SIGN_MEMBER_STATUS_STAGE_CAPTION_REVIEWER_DONE'),
			],
			MemberStatus::REFUSED => [
				'color' => Color::DANGER,
				'text' => Loc::getMessage('SIGN_MEMBER_STATUS_STAGE_CAPTION_REVIEWER_REFUSED'),
			],
			MemberStatus::STOPPED => $this->getStoppedLabelInfo(),
			default => $this->getDefaultLabelInfo(),
		};
	}

	private function getSignerLabelInfo(): array
	{
		if (
			$this->document->status === DocumentStatus::STOPPED
			&& (
				$this->member->status === MemberStatus::STOPPABLE_READY
				|| MemberStatus::canBeChangedByFirstPartyMember($this->member->status)
			)
		)
		{
			return $this->getStoppedLabelInfo();
		}

		return match($this->member->status)
		{
			MemberStatus::WAIT => [
				'color' => Color::DEFAULT,
				'text' => Loc::getMessage('SIGN_MEMBER_STATUS_STAGE_CAPTION_SIGNER_WAIT'),
			],
			MemberStatus::PROCESSING,
			MemberStatus::STOPPABLE_READY,
			MemberStatus::READY => [
				'color' => Color::WARNING,
				'text' => Loc::getMessage('SIGN_MEMBER_STATUS_STAGE_CAPTION_SIGNER_READY'),
			],
			MemberStatus::DONE => [
				'color' => Color::SUCCESS,
				'text' => Loc::getMessage('SIGN_MEMBER_STATUS_STAGE_CAPTION_SIGNER_DONE'),
			],
			MemberStatus::REFUSED => [
				'color' => Color::DANGER,
				'text' => Loc::getMessage('SIGN_MEMBER_STATUS_STAGE_CAPTION_SIGNER_REFUSED'),
			],
			MemberStatus::STOPPED => $this->getStoppedLabelInfo(),
			default => $this->getDefaultLabelInfo(),
		};
	}

	private function hasSignedSigner(): bool
	{
		$documentId = $this->document->id;

		return self::$documentHasSignedSigner[$documentId] ??= $this->memberRepository
			->isSignerExistsByDocumentIdInStatus($documentId, [MemberStatus::DONE])
		;
	}

	private function isAllSignersStopped(): bool
	{
		$documentId = $this->document->id;

		return !(self::$documentHasAllSignersStopped[$documentId] ??= $this->memberRepository
			->isSignerExistsByDocumentIdNotInStatus($documentId, [MemberStatus::STOPPED])
		);
	}

	private function isByEmployee(): bool
	{
		return $this->document->initiatedByType === InitiatedByType::EMPLOYEE;
	}
}
