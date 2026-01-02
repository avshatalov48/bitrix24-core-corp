<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Factory\Entity;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Rest\V1\Factory\Event\DatePeriod;
use Bitrix\Booking\Rest\V1\Factory\EntityFactory;
use Bitrix\Main\Result;

class Booking extends EntityFactory
{
	private DatePeriod $datePeriodFactory;
	private Resource $resourceFactory;

	public function __construct()
	{
		$this->datePeriodFactory = new DatePeriod();
		$this->resourceFactory = new Resource();
	}

	public function validateRestFields(array $fields): Result
	{
		$validationResult = new Result();

		if (isset($fields['RESOURCE_IDS']))
		{
			$resourceIdsValidationResult =
				$this
					->resourceFactory
					->validateResourceIds($fields['RESOURCE_IDS'])
			;
			if (!$resourceIdsValidationResult->isSuccess())
			{
				return $resourceIdsValidationResult;
			}
		}

		if (isset($fields['DATE_PERIOD']))
		{
			$datePeriodValidationResult =
				$this
					->datePeriodFactory
					->validateRestFields($fields['DATE_PERIOD'])
			;
			if (!$datePeriodValidationResult->isSuccess())
			{
				return $datePeriodValidationResult;
			}
		}

		return $validationResult;
	}

	public function createFromRestFields(
		array $fields,
		int $userId = 0,
		?Entity\Booking\Booking $booking = null,
	): Entity\Booking\Booking
	{
		if (!$booking)
		{
			$booking = new Entity\Booking\Booking();
		}

		$resourceCollection = $this->resourceFactory->createCollectionFromResourceIds($fields['RESOURCE_IDS']);
		$booking->setResourceCollection($resourceCollection);

		$datePeriod = $this->datePeriodFactory->createFromRestFields($fields['DATE_PERIOD']);
		$booking->setDatePeriod($datePeriod);

		if (isset($fields['NAME']))
		{
			$booking->setName((string)$fields['NAME']);
		}

		if (isset($fields['DESCRIPTION']))
		{
			$booking->setDescription((string)$fields['DESCRIPTION']);
		}

		return $booking;
	}
}
