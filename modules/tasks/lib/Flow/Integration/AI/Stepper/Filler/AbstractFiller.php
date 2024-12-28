<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Stepper\Filler;

use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Integration\AI\Configuration;
use Bitrix\Tasks\Flow\Integration\AI\Registry;
use Bitrix\Tasks\Flow\Integration\AI\Result\CollectorResult;

abstract class AbstractFiller
{
	protected const SECONDS_IN_HOUR = 60 * 60;
	protected const SECONDS_IN_DAY = 60 * 60 * 24;

	protected CollectorResult $result;
	protected Registry $registry;
	protected Flow $flow;
	protected array $employees = [];

	public function __construct(Registry $registry)
	{
		$this->registry = $registry;

		$this->init();
	}

	abstract public function fill(CollectorResult $result): void;

	abstract protected function init(): void;

	protected function getDateInterval(): int
	{
		return
			($this->flow->getPlannedCompletionTime() >= self::SECONDS_IN_DAY)
				? self::SECONDS_IN_DAY
				: self::SECONDS_IN_HOUR
			;
	}

	protected function formatUserIdForNode(int $userId): string
	{
		$userPrefix = Configuration::getUserPrefix();

		return $userPrefix . $userId;
	}
}
