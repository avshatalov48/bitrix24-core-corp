<?php

namespace Bitrix\Crm\Traits;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Application;

/**
 * @internal
 *
 * Use this trait to make a service that will defer some processing to the background, after the response was sent.
 * Call `ensureProcessingScheduled` whenever you want to schedule the processing, e.g. when adding a job to a queue
 * or in __construct.
 * Implement `process` method to do the actual job.
 *
 * Note that `process` can run several times if processing was scheduled again after the first run.
 */
trait BackgroundProcessor
{
	// do not overwrite or modify in runtime! it should be a const really, but traits can have consts only after php 8.2
	private static int $MAX_PROCESSING_RUNS_COUNT = 10;
	// todo php 8.2
	// private const MAX_PROCESSING_RUNS_COUNT = 10;

	private bool $isScheduled = false;
	private int $processingRunsCount = 0;

	/**
	 * Call whenever you want to schedule the processing, e.g., when adding a job to a queue or in __construct.
	 *
	 * Can be called safely multiple times.
	 */
	final protected function ensureProcessingScheduled(): void
	{
		if ($this->isScheduled)
		{
			return;
		}

		if ($this->isPossibleRecursionDetected())
		{
			return;
		}

		Application::getInstance()->addBackgroundJob(fn() => $this->runProcessing(), [], $this->getPriority());

		$this->isScheduled = true;
	}

	protected function getPriority(): int
	{
		return Application::JOB_PRIORITY_NORMAL;
	}

	private function runProcessing(): void
	{
		$this->isScheduled = false;

		if ($this->isPossibleRecursionDetected())
		{
			return;
		}

		$this->process();

		$this->processingRunsCount++;
	}

	private function isPossibleRecursionDetected(): bool
	{
		if ($this->processingRunsCount > self::$MAX_PROCESSING_RUNS_COUNT)
		{
			Container::getInstance()->getLogger('Default')->critical(
				'{class}: background processing has ran too many times. Possible infinite recursion. Aborting',
				[
					'class' => static::class,
				]
			);

			return true;
		}

		return false;
	}

	/**
	 * Do the actual work. Note that this method can be run several times if processing was scheduled
	 * again after the first run.
	 */
	abstract protected function process(): void;
}
