<?php

declare(strict_types=1);

namespace Bitrix\Booking\Integration\Booking;

interface ProviderInterface
{
	public function getModuleId(): string;

	public function getClientProvider(): ?ClientProviderInterface;

	public function getDataProvider(): ?DataProviderInterface;
}
