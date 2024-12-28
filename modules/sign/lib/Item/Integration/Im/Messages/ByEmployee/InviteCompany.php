<?php

namespace Bitrix\Sign\Item\Integration\Im\Messages\ByEmployee;

use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Integration\Im\Message;

class InviteCompany extends Message\WithInitiator\ByEmployee
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
		return 'byEmployeeInviteCompany';
	}

	public function getFallbackText(): string
	{
		return $this->getLocalizedFallbackMessage(
			'SIGN_CALLBACK_CHAT_BY_EMPLOYEE_INVITE_COMPANY',
			[
				'#DOC_NAME#' => $this->getDocumentName($this->getDocument()),
				'#SIGN_URL#' => $this->getLink(),
				'#INITIATOR_NAME#' => $this->getInitiatorName(),
			]
		);
	}
}
