<?php declare(strict_types = 1);

namespace Bitrix\ImOpenLines\Controller;

use \Bitrix\ImOpenLines\Controller;

const STATUS_COMPLETED = 'COMPLETED';
const STATUS_PROGRESS = 'PROGRESS';

/**
 * Stepper
 */
trait Stepper
{
	/** @var Timer */
	private $timer;

	/** @var int */
	private $totalItems = 0;

	/** @var int */
	private $processedItems = 0;

	/** @var boolean */
	private $isProcessCompleted = false;

	/**
	 * Append action answer with process attributes.
	 *
	 * @param array $actionResult Result answer.
	 *
	 * @return array
	 */
	protected function preformProcessAnswer(array $actionResult = []): array
	{
		if ($this->totalItems > 0)
		{
			$actionResult['TOTAL_ITEMS'] = $this->totalItems;
			$actionResult['PROCESSED_ITEMS'] = $this->processedItems;
		}

		if ($this->hasErrors())
		{
			$actionResult['STATUS'] = Controller\STATUS_COMPLETED;
		}
		elseif ($this->hasProcessCompleted())
		{
			$actionResult['STATUS'] = Controller\STATUS_COMPLETED;
		}
		else
		{
			$actionResult['STATUS'] = Controller\STATUS_PROGRESS;
		}

		return $actionResult;
	}

	/**
	 * Declares total count of items will be processed.
	 *
	 * @param int $totalItems Total number of items.
	 *
	 * @return void
	 */
	public function declareTotalItems(int $totalItems): void
	{
		$this->totalItems = $totalItems;
	}

	/**
	 * Declares count of processed items.
	 *
	 * @param int $processedItems Number of items.
	 *
	 * @return void
	 */
	public function declareProcessedItems(int $processedItems): void
	{
		$this->processedItems = $processedItems;
	}

	/**
	 * Increments count of processed items.
	 *
	 * @param int $incrementItems Number of items.
	 *
	 * @return void
	 */
	public function incrementProcessedItems(int $incrementItems): void
	{
		$this->processedItems += $incrementItems;
	}

	/**
	 * Switch accomplishment flag of the process.
	 *
	 * @return void
	 */
	public function declareProcessDone(): void
	{
		$this->isProcessCompleted = true;
	}

	/**
	 * Tells true if process has completed.
	 *
	 * @return boolean
	 */
	public function hasProcessCompleted(): bool
	{
		return $this->isProcessCompleted;
	}


	/**
	 * Getting array of errors.
	 * @return boolean
	 */
	public function hasErrors(): bool
	{
		/**
		 * @var \Bitrix\Main\Engine\Controller $this
		 */
		if ($this->errorCollection instanceof \Bitrix\Main\ErrorCollection)
		{
			return $this->errorCollection->isEmpty() !== true;
		}

		return false;
	}

	/**
	 * Gets timer.
	 *
	 * @return Timer
	 */
	public function instanceTimer(): Timer
	{
		if (!($this->timer instanceof Timer))
		{
			$this->timer = new Timer();
		}

		return $this->timer;
	}

	/**
	 * Sets start up time.
	 *
	 * @return void
	 */
	public function startTimer(): void
	{
		$this->instanceTimer()->startTimer((int)START_EXEC_TIME);
	}

	/**
	 * Tells true if time limit reached.
	 *
	 * @return boolean
	 */
	public function hasTimeLimitReached(): bool
	{
		return $this->instanceTimer()->hasTimeLimitReached();
	}
}
