<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Factory\Filter;

use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Booking\Rest\V1\Factory\FilterFactory;
use Bitrix\Main\Result;
use Bitrix\Main\Type\Date;

class CreatedWithin extends FilterFactory
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
					message: "Invalid date",
					code: Exception::CODE_INVALID_ARGUMENT
				)
			);
		}

		return $validationResult;
	}


	public function createFromRestFields(
		array $fields,
	): array
	{
		$createdWithin = [];

		$createdWithin['FROM'] = new Date($fields['FROM']);
		$createdWithin['TO']  = new Date($fields['TO']);

		return $createdWithin;
	}
}
