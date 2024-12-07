<?php

namespace Bitrix\Tasks\Flow\Grid\Preload;

use Bitrix\Tasks\Flow\Provider\AverageTimeProvider;
use Bitrix\Tasks\Flow\Time\DatePresenter;

class AverageTimePreloader
{
	protected static array $storage = [];

	private AverageTimeProvider $timeProvider;

	public function __construct()
	{
		$this->init();
	}

	final protected function load(int $status, int ...$flowIds): void
	{
		foreach ($flowIds as $flowId)
		{
			static::$storage[$flowId][$status] = DatePresenter::createFromSeconds(
				$this->timeProvider->getAverageTimeInStatus($flowId, $status)
			);
		}
	}

	protected function init(): void
	{
		$this->timeProvider = new AverageTimeProvider();
	}
}