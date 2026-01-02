<?php

declare(strict_types=1);

namespace Bitrix\Crm\Dto\Booking;

use Bitrix\Main\Type\Contract\Arrayable;

class DatePeriod implements Arrayable
{
	public function __construct(
		public readonly int $from,
		public readonly int $to,
		public readonly string|null $fromTimezone = null,
		public readonly string|null $toTimezone = null,
	)
	{
	}

	public static function mapFromArray(array $params): self
	{
		return new self(
			from: $params['from'],
			to: $params['to'],
			fromTimezone: $params['fromTimezone'] ?? null,
			toTimezone: $params['toTimezone'] ?? null,
		);
	}

	public function toArray(): array
	{
		return [
			'from' => $this->from,
			'to' => $this->to,
			'fromTimezone' => $this->fromTimezone,
			'toTimezone' => $this->toTimezone,
		];
	}
}
