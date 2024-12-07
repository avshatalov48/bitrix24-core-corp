<?php

namespace Bitrix\Sign\Item\Api;

use Bitrix\Main;
use Bitrix\Sign\Contract;

abstract class Response implements Contract\Item
{
	private array $errors = [];

	public function isSuccess(): bool
	{
		return empty($this->errors);
	}

	public function addError(Main\Error $error): self
	{
		$this->errors[] = $error;
		return $this;
	}

	/**
	 * @param Main\Error[] $errors
	 * @return $this
	 */
	public function addErrors(array $errors): self
	{
		$this->errors = array_merge($this->errors, $errors);
		return $this;
	}

	/**
	 * @return Main\Error[]
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

	public function createResult(): Main\Result
	{
		return (new Main\Result())->addErrors($this->getErrors());
	}
}