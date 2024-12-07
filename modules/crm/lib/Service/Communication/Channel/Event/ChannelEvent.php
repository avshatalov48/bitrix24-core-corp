<?php

namespace Bitrix\Crm\Service\Communication\Channel\Event;

use Bitrix\Crm\Service\Communication\Channel\Channel;
use Bitrix\Crm\Service\Communication\Channel\ChannelFactory;
use Bitrix\Crm\Service\Communication\Channel\Property\Property;
use Bitrix\Main\ArgumentException;

final class ChannelEvent
{
	public function __construct(
		private readonly Channel $channel,
		private readonly string $eventId,
		private readonly ChannelEventPropertiesCollection $propertiesCollection
	)
	{
		$handlerClassInstance = ChannelFactory::getInstance()->getChannelHandlerInstance($this->channel);
		if (!$handlerClassInstance)
		{
			throw new ArgumentException('Module ' . $this->channel->getModuleId() . ' not loaded');
		}

		$handlerPropertiesCollection = $handlerClassInstance->getPropertiesCollection();

		foreach ($this->propertiesCollection as $property)
		{
			/**  @var Property $property */
			$code = $property->getCode();
			if (!$handlerPropertiesCollection->hasProperty($code))
			{
				$this->propertiesCollection->remove($code);
			}
		}
	}

	public function getChannel(): Channel
	{
		return $this->channel;
	}

	public function getPropertiesCollection(): ChannelEventPropertiesCollection
	{
		return $this->propertiesCollection;
	}

	public function getEventId(): string
	{
		return $this->eventId;
	}

	public function toArray(): array
	{
		return [
			'eventId' => $this->eventId,
			'channel' => $this->channel->toArray(),
			'propertiesCollection' => $this->propertiesCollection->toArray(),
		];
	}
}
