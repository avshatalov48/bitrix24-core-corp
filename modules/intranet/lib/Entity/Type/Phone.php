<?php

namespace Bitrix\Intranet\Entity\Type;

use Bitrix\Main\PhoneNumber\Parser;
use \Bitrix\Main\PhoneNumber\Format;

class Phone
{
	public function __construct(
		private readonly string $phoneNumber,
		private readonly ?string $countryCode = null
	){}

	public function format(string $format): string
	{
		$phoneNumber = Parser::getInstance()->parse($this->phoneNumber, $this->countryCode ?? "");
		if ($phoneNumber->isValid())
		{
			return $phoneNumber->format($format);
		}

		return $this->phoneNumber;
	}

	public function defaultFormat(): string
	{
		return $this->format(Format::E164);
	}

	public function getCountryCode(): ?string
	{
		return $this->countryCode;
	}

	public function getRawNumber(): string
	{
		return $this->phoneNumber;
	}

	public function isValid(): bool
	{
		$phoneNumber = Parser::getInstance()->parse($this->phoneNumber, $this->countryCode ?? "");

		return $phoneNumber->isValid();
	}
}