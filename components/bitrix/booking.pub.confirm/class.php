<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Bitrix24\Form\AbuseZoneMap;
use Bitrix\Booking\Internals\Feature\BookingConfirmLink;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

Loc::loadMessages(__FILE__);

\CBitrixComponent::includeComponentClass('bitrix:booking.base');

class BookingPubConfirmComponent extends BookingBaseComponent implements \Bitrix\Main\Engine\Contract\Controllerable
{
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
			$booking = (new BookingConfirmLink())->getBookingByHash($hash);

			if (!$booking->isDelayed())
			{
				$command = new \Bitrix\Booking\Internals\Command\Booking\ConfirmBookingCommand($hash);
				$booking = (new \Bitrix\Booking\Internals\Command\Booking\ConfirmBookingCommandHandler())($command);
			}

			$this->setResult('booking', $booking->toArray());
			$this->setResult('hash', $hash);
			$this->setResult('context', $this->getPageContext($booking));
			$this->setResult('company', \Bitrix\Booking\Integration\Crm\MyCompany::getName() ?? '');
			$this->setResult('currentLang', Loc::getCurrentLang());
			$this->setResult('bitrix24Link', $this->getBitrix24Link());
		}
		catch (Throwable $e)
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

		$command = new \Bitrix\Booking\Internals\Command\Booking\CancelBookingCommand($hash);
		(new \Bitrix\Booking\Internals\Command\Booking\CancelBookingCommandHandler())($command);
	}

	public function confirmAction(string $hash): void
	{
		if (!\Bitrix\Main\Loader::includeModule('booking'))
		{
			return;
		}

		$command = new \Bitrix\Booking\Internals\Command\Booking\ConfirmBookingCommand($hash);
		(new \Bitrix\Booking\Internals\Command\Booking\ConfirmBookingCommandHandler())($command);
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

	private function getPageContext(\Bitrix\Booking\Entity\Booking\Booking $booking): string
	{
		return $booking->isDelayed()
			? 'delayed.pub.page'
			: 'cancel.pub.page'
		;
	}
}
