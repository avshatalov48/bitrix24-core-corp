<?php

namespace Bitrix\Crm\Service\Communication\Channel\Event;

final class ChannelEventPropertiesCollection
{
	private array $eventProperties;

	public function __construct(array $eventParams)
	{
		foreach ($eventParams as $eventParam)
		{
			$this->eventProperties[$eventParam->getCode()] = $eventParam;
		}
	}

	public function getByCode(string $code): ?ChannelEventParam
	{
		return $this->eventProperties[$code] ?? null;
	}

	public function has(string $code): bool
	{
		return isset($this->eventProperties[$code]);
	}

	/**
	 * @return ChannelEventParam[]
	 */
	public function getEventProperties(): array
	{
		return $this->eventProperties;
	}

	public function append(ChannelEventParam $channelEventParam): void
	{
		$this->eventProperties[$channelEventParam->getCode()] = $channelEventParam;
	}

	public function remove(string $code): void
	{
		unset($this->eventProperties[$code]);
	}

	// @todo maybe remove
	public function toArray(): array
	{
		$result = [];
		foreach ($this->eventProperties as $property)
		{
			$result[] = $property->toArray();
		}

		return $result;
	}
}
