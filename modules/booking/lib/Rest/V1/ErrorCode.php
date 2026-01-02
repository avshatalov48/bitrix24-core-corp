<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1;

use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Error;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;

abstract class ErrorCode
{
	public static function getInvalidArgumentError(string $field): Error
	{
		$field = self::convertToSnakeCase($field);

		return ErrorBuilder::build(
			"Invalid value of the $field field",
			Exception::CODE_INVALID_ARGUMENT,
		);
	}

	public static function getRequiredFieldsError(array $fields): Error
	{
		$fieldsString = implode(', ', $fields);

		return ErrorBuilder::build(
			message: "Required fields: $fieldsString",
		);
	}

	public static function convertToSnakeCase(string $value): string
	{
		return (new Converter(Converter::TO_CAMEL | Converter::LC_FIRST))->process($value);
	}
}
