<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Im;

use Bitrix\Tasks\V2\Internal\Entity\Task;

interface MessageSenderInterface
{
	public function sendMessage(Task $task, string|null $text): void;
}