<?php

namespace Bitrix\Sign\Item\Integration\Im\Messages\InviteToSign;

use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Integration\Im\Message;

class ReviewerWithInitiator extends Message\WithInitiator
{
	public function __construct(
		int $fromUser,
		int $toUser,
		int $initiatorUserId,
		string $initiatorName,
		Document $document,
		string $link,
	)
	{
		parent::__construct($fromUser, $toUser, $initiatorUserId, $initiatorName);
		$this->document = $document;
		$this->link = $link;
	}

	public function getStageId(): string
	{
		return 'inviteReviewerWithInitiator';
	}

	public function getFallbackText(): string
	{
		return $this->getLocalizedFallbackMessage(
			'SIGN_CALLBACK_CHAT_INVITE_REVIEWER_INITIATOR',
			[
				'#DOC_NAME#' => $this->getDocumentName($this->getDocument()),
				'#SIGN_URL#' => $this->getLink(),
				'#INITIATOR_NAME#' => $this->getInitiatorName(),
			]
		);
	}
}
