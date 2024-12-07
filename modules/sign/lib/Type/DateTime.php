<?php

namespace Bitrix\Sign\Type;

use Bitrix\Main;

class DateTime extends Main\Type\DateTime
{
	public function withTime($hour, $minute, $second = 0, $microseconds = 0): static
	{
		$clone = clone $this;
		$clone->setTime($hour, $minute, $second, $microseconds);

		return $clone;
	}

	public static function createFromMainDateTime(Main\Type\DateTime $dateTime): static
	{
		return static::createFromPhp($dateTime->value);
	}

	public static function createFromMainDateTimeOrNull(?Main\Type\DateTime $dateTime): ?static
	{
		if ($dateTime === null)
		{
			return null;
		}

		return static::createFromMainDateTime($dateTime);
	}

	public function withAdd($interval): static
	{
		$clone = clone $this;
		$clone->add($interval);

		return $clone;
	}

	public function withAddSeconds(int $seconds): static
	{
		$clone = clone $this;
		$clone->add(($seconds < 0 ? '-' : '') . 'T' . abs($seconds) . 'S');

		return $clone;
	}

	public function withDate($year, $month, $day): static
	{
		$clone = clone $this;
		$clone->setDate($year, $month, $day);

		return $clone;
	}

	public function withTimeZone(\DateTimeZone $timezone): static
	{
		$clone = clone $this;
		$clone->setTimezone($timezone);

		return $clone;
	}

	public function getDayOfWeek(): int
	{
		return (int)$this->format('N');
	}

	public static function createFromFormat(string $format, string $dateTime): self
	{
		return self::createFromPhp(\DateTime::createFromFormat($format, $dateTime));
	}

	public function toPhp(): \DateTime
	{
		return clone $this->value;
	}

	public function clone(): static
	{
		return clone $this;
	}
}