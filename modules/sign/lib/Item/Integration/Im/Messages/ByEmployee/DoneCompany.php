<?php

namespace Bitrix\Sign\Item\Integration\Im\Messages\ByEmployee;

use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Integration\Im\Message;

class DoneCompany extends Message\WithInitiator\ByEmployee
{
	public function __construct(
		int $fromUser,
		int $toUser,
		int $initiatorUserId,
		string $initiatorName,
		Document $document,
	)
	{
		parent::__construct($fromUser, $toUser, $initiatorUserId, $initiatorName);
		$this->document = $document;
	}

	public function getStageId(): string
	{
		return 'byEmployeeDoneCompany';
	}

	public function getFallbackText(): string
	{
		return $this->getLocalizedFallbackMessage(
			'SIGN_CALLBACK_CHAT_BY_EMPLOYEE_DONE_COMPANY',
			[
				'#DOC_NAME#' => $this->getDocumentName($this->getDocument()),
				'#INITIATOR_NAME#' => $this->getInitiatorName(),
			]
		);
	}
}
