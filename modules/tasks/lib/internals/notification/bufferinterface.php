<?php

namespace Bitrix\Tasks\Internals\Notification;

interface BufferInterface
{
	public function addProvider(ProviderInterface $provider): void;
	public function flush(): ProviderCollection;
}