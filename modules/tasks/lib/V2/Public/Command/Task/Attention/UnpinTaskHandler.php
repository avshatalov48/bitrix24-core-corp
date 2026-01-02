<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Attention;

use Bitrix\Tasks\V2\Internal\Service\Task\UserOptionService;

class UnpinTaskHandler
{
	public function __construct(
		private readonly UserOptionService $userOptionService,
	)
	{

	}

	public function __invoke(UnpinTaskCommand $command): void
	{
		$this->userOptionService->unpin(
			taskId: $command->taskId,
			userId: $command->userId,
		);
	}
}
