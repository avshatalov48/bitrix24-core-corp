<?php

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Async\Receiver\AddDavSync;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Async\Receiver\AddLastActivity;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Async\Receiver\AddScenario;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Async\Receiver\AddSearchIndex;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Async\Receiver\RecountSort;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Async\Receiver\UpdateDavSync;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Async\Receiver\UpdateSearchIndex;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Async\Receiver\UpdateTopic;
use Bitrix\Tasks\Flow\Kanban\Async\Receiver\AddStages;

return  [
	'value' => [
		'queues' => [
			/** @see QueueId */
			'add_task_dav_sync' => [
				'handler' => AddDavSync::class,
			],
			'add_task_search_index' => [
				'handler' => AddSearchIndex::class,
			],
			'add_task_last_activity' => [
				'handler' => AddLastActivity::class
			],
			'add_task_scenario' => [
				'handler' => AddScenario::class,
			],
			'update_task_dav_sync' => [
				'handler' => UpdateDavSync::class
			],
			'update_task_search_index' => [
				'handler' => UpdateSearchIndex::class,
			],
			'update_task_topic' => [
				'handler' => UpdateTopic::class
			],
			'recount_task_sort' => [
				'handler' => RecountSort::class
			],
			'add_flow_stages' => [
				'handler' => AddStages::class,
			],
		],
	],
	'readonly' => true,
];