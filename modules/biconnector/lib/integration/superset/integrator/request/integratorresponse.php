<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator\Request;

use Bitrix\Main\Error;
use Bitrix\Main\Result;

/** @template T */
final class IntegratorResponse
{
	public const STATUS_OK = 200;
	public const STATUS_CREATED = 201;
	public const STATUS_IN_PROGRESS = 202;
	public const STATUS_NO_ACCESS = 403;
	public const STATUS_NOT_FOUND = 404;
	public const STATUS_REGISTER_REQUIRED = 470;
	public const STATUS_SERVER_ERROR = 500;
	public const STATUS_FROZEN = 555;
	public const STATUS_UNKNOWN = 520;
	public const STATUS_INNER_ERROR = 499;

	private int $status;

	/** @var Error[] */
	private array $errors;

	/** @var T|null */
	private mixed $data;

	private null|Result $requestResult = null;

	public function __construct(int $status = 0, $data = null, array $errors = [])
	{
		$this->status = $status;
		$this->data = $data;
		$this->errors = $errors;
	}

	/** @return T|null */
	public function getData()
	{
		return $this->data;
	}

	public function getRequestResult(): null|Result
	{
		return $this->requestResult;
	}

	public function setRequestResult(null|Result $requestResult): self
	{
		$this->requestResult = $requestResult;

		return $this;
	}

	/**
	 * @param T|null $data
	 * @return static
	 */
	public function setData($data): self
	{
		$this->data = $data;
		return $this;
	}

	public function getStatus(): int
	{
		return $this->status === 0 ? self::STATUS_UNKNOWN : $this->status;
	}

	public function setStatus(mixed $status): self
	{
		$this->status = (int)$status;
		return $this;
	}

	public function addError(Error ...$error): self
	{
		$this->errors = [
			...$this->errors,
			...$error,
		];

		return $this;
	}

	public function hasErrors(): bool
	{
		return count($this->errors) > 0;
	}

	/**
	 * @return Error[]
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}
}
