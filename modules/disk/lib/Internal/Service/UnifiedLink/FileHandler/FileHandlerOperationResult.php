<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internal\Service\UnifiedLink\FileHandler;

use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use LogicException;
use Throwable;

class FileHandlerOperationResult
{
	private function __construct(
		private readonly ?string $value = null,
		private readonly ?ErrorCollection $errorCollection = null,
	)
	{
	}

	public static function createSuccess(string $value): static
	{
		return new static($value);
	}

	public static function createError(ErrorCollection $errorCollection): static
	{
		return new static(null, $errorCollection);
	}

	public static function createFromException(Throwable $e): static
	{
		$errorCollection = new ErrorCollection();
		$errorCollection->setError(new Error($e->getMessage(), $e->getCode()));
		return new static(null, $errorCollection);
	}

	public function isSuccess(): bool
	{
		return $this->errorCollection === null;
	}

	public function getValue(): string
	{
		if (!$this->isSuccess())
		{
			throw new LogicException('Cannot get value from error result');
		}

		return $this->value;
	}

	public function getErrorCollection(): ErrorCollection
	{
		if ($this->isSuccess())
		{
			throw new LogicException('Cannot get error collection from success result');
		}

		return $this->errorCollection;
	}
}