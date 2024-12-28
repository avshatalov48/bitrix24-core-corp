<?php

namespace Bitrix\Sign\Item\Integration\Im\Messages\GroupChat\B2b;

final class SigningCompletedMessage extends BaseGroupChatMessage
{
	public function getText(): string
	{
		return $this->getTranslatedTextByCode(
			'SIGN_IM_GROUP_CHAT_MESSAGE_SIGNING_COMPLETED',
			[
				'#DOC_NAME#' => $this->getDocumentName(),
			],
		);
	}

	public function getBannerId(): string
	{
		return self::SIGNING_COMPLETED_BANNER_ID;
	}
}