<?php

namespace Bitrix\Sign\Item\Integration\Im\Messages\Failure;

use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Integration\Im\Message;
use Bitrix\Sign\Type\User\Gender;

class RefusedToCompany extends Message\WithInitiator
{
	public function __construct(
		int $fromUser,
		int $toUser,
		Document $document,
		int $initiatorUserId,
		string $initiatorName,
		Gender $initiatorGender,
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
			Gender::MALE => 'refusedCompanyV2M',
			Gender::FEMALE => 'refusedCompanyV2F',
			default => 'refusedCompanyV2',
		};
	}

	public function getFallbackText(): string
	{
		$messageId = match ($this->getInitiatorGender())
		{
			Gender::MALE => 'SIGN_CALLBACK_CHAT_REFUSED_COMPANYM_MSGVER_2',
			Gender::FEMALE => 'SIGN_CALLBACK_CHAT_REFUSED_COMPANYF_MSGVER_2',
			default => 'SIGN_CALLBACK_CHAT_REFUSED_COMPANY_MSGVER_2',
		};

		return $this->getLocalizedFallbackMessage(
			$messageId,
			[
				'#DOC_NAME#' => $this->getDocumentName($this->getDocument()),
				'#INITIATOR_NAME#' => $this->getInitiatorName(),
				'#GRID_URL#' => $this->getLink(),
			]
		);
	}
}
