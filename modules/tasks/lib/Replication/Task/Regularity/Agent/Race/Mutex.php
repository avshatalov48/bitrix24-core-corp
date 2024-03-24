<?php

namespace Bitrix\Tasks\Replication\Task\Regularity\Agent\Race;

use Bitrix\Main\Application;
use Bitrix\Main\Data\ManagedCache;

class Mutex
{
	private const TTL = 1800;
	private const LOCKED = 1;
	private const CACHE_NAME = 'tasks_regularity_notification_mutex';


	private ManagedCache $cache;

	public function __construct(private string $name = self::CACHE_NAME)
	{
		$this->init();
	}

	private function init(): void
	{
		$this->cache = Application::getInstance()->getManagedCache();
	}

	public function lock(): bool
	{
		if ($this->cache->read(static::TTL, $this->name))
		{
			$value = $this->cache->get($this->name);
		}

		if (!empty($value))
		{
			return false;
		}

		$this->cache->setImmediate($this->name, static::LOCKED);

		return true;
	}

	/**
	 * @return bool
	 */
	public function unlock(): bool
	{
		$this->cache->clean($this->name);
		return true;
	}
}