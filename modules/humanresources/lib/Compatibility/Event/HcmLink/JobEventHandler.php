<?php

namespace Bitrix\HumanResources\Compatibility\Event\HcmLink;

use Bitrix\Main\Event;

class JobEventHandler
{
	public static function onUpdateDoneJob(Event $event): void
	{
		\Bitrix\Main\Loader::includeModule('pull');

		/** @var \Bitrix\HumanResources\Item\HcmLink\Job $job */
		$job = $event->getParameter('job');
		\CPullWatch::AddToStack('humanresources_person_mapping', [
			'module_id' => 'humanresources',
			'command' => 'external_employee_list_updated',
			'params' => [
				'jobId' => $job->id,
				'status' => $job->status->value,
				'finishedAt' => $job->finishedAt
			],
		]);
	}
}