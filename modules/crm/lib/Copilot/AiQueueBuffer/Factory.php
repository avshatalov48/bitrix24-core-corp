<?php

namespace Bitrix\Crm\Copilot\AiQueueBuffer;

use Bitrix\Crm\Copilot\AiQueueBuffer\Provider\FillRepeatSaleTipsProvider;
use Bitrix\Crm\Copilot\AiQueueBuffer\Provider\QueueBufferProviderInterface;

final class Factory
{
	public static function getProvider(int $providerId): ?QueueBufferProviderInterface
	{
		return match ($providerId)
		{
			FillRepeatSaleTipsProvider::getId() => new FillRepeatSaleTipsProvider(),
			default => null
		};
	}
}
