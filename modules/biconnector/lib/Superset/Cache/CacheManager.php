<?php

namespace Bitrix\BIConnector\Superset\Cache;

use Bitrix\BIConnector\Integration\Superset\Integrator\ProxyIntegrator;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Result;

final class CacheManager
{
	public const OPTION_LAST_CLEAR_TIME = 'superset_last_clear_cache_time';
	public const OPTION_ATTEMPT_NUMBER = 'superset_clear_cache_attempt_number';

	private static self $instance;

	private function __construct()
	{
	}

	public static function getInstance(): self
	{
		if (!isset(self::$instance))
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function clear(): Result
	{
		$result = new Result();

		$integrator = ProxyIntegrator::getInstance();
		$response = $integrator->clearCache();
		if ($response->hasErrors())
		{
			$result->addErrors($response->getErrors());
		}

		if ($result->isSuccess())
		{
			$this->onAfterClear();
		}

		return $result;
	}

	/**
	 * Sets number of attempt and timestamp of last cache clearing.
	 *
	 * @return void
	 */
	public function onAfterClear(): void
	{
		$lastClear = $this->getLastClearTimestamp();
		if (time() - $lastClear > 60 * 60)
		{
			$this->setClearAttemptNumber(0);
		}

		$this->setLastClearTimestamp();

		$currentAttemptNumber = $this->getClearAttemptNumber();
		if ($currentAttemptNumber < 4)
		{
			$this->setClearAttemptNumber($currentAttemptNumber + 1);
		}
	}

	public function canClearCache(): bool
	{
		return $this->getNextClearTimeout() < 0;
	}

	public function setLastClearTimestamp(?int $timestamp = null): void
	{
		if (!$timestamp)
		{
			$timestamp = time();
		}
		Option::set('biconnector', self::OPTION_LAST_CLEAR_TIME, $timestamp);
	}

	public function setClearAttemptNumber(int $attempt): void
	{
		Option::set('biconnector', self::OPTION_ATTEMPT_NUMBER, $attempt);
	}

	public function getLastClearTimestamp(): int
	{
		return (int)Option::get('biconnector', self::OPTION_LAST_CLEAR_TIME);
	}

	public function getClearAttemptNumber(): int
	{
		return (int)Option::get('biconnector', self::OPTION_ATTEMPT_NUMBER);
	}

	/**
	 * Returns time interval in seconds to next activating clear cache button.
	 * If negative - timeout is gone.
	 * @return int
	 */
	public function getNextClearTimeout(): int
	{
		$lastClear = $this->getLastClearTimestamp();
		$attempt = $this->getClearAttemptNumber();
		$delay = match ($attempt)
		{
			1 => 5 * 60,
			2 => 10 * 60,
			3 => 20 * 60,
			default => 40 * 60,
		};

		return $lastClear + $delay - time();
	}
}
