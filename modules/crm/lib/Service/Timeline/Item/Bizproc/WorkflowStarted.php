<?php

namespace Bitrix\Crm\Service\Timeline\Item\Bizproc;

use Bitrix\Main\Localization\Loc;

final class WorkflowStarted extends Base
{
	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_BIZPROC_STARTED_TITLE');
	}

	protected function getBizprocTypeId(): string
	{
		return 'WorkflowStarted';
	}

	public function getMenuItems(): array
	{
		$menuItems = [];
		$workflowId = $this->getModel()->getSettings()['WORKFLOW_ID'] ?? null;

		if (empty($workflowId))
		{
			return [];
		}

		$menuItems['timeline'] = $this->createTimelineMenuItem($workflowId);
		$menuItems['log'] = $this->createLogMenuItem($workflowId)?->setScopeWeb();
		$terminateMenuItem = $this->createTerminateMenuItem($workflowId);
		if (isset($terminateMenuItem))
		{
			$menuItems['terminate'] = $terminateMenuItem;
		}

		return $menuItems;
	}
}