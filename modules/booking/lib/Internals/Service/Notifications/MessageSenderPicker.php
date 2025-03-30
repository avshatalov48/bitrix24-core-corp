<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Notifications;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Interfaces\ClientProviderInterface;
use Bitrix\Booking\Internals\Container;

class MessageSenderPicker
{
	public static function canUseCurrentSender(): bool
	{
		return self::pickCurrent()->canUse();
	}

	public static function pickCurrent(): \Bitrix\Booking\Interfaces\MessageSender
	{
		return self::pickByClientProvider(
			Container::getProviderManager()::getCurrentProvider()->getClientProvider()
		);
	}

	public static function pickByBooking(Booking $booking): \Bitrix\Booking\Interfaces\MessageSender
	{
		return self::pickByClientProvider(
			Container::getProviderManager()::getProviderByBooking($booking)?->getClientProvider()
		);
	}

	private static function pickByClientProvider(ClientProviderInterface|null $clientProvider): \Bitrix\Booking\Interfaces\MessageSender
	{
		if ($clientProvider)
		{
			//@todo should be saved somewhere in module settings
			$currentSettingsValues = [
				'crm' => 'bitrix24',
			];

			$messageSenders = $clientProvider->getMessageSenders();
			foreach ($messageSenders as $messageSender)
			{
				if (
					isset($currentSettingsValues[$messageSender->getModuleId()])
					&& $messageSender->getCode() === $currentSettingsValues[$messageSender->getModuleId()]
				)
				{
					return $messageSender;
				}
			}
		}

		return new DummyMessageSender();
	}
}
