<?php

namespace Bitrix\HumanResources\Result;

use Bitrix\Main\Error;
use Bitrix\HumanResources\Exception\Result\InvalidResultUseException;

class SuccessResult extends Result
{
	public function getData(): array
	{
		$result = [];

		$class = new \ReflectionClass(static::class);
		$properties = $class->getProperties(\ReflectionProperty::IS_PUBLIC);
		foreach ($properties as $property)
		{
			$result[$property->getName()] = $property->getValue($this);
		}

		return $result;
	}

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