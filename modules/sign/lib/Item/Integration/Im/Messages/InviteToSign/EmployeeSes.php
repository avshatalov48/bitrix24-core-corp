<?php

namespace Bitrix\Sign\Item\Integration\Im\Messages\InviteToSign;

use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Integration\Im\Message;

class EmployeeSes extends Message\WithInitiator
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
		return $this->getInitiatorUserId() === $this->getUserTo()
			? 'inviteEmployeeSes'
			: 'inviteEmployeeSesWithInitiator'
		;
	}

	public function getFallbackText(): string
	{
		$messageId = $this->getInitiatorUserId() === $this->getUserTo()
			? 'SIGN_CALLBACK_CHAT_INVITE_EMPLOYEE_SES_MSGVER_2'
			: 'SIGN_CALLBACK_CHAT_INVITE_EMPLOYEE_SES_WITH_INITIATOR'
		;

		return $this->getLocalizedFallbackMessage(
			$messageId,
			[
				'#DOC_NAME#' => $this->getDocumentName($this->getDocument()),
				'#SIGN_URL#' => $this->getLink(),
				'#INITIATOR_NAME#' => $this->getInitiatorName(),
			]
		);
	}
}
