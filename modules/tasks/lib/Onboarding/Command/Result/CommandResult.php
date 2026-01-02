<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Command\Result;

use Bitrix\Main\Result;

final class CommandResult extends Result
{
	private bool $isRetryAllowed = false;

	public function isRetryAllowed(): bool
	{
		return $this->isRetryAllowed;
	}

	public function allowRetry(bool $allow = true): void
	{
		$this->isRetryAllowed = true;
	}
}