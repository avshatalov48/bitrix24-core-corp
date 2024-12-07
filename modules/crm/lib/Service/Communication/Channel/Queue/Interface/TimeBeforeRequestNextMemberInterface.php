<?php

namespace Bitrix\Crm\Service\Communication\Channel\Queue\Interface;

use Bitrix\Crm\Service\Communication\Channel\Event\ChannelEventRegistrar;

interface TimeBeforeRequestNextMemberInterface
{
	/**
	 * @return int[]
	 */
	public function getTimeOffsetVariants(): array;
	public function onSetNextMember(ChannelEventRegistrar $eventRegistrar): void;
}
