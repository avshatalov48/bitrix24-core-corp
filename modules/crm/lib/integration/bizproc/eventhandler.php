<?php

namespace Bitrix\Crm\Integration\BizProc;

use Bitrix\Main\Event;
use Bitrix\Crm\Activity\Provider\Bizproc;

class EventHandler
{
	/**
	 * Event handler for onAfterWorkflowKill event.
	 * Deletes activities that were created by timeleine.
	 *
	 * @param Event $event Event data.
	 *
	 * @return void
	 */
	public static function onAfterWorkflowKill(Event $event): void
	{
		$workflowId = $event->getParameter('ID');

		$activities = \Bitrix\Crm\ActivityTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=ORIGIN_ID' => $workflowId,
				'=COMPLETED' => 'N',
				'@PROVIDER_ID' => [Bizproc\Comment::getId(), Bizproc\Task::getId(), Bizproc\Workflow::getId()]
			],
		])->fetchAll();

		foreach ($activities as $activity)
		{
			\CCrmActivity::Delete($activity['ID']);
		}
	}
}