<?php

namespace Bitrix\Tasks\Integration\CRM\Timeline;

interface BackGroundJob
{
	public function addToBackgroundJobs(
		array $payload,
		string $endpoint = '',
		int $priority = 0,
	): void;
}