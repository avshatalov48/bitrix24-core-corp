<?php

namespace Bitrix\Tasks\Replication;

use Bitrix\Main\Application;
use Bitrix\Main\Data\ManagedCache;

abstract class AbstractMutex
{
	protected const LOCKED = 1;

	private ManagedCache $cache;

	private bool $isEnabled;

	abstract protected function getTTL(): int;
	abstract protected function getCacheName(): string;

	public function __construct(bool $isEnabled = true)
	{
		$this->isEnabled = $isEnabled;
		$this->init();
	}

	private function init(): void
	{
		$this->cache = Application::getInstance()->getManagedCache();
	}

	public function lock(): bool
	{
		if (!$this->isEnabled)
		{
			return true;
		}

		if ($this->cache->read($this->getTTL(), $this->getCacheName()))
		{
			$value = $this->cache->get($this->getCacheName());
		}

		if (!empty($value))
		{
			return false;
		}

		$this->cache->setImmediate($this->getCacheName(), static::LOCKED);

		return true;
	}

	public function unlock(): bool
	{
		$this->isEnabled && $this->cache->clean($this->getCacheName());
		return true;
	}
}