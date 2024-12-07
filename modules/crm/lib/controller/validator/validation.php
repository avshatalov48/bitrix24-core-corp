<?php

namespace Bitrix\Crm\Controller\Validator;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;

class Validation implements Errorable
{
	private ErrorCollection $errorCollection;
	private bool $isSuccess = true;

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection();
	}

	/**
	 * @param mixed $value
	 * @param Validator[] $validators
	 * @return $this
	 * @throws ArgumentException
	 */
	public function validate(mixed $value, array $validators): self
	{
		foreach ($validators as $i => $validator)
		{
			if (!($validator instanceof Validator))
			{
				$validatorClass = Validator::class;

				throw new ArgumentException("{$i} array element must implement {$validatorClass}", 'validators');
			}

			$result = $validator->validate($value);
			if (!$result->isSuccess())
			{
				$this->addErrors($result->getErrors());
			}
		}

		return $this;
	}

	public function isSuccess(): bool
	{
		return $this->isSuccess;
	}

	public function getErrors(): array
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code): ?Error
	{
		foreach ($this->errorCollection as $error)
		{
			if ($error->getCode() === $code)
			{
				return $error;
			}
		}

		return null;
	}

	private function addError(Error $error): self
	{
		$this->errorCollection->add([$error]);
		$this->isSuccess = false;

		return $this;
	}

	private function addErrors(array $errors): self
	{
		$this->errorCollection->add($errors);
		$this->isSuccess = false;

		return $this;
	}
}
