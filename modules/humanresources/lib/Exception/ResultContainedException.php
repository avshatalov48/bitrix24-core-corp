<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Exception;

use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Error;

abstract class ResultContainedException extends \Exception
{
	protected ErrorCollection $errors;

	public function __construct($message = "", $code = 0, \Throwable $previous = null)
	{
		$this->errors = new ErrorCollection();

		parent::__construct($message, $code, $previous);
	}

	public function setErrors(ErrorCollection $errors): static
	{
		$this->errors = $errors;

		return $this;
	}

	public function addError(Error $error): static
	{
		$this->errors->add([$error]);
		$this->message .= $error->getMessage() . '. ';

		return $this;
	}

	public function getErrors(): ErrorCollection
	{
		return $this->errors;
	}
}