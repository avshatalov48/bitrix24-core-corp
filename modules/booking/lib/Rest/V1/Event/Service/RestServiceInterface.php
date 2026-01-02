<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Event\Service;

interface RestServiceInterface
{
	public function getEvents(): array;
}
