<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add;

use Bitrix\Tasks\V2\Internal\Entity\HistoryLog;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;
use Bitrix\Tasks\V2\Internal\Service\Task\Trait\OccurredUserTrait;

class AddHistoryLog
{
	use ConfigTrait;
	use OccurredUserTrait;

	public function __invoke(array $fields): void
	{
		$logRepository = Container::getInstance()->getTaskLogRepository();

		$log = new HistoryLog(
			userId: $this->getOccurredUserId($this->config->getUserId()),
			taskId: (int)$fields['ID'],
			field: 'NEW'
		);

		$logRepository->add($log);
	}
}