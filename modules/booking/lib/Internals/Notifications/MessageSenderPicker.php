<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Notifications;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Integration\Booking\ClientProviderInterface;
use Bitrix\Booking\Integration\Booking\Message\MessageSender;
use Bitrix\Booking\Integration\Booking\Message\DummyMessageSender;
use Bitrix\Booking\Internals\Container;

class MessageSenderPicker
{
	public static function canUseCurrentSender(): bool
	{
		return self::pickCurrent()->canUse();
	}

	public static function pickCurrent(): MessageSender
	{
		return self::pickByClientProvider(
			Container::getProviderManager()::getCurrentProvider()->getClientProvider()
		);
	}

	public static function pickByBooking(Booking $booking): MessageSender
	{
		return self::pickByClientProvider(
			Container::getProviderManager()::getProviderByBooking($booking)?->getClientProvider()
		);
	}

	private static function pickByClientProvider(ClientProviderInterface|null $clientProvider): MessageSender
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
