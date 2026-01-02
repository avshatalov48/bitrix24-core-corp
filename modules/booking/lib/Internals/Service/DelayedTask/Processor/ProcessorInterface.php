<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\DelayedTask\Processor;

interface ProcessorInterface
{
	public function __invoke(): void;
}
