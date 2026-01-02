<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Transfer;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Onboarding\Internal\Type;

final class QueueJob
{
	public function __construct(
		public readonly Type $type,
		public readonly int $taskId,
		public readonly int $userId,
		public readonly string $code,
		public readonly DateTime $nextExecution,
		public readonly ?int $jobCount = null,
		public readonly DateTime $createdDate = new DateTime(),
		public readonly bool $isProcessed = false,
		public readonly bool $isCountable = false
	)
	{

	}
}
