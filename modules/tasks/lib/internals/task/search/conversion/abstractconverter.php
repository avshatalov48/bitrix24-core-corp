<?php

namespace Bitrix\Tasks\Internals\Task\Search\Conversion;

use Bitrix\Tasks\Internals\Task\Search\Exception\SearchIndexException;
use Bitrix\Tasks\Internals\Task\Search\RepositoryInterface;

abstract class AbstractConverter
{
	use UserTrait;

	protected array $task;

	/** @throws SearchIndexException */
	public function __construct(protected RepositoryInterface $repository)
	{
		$this->task = $this->repository->getTask();
	}

	public function convert(): string
	{
		$value = $this->getFieldValue();
		return is_string($value) ? $value : '';
	}

	public function getFieldValue(): mixed
	{
		return $this->task[static::getFieldName()];
	}

	abstract public static function getFieldName(): string;
}