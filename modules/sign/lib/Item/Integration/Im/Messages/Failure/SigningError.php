<?php

namespace Bitrix\Sign\Item\Integration\Im\Messages\Failure;

use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Integration\Im\Message;
use Bitrix\Sign\Item\Member;

class SigningError extends Message
{
	public function __construct(
		int $fromUser,
		int $toUser,
		Document $document,
		Member $member,
		string $link,
	)
	{
		parent::__construct($fromUser, $toUser);
		$this->document = $document;
		$this->member = $member;
		$this->link = $link;
	}

	public function getStageId(): string
	{
		return 'signingError';
	}

	public function getFallbackText(): string
	{
		return $this->getLocalizedFallbackMessage(
			'SIGN_CALLBACK_CHAT_SIGNING_ERROR',
			[
				'#SIGNER_NAME#' => $this->getMemberName($this->getMember()),
				'#DOC_NAME#' => $this->getDocumentName($this->getDocument()),
				'#GRID_URL#' => $this->getLink(),
			]
		);
	}
}
