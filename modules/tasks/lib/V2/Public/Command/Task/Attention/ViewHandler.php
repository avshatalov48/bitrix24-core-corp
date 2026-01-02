<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Attention;

use Bitrix\Tasks\V2\Internal\Entity\Task\View;
use Bitrix\Tasks\V2\Internal\Service\Task\ViewService;

class ViewHandler
{
	public function __construct(
		private readonly ViewService $viewService,
	)
	{

	}

	public function __invoke(ViewCommand $command): void
	{
		$view = new View(
			taskId: $command->taskId,
			userId: $command->userId,
			viewedTs: $command->viewedTs ?? time(),
			isRealView: $command->isRealView,
		);

		$this->viewService->set($view, $command->sendPush, $command->updateTopicLastVisit);
	}
}