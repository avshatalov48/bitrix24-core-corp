<?php

namespace Bitrix\HumanResources\Exception;

use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Error;

abstract class ResultContainedException extends \Exception
{
	protected ErrorCollection $errors;

	public function __construct()
	{
		$this->errors = new ErrorCollection();
		parent::__construct();
	}

	public function setErrors(ErrorCollection $errors): static
	{
		$this->errors = $errors;

		return $this;
	}

	public function addError(Error $error): static
	{
		$this->errors[] = $error;
		$this->message .= $error->getMessage() . '. ';

		return $this;
	}

	public function getErrors(): ErrorCollection
	{
		return $this->errors;
	}
}