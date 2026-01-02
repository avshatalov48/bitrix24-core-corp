<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1\BookingService\Yandex;

use Bitrix\Booking\Controller\V1\BookingService\BaseReceiver;
use Bitrix\Booking\Controller\V1\BookingService\Yandex\Filter\BookingDisabled;
use Bitrix\Booking\Internals\Exception\Yandex\YandexException;
use Bitrix\Main\Error;

class BaseController extends BaseReceiver
{
	protected function getDefaultPreFilters(): array
	{
		return [
			...parent::getDefaultPreFilters(),
			new BookingDisabled(),
		];
	}

	protected function handle(callable $fn): mixed
	{
		try
		{
			return $fn();
		}
		catch (\Throwable $exception)
		{
			if ($exception instanceof YandexException)
			{
				$this->addError(
					new Error(
						$exception->getMessage(),
						$exception->getExternalCode(),
					)
				);

				return null;
			}

			$this->addError(new Error($exception->getMessage()));

			return null;
		}
	}
}
