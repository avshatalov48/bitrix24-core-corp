<?php

namespace Bitrix\Crm\Integration\AI\Function\UserField;

use Bitrix\Crm\Integration\AI\Contract\AIFunction;
use Bitrix\Crm\Integration\AI\Function\UserField\Boolean\CreateBoolean;
use Bitrix\Crm\Integration\AI\Function\UserField\Date\CreateMultipleDate;
use Bitrix\Crm\Integration\AI\Function\UserField\Date\CreateSingleDate;
use Bitrix\Crm\Integration\AI\Function\UserField\DateTime\CreateMultipleDateTime;
use Bitrix\Crm\Integration\AI\Function\UserField\DateTime\CreateSingleDateTime;
use Bitrix\Crm\Integration\AI\Function\UserField\Double\CreateMultipleDouble;
use Bitrix\Crm\Integration\AI\Function\UserField\Double\CreateSingleDouble;
use Bitrix\Crm\Integration\AI\Function\UserField\Dto\UniversalCreateUserFieldParameters;
use Bitrix\Crm\Integration\AI\Function\UserField\Enum\UserFieldType;
use Bitrix\Crm\Integration\AI\Function\UserField\Enumeration\CreateMultipleEnumeration;
use Bitrix\Crm\Integration\AI\Function\UserField\Enumeration\CreateSingleEnumeration;
use Bitrix\Crm\Integration\AI\Function\UserField\File\CreateMultipleFile;
use Bitrix\Crm\Integration\AI\Function\UserField\File\CreateSingleFile;
use Bitrix\Crm\Integration\AI\Function\UserField\Integer\CreateMultipleInteger;
use Bitrix\Crm\Integration\AI\Function\UserField\Integer\CreateSingleInteger;
use Bitrix\Crm\Integration\AI\Function\UserField\String\CreateMultipleString;
use Bitrix\Crm\Integration\AI\Function\UserField\String\CreateSingleString;
use Bitrix\Crm\Result;

final class UniversalCreateUserField implements AIFunction
{
	public function __construct(
		private readonly int $currentUserId,
	)
	{
	}

	public function isAvailable(): bool
	{
		return true;
	}

	public function invoke(...$args): Result
	{
		$parameters = new UniversalCreateUserFieldParameters($args);
		if ($parameters->hasValidationErrors())
		{
			return Result::fail($parameters->getValidationErrors());
		}

		return $this
			->getCreateUserFieldFunction($parameters)
			->invoke(...$parameters->toCreateUserFieldParameters());
	}

	private function getCreateUserFieldFunction(UniversalCreateUserFieldParameters $parameters): AbstractCreateUserField
	{
		$type = UserFieldType::from($parameters->type);
		$isMultiple = $parameters->isMultiple;

		return $isMultiple
			? $this->getCreateMultipleUserFieldFunction($type)
			: $this->getCreateSingleUserFieldFunction($type);
	}

	private function getCreateMultipleUserFieldFunction(UserFieldType $type): AbstractCreateUserField
	{
		return match ($type) {
			UserFieldType::Double => new CreateMultipleDouble($this->currentUserId),
			UserFieldType::Integer => new CreateMultipleInteger($this->currentUserId),
			UserFieldType::String => new CreateMultipleString($this->currentUserId),
			UserFieldType::Date => new CreateMultipleDate($this->currentUserId),
			UserFieldType::DateTime => new CreateMultipleDateTime($this->currentUserId),
			UserFieldType::Enumeration => new CreateMultipleEnumeration($this->currentUserId),
			UserFieldType::File => new CreateMultipleFile($this->currentUserId),
			UserFieldType::Boolean => new CreateBoolean($this->currentUserId),
		};
	}

	private function getCreateSingleUserFieldFunction(UserFieldType $type): AbstractCreateUserField
	{
		return match ($type) {
			UserFieldType::Double => new CreateSingleDouble($this->currentUserId),
			UserFieldType::Integer => new CreateSingleInteger($this->currentUserId),
			UserFieldType::String => new CreateSingleString($this->currentUserId),
			UserFieldType::Date => new CreateSingleDate($this->currentUserId),
			UserFieldType::DateTime => new CreateSingleDateTime($this->currentUserId),
			UserFieldType::Enumeration => new CreateSingleEnumeration($this->currentUserId),
			UserFieldType::File => new CreateSingleFile($this->currentUserId),
			UserFieldType::Boolean => new CreateBoolean($this->currentUserId),
		};
	}
}
