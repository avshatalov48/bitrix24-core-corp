<?php

namespace Bitrix\Crm\Integration\AI\ContextCollector;

final class Context
{
	public function __construct(
		private readonly int $userId,
	)
	{
	}

	public function userId(): int
	{
		return $this->userId;
	}
}
