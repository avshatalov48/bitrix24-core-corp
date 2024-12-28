<?php

namespace Bitrix\Sign\Item\Integration\Im\Messages\ByEmployee;

use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Integration\Im\Message;

class InviteEmployee extends Message\ByEmployee
{
	public function __construct(
		int $fromUser,
		int $toUser,
		Document $document,
		string $link,
	)
	{
		parent::__construct($fromUser, $toUser);
		$this->document = $document;
		$this->link = $link;
	}

	public function getStageId(): string
	{
		return 'byEmployeeInviteEmployee';
	}

	public function getFallbackText(): string
	{
		return $this->getLocalizedFallbackMessage(
			'SIGN_CALLBACK_CHAT_BY_EMPLOYEE_INVITE_EMPLOYEE',
			[
				'#DOC_NAME#' => $this->getDocumentName($this->getDocument()),
				'#SIGN_URL#' => $this->getLink(),
			]
		);
	}
}
