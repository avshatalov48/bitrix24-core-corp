<?php

namespace Bitrix\Crm\Copilot\AiQueueBuffer\Provider;

use Bitrix\Main\Result;

interface QueueBufferProviderInterface
{
	public static function getId(): int;
	public function process(?array $data = null): Result;
}
