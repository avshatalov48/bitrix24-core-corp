<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Provider;

class AdviceData
{
	public function __construct(
		public readonly int $flowId,
		public readonly string $factor = '',
		public readonly string $advice = '',
	)
	{
	}
}
