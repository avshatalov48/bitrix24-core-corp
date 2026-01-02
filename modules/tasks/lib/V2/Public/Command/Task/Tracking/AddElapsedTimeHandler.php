<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Tracking;

use Bitrix\Tasks\V2\Internal\Entity\Task\ElapsedTime;
use Bitrix\Tasks\V2\Internal\Service\Task\Trait\ApplicationErrorTrait;
use Bitrix\Tasks\V2\Internal\Exception\Task\ElapsedTimeException;
use Bitrix\Tasks\V2\Internal\Service\Task\ElapsedTimeService;

class AddElapsedTimeHandler
{
	use ApplicationErrorTrait;

	public function __construct(
		private readonly ElapsedTimeService $elapsedTimeService,
	)
	{

	}

	public function __invoke(AddElapsedTimeCommand $command): ElapsedTime
	{
		[$id, ] = $this->elapsedTimeService->add($command->elapsedTime);

		if ($id === null)
		{
			throw new ElapsedTimeException($this->getApplicationError());
		}

		return $command->elapsedTime->cloneWith(['id' => $id]);
	}
}