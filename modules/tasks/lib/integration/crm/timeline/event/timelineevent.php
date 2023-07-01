<?php

namespace Bitrix\Tasks\Integration\CRM\Timeline\Event;

interface TimeLineEvent
{
	public function getPayload(): array;
	public function getEndpoint(): string;
	public function getPriority(): int;
}