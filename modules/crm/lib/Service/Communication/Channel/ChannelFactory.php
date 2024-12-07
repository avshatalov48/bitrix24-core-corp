<?php

namespace Bitrix\Crm\Service\Communication\Channel;

use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Loader;

final class ChannelFactory
{
	use Singleton;

	public function getChannelHandlerInstance(Channel $channel): ?ChannelInterface
	{
		if (Loader::includeModule($channel->getModuleId()))
		{
			return $channel->getHandlerClass()::createInstance($channel->getCode());
		}

		return null;
	}
}
