<?php

namespace Bitrix\Tasks\Internals\Notification;

interface ProviderInterface
{
	public function addMessage(Message $message): void;
	public function pushMessages(): void;
}