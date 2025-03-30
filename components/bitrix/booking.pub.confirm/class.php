<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Bitrix24\Form\AbuseZoneMap;
use Bitrix\Booking\Internals\Service\Feature\BookingConfirmLink;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use Bitrix\Booking\Internals\Service\Feature\BookingConfirmContext;
use Bitrix\Booking\Internals\Exception\Exception;

Loc::loadMessages(__FILE__);

\CBitrixComponent::includeComponentClass('bitrix:booking.base');

class BookingPubConfirmComponent extends BookingBaseComponent implements \Bitrix\Main\Engine\Contract\Controllerable
{
	public const PAGE_CONTEXT_DELAYED = 'delayed.pub.page';
	public const PAGE_CONTEXT_CANCEL = 'cancel.pub.page';

	public function configureActions(): array
	{
		return [
			'cancel' => [
				'-prefilters' => [
					\Bitrix\Main\Engine\ActionFilter\Csrf::class,
					\Bitrix\Main\Engine\ActionFilter\Authentication::class,
				],
			],
			'confirm' => [
				'-prefilters' => [
					\Bitrix\Main\Engine\ActionFilter\Csrf::class,
					\Bitrix\Main\Engine\ActionFilter\Authentication::class,
				],
			],
		];
	}

	public function exec(): void
	{
		$this->setResult('title', Loc::getMessage('BOOKING_CONFIRM_PAGE_TITLE'));

		try
		{
			$hash = $this->getStringParam('HASH');
			$resp = (new BookingConfirmLink())->getBookingWithContext($hash);

			$booking = $resp['booking'];
			$context = $resp['context'];

			if (!$booking->isDelayed())
			{
				$result = (new \Bitrix\Booking\Command\Booking\ConfirmBookingCommand($hash))->run();

				if (!$result->isSuccess())
				{
					$this->addError($result->getError()?->getCode(), $result->getError()?->getMessage());
					$this->setTemplate('error');
					return;
				}

				$booking = $result?->getBooking();
			}

			$this->setResult('booking', $booking->toArray());
			$this->setResult('hash', $hash);
			$this->setResult('context', $this->getPageContext($booking, $context));
			$this->setResult('company', \Bitrix\Booking\Internals\Integration\Crm\MyCompany::getName() ?? '');
			$this->setResult('currentLang', Loc::getCurrentLang());
			$this->setResult('bitrix24Link', $this->getBitrix24Link());
		}
		catch (Exception $e)
		{
			$this->addError($e->getCode(), $e->getMessage());
			$this->setTemplate('error');
		}
	}

	public function cancelAction(string $hash): void
	{
		if (!\Bitrix\Main\Loader::includeModule('booking'))
		{
			return;
		}

		$result = (new \Bitrix\Booking\Command\Booking\CancelBookingCommand($hash))->run();

		if (!$result->isSuccess())
		{
			$this->addError($result->getError()?->getCode(), $result->getError()?->getMessage());
		}
	}

	public function confirmAction(string $hash): void
	{
		if (!\Bitrix\Main\Loader::includeModule('booking'))
		{
			return;
		}

		$result = (new \Bitrix\Booking\Command\Booking\ConfirmBookingCommand($hash))->run();

		if (!$result->isSuccess())
		{
			$this->addError($result->getError()?->getCode(), $result->getError()?->getMessage());
		}
	}

	private function getBitrix24Link(): ?string
	{
		if (!\Bitrix\Main\Loader::includeModule('bitrix24'))
		{
			return null;
		}

		$region = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion();
		$abuseLink = AbuseZoneMap::getLink($region);

		$parsedUrl = parse_url($abuseLink);
		$protocol = $parsedUrl['scheme'];
		$host = $parsedUrl['host'];
		$parsedUri = new Uri($protocol . '://' . $host);

		return rtrim($parsedUri->getLocator(), '/');
	}

	private function getPageContext(
		\Bitrix\Booking\Entity\Booking\Booking $booking,
		BookingConfirmContext $context
	): string
	{
		// if it is already delayed
		if ($booking->isDelayed())
		{
			return self::PAGE_CONTEXT_DELAYED;
		}

		return $context === BookingConfirmContext::Delayed
			? self::PAGE_CONTEXT_DELAYED
			: self::PAGE_CONTEXT_CANCEL
		;
	}
}
