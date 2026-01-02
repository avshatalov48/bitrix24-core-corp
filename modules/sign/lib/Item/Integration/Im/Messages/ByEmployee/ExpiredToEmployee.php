<?php

namespace Bitrix\Sign\Item\Integration\Im\Messages\ByEmployee;

use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Integration\Im\Message;
use Bitrix\Sign\Type\User\Gender;

class ExpiredToEmployee extends Message\WithInitiator\ByEmployee
{
	public function __construct(
		int $fromUser,
		int $toUser,
		int $initiatorUserId,
		string $initiatorName,
		Gender $initiatorGender,
		Document $document,
		string $link,
	)
	{
		parent::__construct($fromUser, $toUser, $initiatorUserId, $initiatorName, $initiatorGender);
		$this->document = $document;
		$this->link = $link;
	}

	public function getStageId(): string
	{
		return 'byEmployeeExpiredToEmployee';
	}

	public function getFallbackText(): string
	{
		return $this->getLocalizedFallbackMessage(
			'SIGN_CALLBACK_BY_EMPLOYEE_CHAT_EXPIRED_TO_EMPLOYEE',
			[
				'#DOC_NAME#' => $this->getDocumentName($this->getDocument()),
				'#SIGN_URL#' => $this->getLink(),
				'#INITIATOR_NAME#' => $this->getInitiatorName(),
			]
		);
	}
}
