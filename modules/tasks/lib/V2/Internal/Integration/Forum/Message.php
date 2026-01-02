<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Forum;

use Bitrix\Forum\MessageTable;
use Bitrix\Main\Loader;

class Message
{
	public function hasTopicComments(int $topicId): bool
	{
		if (!Loader::includeModule('forum'))
		{
			return false;
		}

		// Check if there is at least one message for this topic
		$count = MessageTable::getCount([
			'=TOPIC_ID' => $topicId,
		]);

		return $count > 0;
	}
}
