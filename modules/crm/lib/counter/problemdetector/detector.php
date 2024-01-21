<?php

namespace Bitrix\Crm\Counter\ProblemDetector;

use Bitrix\Crm\Counter\ProblemDetector\Collector\Collector;
use Bitrix\Crm\Counter\ProblemDetector\Collector\CountableCompletedCollector;
use Bitrix\Crm\Counter\ProblemDetector\Collector\CountableDeletedCollector;
use Bitrix\Crm\Counter\ProblemDetector\Collector\LightCounterCompletedCollector;
use Bitrix\Crm\Counter\ProblemDetector\Collector\UncompletedCompletedCollector;
use Bitrix\Crm\Counter\ProblemDetector\Collector\UncompletedDeletedCollector;
use Bitrix\Main\Config\Option;

/**
 * Service to detect problems with counters log them and try to fix
 */
class Detector
{
	public const PROBLEM_TYPE_COUNTABLE_COMPLETED = 'countable_completed';
	public const PROBLEM_TYPE_COUNTABLE_DELETED = 'countable_deleted';
	public const PROBLEM_TYPE_UNCOMPLETED_COMPLETED = 'uncompleted_completed';
	public const PROBLEM_TYPE_UNCOMPLETED_DELETED = 'uncompleted_deleted';
	public const PROBLEM_TYPE_LIGHTCOUNTER_COMPLETED = 'lightcounter_completed';

	/** @var Collector[] */
	private array $problemCollectors;

	private Recovery\Dispatcher $recovery;

	private Logger $logger;

	/**
	 * @param Collector ...$collectors list of problem that will be detected. If empty will run all.
	 */
	public function __construct(Collector ...$collectors)
	{
		$this->logger = Logger::getInstance();
		$this->recovery = Recovery\Dispatcher::getInstance();

		if (empty($collectors))
		{
			$this->problemCollectors = [
				new CountableCompletedCollector(),
				new CountableDeletedCollector(),
				new UncompletedCompletedCollector(),
				new UncompletedDeletedCollector(),
				new LightCounterCompletedCollector(),
			];
		}
		else
		{
			$this->problemCollectors = $collectors;
		}
	}

	public function execute(): void
	{
		if (!$this->isReadyToExecute())
		{
			return;
		}

		$start = microtime(true);

		$problemList = new ProblemList();
		foreach ($this->problemCollectors as $collector)
		{
			$problemList->add($collector->collect());
		}
		$duration = round(microtime(true) - $start, 10);

		if (!$problemList->hasAnyProblem())
		{
			return;
		}

		$this->logger->log($problemList, ['time' => $duration]);

		$this->recovery->execute($problemList);
	}

	private function isReadyToExecute(): bool
	{
		return Option::get('crm', 'enable_entity_countable_act', 'Y') === 'Y'
			&& Option::get('crm', 'enable_entity_uncompleted_act', 'Y') === 'Y';
	}
}
