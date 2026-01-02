<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation;

use Bitrix\Tasks\Integration\Forum\Task\Topic;

class DeleteTopics
{
	public function __invoke(array $fullTaskData): void
	{
		Topic::delete($fullTaskData["FORUM_TOPIC_ID"]);
	}
}