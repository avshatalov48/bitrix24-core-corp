<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;
use Bitrix\Tasks\Helper\Analytics;

class SendAnalytics
{
	use ConfigTrait;

	public function __invoke(array $fields, bool $status = true): void
	{
		if (empty($fields['TASKS_ANALYTICS_SECTION']))
		{
			return;
		}

		$parentId = (int)($fields['PARENT_ID'] ?? null);
		$event = $parentId ? Analytics::EVENT['subtask_add'] : Analytics::EVENT['task_create'];
		$params = array_merge(
			$fields['TASKS_ANALYTICS_PARAMS'] ?? [],
			[
				'p3' => 'auditorsCount_' . count($fields['AUDITORS'] ?? []),
				'p5' => 'accomplicesCount_' . count($fields['ACCOMPLICES'] ?? []),
			]
		);

		Analytics::getInstance($this->config->getUserId())->onTaskCreate(
			$fields['TASKS_ANALYTICS_CATEGORY'] ?: Analytics::TASK_CATEGORY,
			$event,
			$fields['TASKS_ANALYTICS_SECTION'],
			$fields['TASKS_ANALYTICS_ELEMENT'] ?? null,
			$fields['TASKS_ANALYTICS_SUB_SECTION'] ?? null,
			$status,
			$params,
		);
	}
}