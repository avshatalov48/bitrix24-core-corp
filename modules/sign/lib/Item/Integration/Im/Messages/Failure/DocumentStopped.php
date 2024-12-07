<?php

namespace Bitrix\Sign\Item\Integration\Im\Messages\Failure;

use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Integration\Im\Message;
use Bitrix\Sign\Type\User\Gender;
use Bitrix\Sign\Type\Member\Role;

class DocumentStopped extends Message\WithInitiator
{
	private ?string $role;

	public function __construct(
		int $fromUser,
		int $toUser,
		int $initiatorUserId,
		string $initiatorName,
		Gender $initiatorGender,
		Document $document,
		string $link,
		?string $role,
	)
	{
		parent::__construct($fromUser, $toUser, $initiatorUserId, $initiatorName, $initiatorGender);
		$this->document = $document;
		$this->link = $link;
		$this->role = $role;
	}

	public function getStageId(): string
	{
		return match ($this->role)
		{
			// documentStopped — old message
			Role::ASSIGNEE => match ($this->getInitiatorGender())
			{
				Gender::MALE => 'documentStoppedToAssigneeM',
				Gender::FEMALE => 'documentStoppedToAssigneeF',
				default => 'documentStoppedToAssignee',
			},

			Role::REVIEWER => match ($this->getInitiatorGender())
			{
				Gender::MALE => 'documentStoppedToReviewerM',
				Gender::FEMALE => 'documentStoppedToReviewerF',
				default => 'documentStoppedToReviewer',
			},

			Role::EDITOR => match ($this->getInitiatorGender())
			{
				Gender::MALE => 'documentStoppedToEditorM',
				Gender::FEMALE => 'documentStoppedToEditorF',
				default => 'documentStoppedToEditor',
			},

			default => match ($this->getInitiatorGender())
			{
				Gender::MALE => 'documentStoppedToInitiatorM',
				Gender::FEMALE => 'documentStoppedToInitiatorF',
				default => 'documentStoppedToInitiator',
			},
		};
	}

	public function getFallbackText(): string
	{
		$messageId = match ($this->role)
		{
			Role::ASSIGNEE => match ($this->getInitiatorGender())
			{
				Gender::MALE => 'SIGN_CALLBACK_CHAT_DOCUMENT_STOPPED_TO_ASSIGNEEM',
				Gender::FEMALE => 'SIGN_CALLBACK_CHAT_DOCUMENT_STOPPED_TO_ASSIGNEEF',
				default => 'SIGN_CALLBACK_CHAT_DOCUMENT_STOPPED_TO_ASSIGNEE',
			},

			Role::REVIEWER => match ($this->getInitiatorGender())
			{
				Gender::MALE => 'SIGN_CALLBACK_CHAT_DOCUMENT_STOPPED_TO_REVIEWERM',
				Gender::FEMALE => 'SIGN_CALLBACK_CHAT_DOCUMENT_STOPPED_TO_REVIEWERF',
				default => 'SIGN_CALLBACK_CHAT_DOCUMENT_STOPPED_TO_REVIEWER',
			},

			Role::EDITOR => match ($this->getInitiatorGender())
			{
				Gender::MALE => 'SIGN_CALLBACK_CHAT_DOCUMENT_STOPPED_TO_EDITORM',
				Gender::FEMALE => 'SIGN_CALLBACK_CHAT_DOCUMENT_STOPPED_TO_EDITORF',
				default => 'SIGN_CALLBACK_CHAT_DOCUMENT_STOPPED_TO_EDITOR',
			},

			// initiator
			default => match ($this->getInitiatorGender())
			{
				Gender::MALE => 'SIGN_CALLBACK_CHAT_DOCUMENT_STOPPEDM_MSGVER_2',
				Gender::FEMALE => 'SIGN_CALLBACK_CHAT_DOCUMENT_STOPPEDF_MSGVER_2',
				default => 'SIGN_CALLBACK_CHAT_DOCUMENT_STOPPED_MSGVER_2',
			},
		};

		return $this->getLocalizedFallbackMessage(
			$messageId,
			[
				'#DOC_NAME#' => $this->getDocumentName($this->getDocument()),
				'#INITIATOR_NAME#' => $this->getInitiatorName(),
				'#GRID_URL#' => $this->getLink(),
			]
		);
	}
}
