<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Stepper\Trait;

use Bitrix\Main\Update\Stepper;

trait StepperQueueTrait
{
	abstract private function getNext(): array;

	private function getDelay(): int
	{
		return 0;
	}

	private function runNext(): void
	{
		$next = $this->getNext();
		$stepper = $next['class'] ?? null;
		$args = $next['args'] ?? [];

		if (is_subclass_of($stepper, Stepper::class))
		{
			$stepper::bind($this->getDelay(), $args);
		}
	}
}
