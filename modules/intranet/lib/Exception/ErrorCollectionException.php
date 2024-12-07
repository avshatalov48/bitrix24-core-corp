<?php

namespace Bitrix\Intranet\Exception;

use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;

abstract class ErrorCollectionException extends \Exception
{
	protected ErrorCollection $errors;

	public function __construct(?ErrorCollection $errors = null)
	{
		$this->errors = $errors instanceof ErrorCollection ? $errors : new ErrorCollection();
		parent::__construct($this->errorCollectionToString());
	}

	public function setErrors(ErrorCollection $errors): static
	{
		$this->errors = $errors;
		$this->message = $this->errorCollectionToString();

		return $this;
	}

	public function addError(Error $error): static
	{
		$this->errors[] = $error;
		$this->message .= $error->getMessage(). "\r\n";

		return $this;
	}

	public function getErrors(): ErrorCollection
	{
		return $this->errors;
	}

	protected function errorCollectionToString(): string
	{
		$message = '';
		foreach ($this->errors as $error)
		{
			$message .= $error->getMessage(). "\r\n";
		}

		return $message;
	}
}