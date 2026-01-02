<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\CustomField\Field\Validator;

final class RequiredValidator implements ValidatorInterface
{
	private string $errorMessage;

	public function __construct(string $errorMessage = '')
	{
		$this->errorMessage = $errorMessage ?: 'This field is required';
	}

	public function validate(mixed $value): bool
	{
		return !empty($value);
	}

	public function getErrorMessage(): string
	{
		return $this->errorMessage;
	}

	public function toArray(): array
	{
		return [
			'required' => true,
		];
	}
}
