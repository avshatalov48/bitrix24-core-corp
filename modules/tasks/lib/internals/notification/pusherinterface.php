<?php

namespace Bitrix\Tasks\Internals\Notification;

interface PusherInterface
{
	public function push(ProviderCollection $notifications): void;
}