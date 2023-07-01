<?php

namespace Bitrix\Tasks\Internals\Task\Placeholder\Placeholder;

use Bitrix\Tasks\Internals\Task\Placeholder\Exception\PlaceholderValidationException;

abstract class Placeholder
{
	protected $value;
	protected array $errors;

	/**
	 * @throws PlaceholderValidationException
	 */
	public function __construct($value)
	{
		$this->value = $value;
		$this->validate();
	}

	abstract public function toString(): string;

	/**
	 * @throws PlaceholderValidationException
	 */
	abstract protected function validate(): bool;
}