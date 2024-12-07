<?php

namespace Bitrix\Tasks\Flow\Kanban\Command;

use Bitrix\Tasks\AbstractCommand;
use Bitrix\Tasks\Internals\Attribute\PositiveNumber;

/**
 * @method self setProjectId(int $projectId)
 * @method self setFlowId(int $flowId)
 * @method self setOwnerId(int $ownerId)
 */
class ReinstallFlowRobotsCommand extends AbstractCommand
{
	#[PositiveNumber]
	public int $flowId;

	#[PositiveNumber]
	public int $projectId;

	#[PositiveNumber]
	public int $ownerId;
}