<?php

namespace Bitrix\Tasks\Integration\AI\event;

use Bitrix\Tasks\Integration\AI\Event\Message\Message;
use Bitrix\Tasks\Integration\AI\Event\Message\MessageCollection;

interface EventControllerInterface
{
	public function getOriginalMessage(): Message;
	public function getAdditionalMessages(): MessageCollection;
}
