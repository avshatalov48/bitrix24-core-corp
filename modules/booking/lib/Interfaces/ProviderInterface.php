<?php

declare(strict_types=1);

namespace Bitrix\Booking\Interfaces;

interface ProviderInterface
{
	public function getModuleId(): string;

	public function getClientProvider(): ClientProviderInterface|null;

	public function getDataProvider(): DataProviderInterface|null;
}
