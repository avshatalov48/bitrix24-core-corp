<?php

namespace Bitrix\Crm\Integration\Analytics;

use Bitrix\Crm\Integration\Analytics\Builder\Entity\ConvertEvent;

final class ConvertEventsContainer {

	/** @var ConvertEvent[] */
	private array $container = [];

	public function __construct(private readonly ConvertEvent $prefilledEvent)
	{
	}

	public function getPrefilledConvertEvent(): ConvertEvent
	{
		return clone($this->prefilledEvent);
	}

	public function addEvent(?ConvertEvent $event): void
	{
		if ($event)
		{
			$this->container[$this->getEventHash($event)] = $event;
		}
	}

	public function setErrorStatus(): void
	{
		foreach ($this->container as $event)
		{
			$event->setStatus(Dictionary::STATUS_ERROR);
		}
	}

	public function submitEvents(): void
	{
		foreach ($this->container as $event)
		{
			$event->buildEvent()->send();
		}
	}

	private function getEventHash(ConvertEvent $event): string
	{
		return hash('md5', serialize($event));
	}
}