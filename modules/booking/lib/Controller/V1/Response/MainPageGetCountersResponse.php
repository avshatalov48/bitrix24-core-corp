<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1\Response;

class MainPageGetCountersResponse implements \JsonSerializable
{
	public function __construct(
		public readonly int $totalClients,
		public readonly int $totalClientsToday,
		public readonly array $counters = [],
		public readonly array $moneyStatistics = [],
	)
	{
	}

	public function jsonSerialize(): array
	{
		return [
			'clientStatistics' => [
				'total' => $this->totalClients,
				'totalToday' => $this->totalClientsToday,
			],
			'moneyStatistics' => $this->moneyStatistics,
			'counters' => $this->counters,
		];
	}
}
