<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Attention;

use Bitrix\Tasks\V2\Internal\Service\Task\UserOptionService;

class PinTaskHandler
{
	public function __construct(
		private readonly UserOptionService $userOptionService,
	)
	{

	}

	public function __invoke(PinTaskCommand $command): void
	{
		$this->userOptionService->pin(
			taskId: $command->taskId,
			userId: $command->userId,
		);
	}
}
