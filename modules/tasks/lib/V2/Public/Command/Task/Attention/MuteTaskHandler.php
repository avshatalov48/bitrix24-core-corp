<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Attention;

use Bitrix\Tasks\V2\Internal\Service\Task\UserOptionService;

class MuteTaskHandler
{
	public function __construct(
		private readonly UserOptionService $userOptionService,
	)
	{

	}

	public function __invoke(MuteTaskCommand $command): void
	{
		$this->userOptionService->mute(
			taskId: $command->taskId,
			userId: $command->userId,
		);
	}
}
