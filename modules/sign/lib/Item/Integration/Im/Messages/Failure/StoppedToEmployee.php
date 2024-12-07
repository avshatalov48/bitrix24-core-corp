<?php

namespace Bitrix\Sign\Item\Integration\Im\Messages\Failure;

use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Integration\Im\Message;
use Bitrix\Sign\Type\User\Gender;

class StoppedToEmployee extends Message\WithInitiator
{
	public function __construct(
		int $fromUser,
		int $toUser,
		int $initiatorUserId,
		string $initiatorName,
		Gender $initiatorGender,
		Document $document,
	)
	{
		parent::__construct($fromUser, $toUser, $initiatorUserId, $initiatorName, $initiatorGender);
		$this->document = $document;
	}

	public function getStageId(): string
	{
		return match ($this->getInitiatorGender())
		{
			Gender::MALE => 'stoppedToEmployeeM',
			Gender::FEMALE => 'stoppedToEmployeeF',
			default => 'stoppedToEmployee',
		};
	}

	public function getFallbackText(): string
	{
		$messageId = match ($this->getInitiatorGender())
		{
			Gender::MALE => 'SIGN_CALLBACK_CHAT_STOPPED_TO_EMPLOYEEM',
			Gender::FEMALE => 'SIGN_CALLBACK_CHAT_STOPPED_TO_EMPLOYEEF',
			default  => 'SIGN_CALLBACK_CHAT_STOPPED_TO_EMPLOYEE',
		};

		return $this->getLocalizedFallbackMessage(
			$messageId,
			[
				'#DOC_NAME#' => $this->getDocumentName($this->getDocument()),
				'#INITIATOR_NAME#' => $this->getInitiatorName(),
			]
		);
	}
}
