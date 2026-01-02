<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Entity\User\Field;

use Bitrix\Main\PhoneNumber\Parser;

class PhoneField extends SingleField
{
	public function __construct(
		public readonly string $id,
		public readonly string $title,
		public readonly bool $isEditable,
		public readonly bool $isShowAlways,
		public readonly mixed $value = '',
	)
	{
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getValue(): string
	{
		return $this->isValid() ? $this->value : '';
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function isEditable(): bool
	{
		return $this->isEditable;
	}

	public function isShowAlways(): bool
	{
		return $this->isShowAlways;
	}

	public function isValid(mixed $value = null): bool
	{
		$value ??= $this->value;

		if (!is_string($value))
		{
			return false;
		}

		if (empty($value))
		{
			return true;
		}

		$phoneNumber = Parser::getInstance()->parse($value);

		return $phoneNumber->isValid();
	}
}
