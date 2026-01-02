<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Internal\DI;

use Bitrix\Tasks\DI\AbstractContainer;
use Bitrix\Tasks\Flow\Kanban\KanbanService;
use Bitrix\Tasks\Flow\Migration\Access\Service\FlowAccessRightsService;

class Container extends AbstractContainer
{
	public function getFlowAccessRightsService(): FlowAccessRightsService
	{
		return $this->get(FlowAccessRightsService::class);
	}

	public function getKanbanService(): KanbanService
	{
		return $this->get(KanbanService::class);
	}
}
