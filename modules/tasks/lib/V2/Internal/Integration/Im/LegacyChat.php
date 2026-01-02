<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Im;

use Bitrix\Main\Loader;
use Bitrix\Im\V2\Chat\ChatFactory;

class LegacyChat
{
	public function getTaskChatId(int $taskId): ?int
	{
		if (!Loader::includeModule('im'))
		{
			return null;
		}

		$chat = ChatFactory::getInstance()->getEntityChat(
			entityType: 'TASKS',
			entityId: (string)$taskId,
		);

		return $chat->getId();
	}
}
