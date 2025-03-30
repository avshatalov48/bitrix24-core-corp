<?php

namespace Bitrix\Booking\Internals\Service\Feature;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Internals\Exception\InvalidSignatureException;
use Bitrix\Booking\Internals\Container;
use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Security\Sign\BadSignatureException;
use Bitrix\Main\Security\Sign\TimeSigner;
use Bitrix\Main\Web\Uri;

class BookingConfirmLink
{
	private const PUBLIC_PATH = '/pub/booking/confirmation/';

	public function getLink(Booking $booking, BookingConfirmContext $context = BookingConfirmContext::Cancel): string
	{
		$salt = $this->getSalt($booking->getId());

		$bookingDateToTs = $booking->getDatePeriod()->getDateTo()->getTimestamp() + 86400;
		$tokenValidUntil = ($bookingDateToTs < time()) ? time() : $bookingDateToTs;

		$packed = implode('.', [$booking->getId(), $context->value]);

		$token = (new TimeSigner())->sign(
			value: $packed,
			time: $tokenValidUntil,
			salt: $salt,
		);

		$shortUri = \CBXShortUri::getShortUri(self::PUBLIC_PATH . $token . '/');

		$server = Context::getCurrent()->getServer();
		$request = Context::getCurrent()->getRequest();

		$uri = $request->isHttps() ? 'https://' : 'http://';
		$uri .= $this->getServerName();
		$uri .= (
			(int)$server->getServerPort() === 80
			|| ($server->get('HTTPS') && (int)$server->getServerPort() === 443)
		)
			? ''
			: ':' . $server->getServerPort();

		$uri .= $shortUri;

		return (new Uri($uri))->getUri();
	}

	public function getBookingByHash(string $hash): Booking
	{
		return $this->getBookingWithContext($hash)['booking'];
	}

	public function getBookingWithContext(string $hash): array
	{
		try
		{
			$tokenBookingId = (int)explode('.', $hash)[0];
			$salt = $this->getSalt($tokenBookingId);

			$packed = (new TimeSigner())->unsign(
				signedValue: $hash,
				salt: $salt,
			);

			$bookingId = (int)explode('.', $packed)[0];
			$contextValue = explode('.', $packed)[1] ?? BookingConfirmContext::Cancel->value;

			if ($bookingId !== $tokenBookingId)
			{
				throw new InvalidSignatureException();
			}

			$booking = Container::getBookingRepository()->getById($bookingId);

			if (!$booking)
			{
				throw new InvalidSignatureException();
			}

			return [
				'booking' => $booking,
				'context' => BookingConfirmContext::from($contextValue),
			];
		}
		catch (BadSignatureException $e)
		{
			throw new InvalidSignatureException();
		}
	}

	private function getSalt(int $bookingId): string
	{
		return 'BOOKING_' . $bookingId;
	}

	private function getServerName(): string
	{
		return \COption::getOptionString(
			'main',
			'server_name',
			Application::getInstance()->getContext()->getServer()->getServerName()
		);
	}
}
