<?php

namespace Bitrix\Sign\Item\Integration\Im\Messages\Done;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Integration\Im\Message;

class ToEmployeeGoskey extends Message
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
		return 'doneEmployeeGosKey';
	}

	public function getFallbackText(): string
	{
		return $this->getLocalizedFallbackMessage(
			'SIGN_CALLBACK_CHAT_DONE_EMPLOYEE_GOS_KEY_MSGVER_1',
			[
				'#DOC_NAME#' => $this->getDocumentName($this->getDocument()),
				'#SIGN_URL#' => $this->getLink(),
			]
		);
	}
}
