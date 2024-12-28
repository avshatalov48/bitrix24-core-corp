<?php

namespace Bitrix\Sign\Item\Integration\Im\Messages\GroupChat\B2b;

final class SigningStartedMessage extends BaseGroupChatMessage
{
	public function getText(): string
	{
		return $this->getTranslatedTextByCode(
			'SIGN_IM_GROUP_CHAT_MESSAGE_SIGNING_STARTED',
			[
				'#DOC_NAME#' => $this->getDocumentName(),
			],
		);
	}

	public function getBannerId(): string
	{
		return self::SIGNING_STARTED_BANNER_ID;
	}
}