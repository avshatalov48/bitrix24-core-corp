<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Im;

use Bitrix\Tasks\V2\Internal\Entity\Task;

interface ChatNotificationInterface
{
	public function notify(NotificationType $type, Task $task, array $args = []): void;
}