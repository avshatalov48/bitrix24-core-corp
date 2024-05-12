<?php

namespace Bitrix\Intranet\License\Notification;

interface NotificationProvider
{
	public function isAvailable(): bool;
	public function checkNeedToShow(): bool;
	public function getConfiguration(): array;
}