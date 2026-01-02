<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Yandex\Dto\Validator;

use Bitrix\Booking\Internals\Service\Yandex\Dto\Item;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class Company
{
	public const ERROR_CODE_EMPTY_ID = 'EMPTY_ID';
	public const ERROR_CODE_EMPTY_NAME = 'EMPTY_NAME';
	public const ERROR_CODE_EMPTY_ADDRESS = 'EMPTY_ADDRESS';
	public const ERROR_CODE_EMPTY_SERVICES = 'EMPTY_SERVICES';
	public const ERROR_CODE_EMPTY_RUBRICS = 'EMPTY_RUBRICS';

	public function validate(Item\Company $company): Result
	{
        $result = new Result();

		if ($company->getId() === null)
		{
            $result->addError(
                new Error(
                    'Company id is not specified.',
                    self::ERROR_CODE_EMPTY_ID
                )
            );
		}

		if ($company->getName() === null)
		{
            $result->addError(
                new Error(
                    'Company name is not specified.',
                    self::ERROR_CODE_EMPTY_NAME
                )
            );
		}

		if ($company->getAddress() === null)
		{
            $result->addError(
                new Error(
                    'Company address is not specified.',
                    self::ERROR_CODE_EMPTY_ADDRESS
                )
            );
		}

		if ($company->getServices()->isEmpty())
		{
            $result->addError(
                new Error(
                    'Services are not specified.',
                    self::ERROR_CODE_EMPTY_SERVICES
                )
            );
		}

		if (empty($company->getRubrics()))
		{
            $result->addError(
                new Error(
                    'Rubrics are not specified.',
                    self::ERROR_CODE_EMPTY_RUBRICS
                )
            );
		}

		return $result;
	}
}
