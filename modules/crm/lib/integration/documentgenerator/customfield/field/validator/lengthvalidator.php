<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\CustomField\Field\Validator;

final class LengthValidator implements ValidatorInterface
{
	private int $minLength;
	private int $maxLength;
	private string $errorMessage;

	public function __construct(int $minLength, int $maxLength, string $errorMessage = '')
	{
		$this->minLength = $minLength;
		$this->maxLength = $maxLength;

		$this->errorMessage = $errorMessage ?: "Length must be between {$minLength} and {$maxLength} characters";
	}

	public function validate(mixed $value): bool
	{
		$length = mb_strlen((string) $value);

		return $length >= $this->minLength && $length <= $this->maxLength;
	}

	public function getErrorMessage(): string
	{
		return $this->errorMessage;
	}

	public function toArray(): array
	{
		return [
			'minLength' => $this->minLength,
			'maxLength' => $this->maxLength,
		];
	}
}
