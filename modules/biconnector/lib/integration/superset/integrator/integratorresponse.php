<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator;

use Bitrix\Main\Error;

/** @template T */
abstract class IntegratorResponse
{
	public const STATUS_OK = 200;
	public const STATUS_CREATED = 201;
	public const STATUS_IN_PROGRESS = 202;
	public const STATUS_NO_ACCESS = 403;
	public const STATUS_NOT_FOUND = 404;
	public const STATUS_SERVER_ERROR = 500;
	public const STATUS_FROZEN = 555;
	public const STATUS_UNKNOWN = 0;

	protected int $status;

	/** @var Error[] */
	protected array $errors;

	/** @var T|null */
	protected mixed $data;

	public function __construct(int $status = 0, $data = null, array $errors = [])
	{
		$this->status = static::parseInnerStatus($status);
		$this->data = $data;
		$this->errors = [];
	}

	protected static function parseInnerStatus(mixed $status): int
	{
		$availableStatuses = [
			self::STATUS_OK,
			self::STATUS_CREATED,
			self::STATUS_NO_ACCESS,
			self::STATUS_IN_PROGRESS,
			self::STATUS_SERVER_ERROR,
			self::STATUS_FROZEN,
		];

		if (!in_array($status, $availableStatuses, true))
		{
			$status = self::STATUS_UNKNOWN;
		}

		return $status;
	}

	/** @return T|null */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * @param T|null $data
	 * @return static
	 */
	public function setData($data): static
	{
		$this->data = $data;
		return $this;
	}

	public function getStatus(): int
	{
		return $this->status;
	}

	public function setStatus(mixed $status): static
	{
		$this->status = $status;
		return $this;
	}

	public function setInnerStatus(mixed $status): static
	{
		$this->status = static::parseInnerStatus($status);
		return $this;
	}

	public function addError(Error ...$error): static
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