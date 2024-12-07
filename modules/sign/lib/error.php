<?php
namespace Bitrix\Sign;

/**
 * @deprecated
 */
class Error
{
	/**
	 * Current errors.
	 * @var array
	 */
	private $errors = [];

	/**
	 * Instance of this class.
	 * @var Error | null
	 */
	private static $instance = null;

	/**
	 * Class constructor. Not for direct access.
	 */
	private function __construct()
	{
	}

	/**
	 * Returns single instance of the class.
	 * @return Error
	 */
	public static function getInstance(): Error
	{
		if (!self::$instance)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Clears current error collection.
	 * @return void
	 */
	public function clear(): void
	{
		$this->errors = [];
	}

	/**
	 * Adds error to the current collection.
	 * @param string $code Error code.
	 * @param string|null $message Error message.
	 * @return void
	 */
	public function addError(string $code, ?string $message = null): void
	{
		$this->errors[] = new \Bitrix\Main\Error($message ?: $code, $code);
	}

	/**
	 * Adds error instance to the current collection.
	 * @param \Bitrix\Main\Error $error Error instance.
	 * @return void
	 */
	public function addErrorInstance(\Bitrix\Main\Error $error): void
	{
		$this->errors[] = $error;
	}

	/**
	 * Returns errors collection.
	 * @return \Bitrix\Main\Error[]
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

	/**
	 * Returns first error from collection if exists.
	 * @return \Bitrix\Main\Error
	 */
	public function getFirstError(): ?\Bitrix\Main\Error
	{
		return !empty($this->errors) ? $this->errors[0] : null;
	}

	/**
	 * Returns error instance by code.
	 * @param string $code Error code.
	 * @return \Bitrix\Main\Error|null
	 */
	public function getErrorByCode(string $code): ?\Bitrix\Main\Error
	{
		foreach ($this->errors as $error)
		{
			if ($error->getCode() === $code)
			{
				return $error;
			}
		}

		return null;
	}

	/**
	 * Returns errors collection as array.
	 * @return array
	 */
	public function getErrorsAsArray(): array
	{
		$errors = [];

		/** @var \Bitrix\Main\Error $error */
		foreach ($this->errors as $error)
		{
			$errors[] = [
				'code' => $error->getCode(),
				'message' => $error->getMessage()
			];
		}

		return $errors;
	}

	/**
	 * Exists or not any errors.
	 * @return bool
	 */
	public function isEmpty(): bool
	{
		return empty($this->errors);
	}

	/**
	 * Collects errors from result.
	 * @param mixed $result Result.
	 * @return void
	 */
	public function addFromResult($result): void
	{
		if (
			(
				$result instanceof \Bitrix\Main\Result ||
				$result instanceof \Bitrix\Main\Entity\AddResult ||
				$result instanceof \Bitrix\Main\Entity\UpdateResult ||
				$result instanceof \Bitrix\Main\Entity\DeleteResult
			) && !$result->isSuccess()
		)
		{
			foreach ($result->getErrors() as $error)
			{
				$this->addError(
					$error->getCode(),
					$error->getMessage()
				);
			}
		}
	}
}
