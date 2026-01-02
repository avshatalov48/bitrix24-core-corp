<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Exception\Yandex;

use Bitrix\Booking\Internals\Exception\Exception;

abstract class YandexException extends Exception
{
	abstract public function getExternalCode(): string;
}
