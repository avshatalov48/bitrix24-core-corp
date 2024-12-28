<?php

namespace Bitrix\Sign\Item\Integration\Im\Messages\ByEmployee;

use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Integration\Im\Message;
use Bitrix\Sign\Type\User\Gender;

class StoppedToEmployee extends Message\WithInitiator\ByEmployee
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
		return match ($this->getInitiatorGender())
		{
			Gender::MALE => 'byEmployeeStoppedToEmployeeM',
			Gender::FEMALE => 'byEmployeeStoppedToEmployeeF',
			default => 'byEmployeeStoppedToEmployee',
		};
	}

	public function getFallbackText(): string
	{
		$messageId = match ($this->getInitiatorGender())
		{
			Gender::MALE => 'SIGN_CALLBACK_BY_EMPLOYEE_CHAT_STOPPED_TO_EMPLOYEEM',
			Gender::FEMALE => 'SIGN_CALLBACK_BY_EMPLOYEE_CHAT_STOPPED_TO_EMPLOYEEF',
			default  => 'SIGN_CALLBACK_BY_EMPLOYEE_CHAT_STOPPED_TO_EMPLOYEE',
		};

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
