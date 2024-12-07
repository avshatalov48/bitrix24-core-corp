<?php

namespace Bitrix\Sign\Item\Integration\Im\Messages\Done;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Integration\Im\Message;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Service\Sign\DocumentService;
use Bitrix\Sign\Service\Sign\MemberService;

class AllSignedToCompany extends Message
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
		return 'doneCompany';
	}

	public function getFallbackText(): string
	{
		return $this->getLocalizedFallbackMessage(
			'SIGN_CALLBACK_CHAT_DONE_COMPANY_MSGVER_2',
			[
				'#DOC_NAME#' => $this->getDocumentName($this->getDocument()),
				'#SAFE_URL#' => $this->getLink(),
			]
		);
	}
}
