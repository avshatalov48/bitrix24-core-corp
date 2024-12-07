<?php

namespace Bitrix\BizprocMobile\EntityEditor\Fields;

use Bitrix\Main\Type\DateTime;

class DatetimeField extends BaseField
{
	protected bool $isDateTimeField;

	public function __construct(array $property, mixed $value, array $documentType)
	{
		parent::__construct($property, $value, $documentType);

		$this->isDateTimeField = $property['Type'] === 'datetime';
	}

	public function getType(): string
	{
		return 'datetime';
	}

	public function getConfig(): array
	{
		return [
			'enableEditInView' => true,
			'enableTime' => $this->isDateTimeField,
		];
	}

	protected function convertToMobileType($value): string|int
	{
		$offset = \CTimeZone::GetOffset();

		if (
			\Bitrix\Bizproc\BaseType\Value\DateTime::isSerialized($value)
			&& preg_match(\Bitrix\Bizproc\BaseType\Value\Date::SERIALIZED_PATTERN, $value, $matches)
		)
		{
			$value = $matches[1];
			$offset = (int)$matches[2];
		}

		$timestamp =
			\CBPHelper::hasStringRepresentation($value)
				? $this->getTimestampFromDateString($value, $offset)
				: null
		;

		return $timestamp === null ? '' : $timestamp;
	}

	protected function convertToWebType($value): ?string
	{
		if (is_numeric($value))
		{
			$value = (int)$value;
			$date = new \Bitrix\Bizproc\BaseType\Value\DateTime($value, \CTimeZone::GetOffset());

			return $date->serialize();
		}

		return null;
	}

	protected function getTimestampFromDateString(string $date, int $offset = 0): ?int
	{
		if (DateTime::isCorrect($date))
		{
			$timestamp = (new DateTime($date))->getTimestamp();
			$timestamp -= $offset;

			return $timestamp;
		}

		return null;
	}
}
