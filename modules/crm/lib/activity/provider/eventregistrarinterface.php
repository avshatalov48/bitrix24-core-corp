<?php

namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\Service\Communication\Channel\Event\ChannelEventRegistrar;
use Bitrix\Main\Result;

interface EventRegistrarInterface
{
	public function createActivityFromChannelEvent(ChannelEventRegistrar $eventRegistrar): Result;
}
