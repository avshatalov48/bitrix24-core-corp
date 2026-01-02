<?php

declare(strict_types=1);

namespace Bitrix\Crm\Dto\Booking\Booking;

enum BookingStatusEnum: string
{
	case ConfirmedByClient = 'confirmedByClient';
	case ConfirmedByManager = 'confirmedByManager';
	case ComingSoon = 'comingSoon';
	case DelayedCounterActivated = 'delayedCounterActivated';
	case CanceledByClient = 'canceledByClient';
	case ConfirmCounterActivated = 'confirmCounterActivated';

	public function supportLogMessage(): bool
	{
		return in_array(
			$this,
			[
				self::ConfirmedByClient,
				self::ConfirmedByManager,
				self::ComingSoon,
				self::CanceledByClient,
			],
			true,
		);
	}

	public function supportActivityStatusUpdate(): bool
	{
		return in_array(
			$this,
			[
				self::ConfirmedByClient,
				self::ConfirmedByManager,
				self::ComingSoon,
				self::DelayedCounterActivated,
				self::CanceledByClient,
			],
			true,
		);
	}

	public function supportToDoActivity(): bool
	{
		return in_array(
			$this,
			[
				self::DelayedCounterActivated,
				self::ConfirmCounterActivated,
			],
			true,
		);
	}
}
