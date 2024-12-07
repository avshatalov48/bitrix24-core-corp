<?php

namespace Bitrix\Sign\Item\Integration\Im\Messages\Done;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Integration\Im\Message;

class FromReviewer extends Message
{
	public function __construct(
		int $fromUser,
		int $toUser,
		Document $document,
	)
	{
		parent::__construct($fromUser, $toUser);
		$this->document = $document;
	}

	public function getStageId(): string
	{
		return 'doneFromReviewer';
	}

	public function getFallbackText(): string
	{
		return $this->getLocalizedFallbackMessage(
			'SIGN_CALLBACK_CHAT_DONE_FROM_REVIEWER_MSGVER_1',
			[
				'#DOC_NAME#' => $this->getDocumentName($this->getDocument()),
			]
		);
	}
}
