<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Esg;

use Bitrix\Tasks\V2\Internal\Entity\Task;

class IngressController
{
	public function enrichWithChatMessages(Task $task, int $offset = 0, int $limit = 50): self
	{
		// todo:
		// $messages = $chatIntegration->tail($task->getId(), $offset, $limit);
		// $task->appendChatMessages($messages);

		return $this;
	}

	public function enrichWithFiles(Task $task): self
	{
		// todo:
		// $files = $diskIntegration->getFiles($task->getId());
		// $task->setFiles($files);

		return $this;
	}
}
