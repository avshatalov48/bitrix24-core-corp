<?php

namespace Bitrix\Sign\Item\Integration\Im\Messages\Failure;

use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Integration\Im\Message;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Type\User\Gender;

class EmployeeStoppedToCompany extends Message\WithInitiator
{
	public function __construct(
		int $fromUser,
		int $toUser,
		int $initiatorUserId,
		string $initiatorName,
		Gender $initiatorGender,
		Document $document,
		Member $member,
		string $link,
	)
	{
		parent::__construct($fromUser, $toUser, $initiatorUserId, $initiatorName, $initiatorGender);
		$this->document = $document;
		$this->member = $member;
		$this->link = $link;
	}

	public function getStageId(): string
	{
		// employeeStoppedToCompany — old message
		return match ($this->getInitiatorGender())
		{
			Gender::MALE => 'employeeStoppedToCompanyV2M',
			Gender::FEMALE => 'employeeStoppedToCompanyV2F',
			default => 'employeeStoppedToCompanyV2',
		};
	}

	public function getFallbackText(): string
	{
		$messageId = match ($this->getInitiatorGender())
		{
			Gender::MALE => 'SIGN_CALLBACK_CHAT_EMPLOYEE_STOPPED_TO_COMPANYM_MSGVER_2',
			Gender::FEMALE => 'SIGN_CALLBACK_CHAT_EMPLOYEE_STOPPED_TO_COMPANYF_MSGVER_2',
			default  => 'SIGN_CALLBACK_CHAT_EMPLOYEE_STOPPED_TO_COMPANY_MSGVER_2',
		};

		return $this->getLocalizedFallbackMessage(
			$messageId,
			[
				'#DOC_NAME#' => $this->getDocumentName($this->getDocument()),
				'#SIGNER_NAME#' => $this->getMemberName($this->getMember()),
				'#INITIATOR_NAME#' => $this->getInitiatorName(),
				'#GRID_URL#' => $this->getLink(),
			]
		);
	}
}
