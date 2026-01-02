<?php

namespace Bitrix\Crm\RepeatSale\DataCollector;

interface CopilotMarkerProviderInterface
{
	public function getMarkers(array $parameters = []): array;
}
