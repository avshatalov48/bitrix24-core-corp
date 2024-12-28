<?php

namespace Bitrix\Sign\Result;

use Bitrix\Main;
use Bitrix\Main\Error;

class Result extends Main\Result
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

	public function getLastError(): ?\Bitrix\Main\Error
	{
		$errors = $this->getErrors();

		return array_pop($errors);
	}

	public function getFirstError(): ?\Bitrix\Main\Error
	{
		$errors = $this->getErrors();

		return array_shift($errors);
	}

	public function addErrorsFromResult(Main\Result $result): self
	{
		$this->addErrors($result->getErrors());

		return $this;
	}

	final public static function createWithErrors(Error ...$errors): self
	{
		$result = new self();
		$result->addErrors($errors);

		return $result;
	}

	final public static function createByErrorData(string $message, string|int $code = 0): self
	{
		$result = new self();
		$result->addError(new Error($message, $code));

		return $result;
	}

	final public static function createByMainResult(Main\Result $result): self
	{
		return static::createWithErrors(...$result->getErrors())
			->setData($result->getData())
		;
	}

	final public static function createByErrorMessage(string $message): self
	{
		return static::createByErrorData($message);
	}
}