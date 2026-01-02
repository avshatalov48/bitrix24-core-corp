<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Factory\Event;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Booking\Internals\Exception\InvalidArgumentException;
use Bitrix\Booking\Rest\V1\Factory\EventFactory;
use Bitrix\Main\Result;
use DateTimeImmutable;
use DateTimeZone;

class DatePeriod extends EventFactory
{
	public function validateRestFields(array $fields): Result
	{
		$validationResult = new Result();

		try
		{
			$this->createFromRestFields($fields);
		}
		catch (\Exception)
		{
			$validationResult->addError(
				ErrorBuilder::build(
					message: "Invalid date period",
					code: Exception::CODE_INVALID_ARGUMENT
				)
			);
		}

		return $validationResult;
	}

	/**
	 * @throws \DateMalformedStringException
	 * @throws \DateInvalidTimeZoneException
	 * @throws InvalidArgumentException
	 */
	public function createFromRestFields(
		array $fields,
	): Entity\DatePeriod
	{
		$dateFrom =
			(new DateTimeImmutable('@' . (int)$fields['FROM']['TIMESTAMP']))
				->setTimezone(new DateTimeZone((string)$fields['FROM']['TIMEZONE']));

		$dateTo =
			(new DateTimeImmutable('@' . (int)$fields['TO']['TIMESTAMP']))
				->setTimezone(new DateTimeZone((string)$fields['TO']['TIMEZONE']));

		return new Entity\DatePeriod($dateFrom, $dateTo);
	}
}
