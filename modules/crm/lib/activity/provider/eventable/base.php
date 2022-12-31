<?php

namespace Bitrix\Crm\Activity\Provider\Eventable;

use Bitrix\Crm\Traits;
use Bitrix\Main\EventManager;

abstract class Base
{
	use Traits\Singleton;

	/**
	 * @var array
	 */
	protected array $cache = [];

	abstract public function register(int $activityId): void;
	abstract public function unregister(int $activityId): void;
	abstract protected function getEventNamePrefix(): string;

	public function __construct()
	{
		$eventManager = EventManager::getInstance();
		$eventNamePrefix = $this->getEventNamePrefix();

		$eventManager->addEventHandler('crm', $eventNamePrefix . '::onAfterAdd', [$this, 'clearCache']);
		$eventManager->addEventHandler('crm', $eventNamePrefix . '::onAfterUpdate', [$this, 'clearCache']);
		$eventManager->addEventHandler('crm', $eventNamePrefix . '::onAfterDelete', [$this, 'clearCache']);
	}

	public function clearCache(): void
	{
		$this->cache = [];
	}
}
