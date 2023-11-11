<?php

namespace Bitrix\Tasks\Integration\IM\Notification;

interface NotificationInterface
{
	public function getMessage(): string;
}