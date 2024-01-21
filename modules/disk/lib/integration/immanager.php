<?php

namespace Bitrix\Disk\Integration;

use Bitrix\Main\Loader;

final class ImManager
{
	public function sendMessageToGeneralChat(int $fromUserId, array $params): bool
	{
		if (!Loader::includeModule('im'))
		{
			return false;
		}

		$chatId = \CIMChat::GetGeneralChatId();
		if (!$chatId)
		{
			return false;
		}

		$params = array_merge(
			$params,
			[
				'TO_CHAT_ID' => $chatId,
				'FROM_USER_ID' => $fromUserId,
				'SKIP_USER_CHECK' => 'Y',
			]
		);

		$result = \CIMChat::AddMessage($params);

		return $result !== false;
	}
}