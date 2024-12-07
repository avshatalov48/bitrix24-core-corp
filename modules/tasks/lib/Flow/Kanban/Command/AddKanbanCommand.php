<?php

namespace Bitrix\Tasks\Flow\Kanban\Command;

use Bitrix\Tasks\AbstractCommand;
use Bitrix\Tasks\Internals\Attribute\PositiveNumber;

/**
 * @method self setProjectId(int $projectId)
 * @method self setOwnerId(int $ownerId)
 * @method self setFlowId(int $flowId)
 */
class AddKanbanCommand extends AbstractCommand
{
	#[PositiveNumber]
	public int $projectId;

	#[PositiveNumber]
	public int $ownerId;

	#[PositiveNumber]
	public int $flowId;
}