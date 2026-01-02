<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Processor\Task\AutoCloser;

class AutoClose
{
	use ConfigTrait;

	public function __invoke(array $fields, array $fullTaskData): void
	{
		if (
			!array_key_exists('STATUS', $fields)
			|| (int)$fields['STATUS'] !== Status::COMPLETED
		)
		{
			return;
		}

		if (!$this->config->isNeedAutoclose())
		{
			return;
		}

		$taskId = (int)$fullTaskData['ID'];

		$closer = AutoCloser::getInstance($this->config->getUserId());
		$closeResult = $closer->processEntity($taskId, $fields);
		if ($closeResult->isSuccess())
		{
			$closeResult->save(['!ID' => $taskId]);
		}
	}
}