<?php

namespace Bitrix\Sign\Result;

use Bitrix\Main\Error;
use Bitrix\Sign\Exception\Result\InvalidResultUseException;

abstract class SuccessResult extends Result
{
	public function addError(Error $error)
	{
		$this->throwInvalidResultUseException();
	}

	public function addErrors(array $errors)
	{
		$this->throwInvalidResultUseException();
	}

	public function getErrorCollection()
	{
		$this->throwInvalidResultUseException();
	}

	public function getErrorMessages()
	{
		$this->throwInvalidResultUseException();
	}

	public function getErrors()
	{
		$this->throwInvalidResultUseException();
	}

	/**
	 * @throws InvalidResultUseException
	 */
	private function throwInvalidResultUseException(): void
	{
		throw new InvalidResultUseException('Success result can not contain errors');
	}
}