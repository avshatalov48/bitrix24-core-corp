<?php

declare(strict_types=1);


namespace Bitrix\Tasks\Onboarding\Transfer;

use ArrayIterator;
use IteratorAggregate;

/** @method QueueJob[] getIterator() */
final class QueueJobCollection implements IteratorAggregate
{
	/** @var QueueJob[] */
	private array $jobs = [];

	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->jobs);
	}

	public function add(QueueJob $job): void
	{
		$this->jobs[] = $job;
	}

	public function isEmpty(): bool
	{
		return empty($this->jobs);
	}
}