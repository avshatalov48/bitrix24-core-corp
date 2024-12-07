<?php

namespace Bitrix\Tasks\Flow\Kanban;

use Bitrix\Tasks\Flow\Control\AbstractCommand;

class KanbanCommand extends AbstractCommand
{
	public function __construct(
		public readonly int $projectId,
		public readonly int $ownerId,
	)
	{

	}
}