<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
\CBitrixComponent::includeComponentClass('bitrix:booking.base');

class BookingDetailComponent extends BookingBaseComponent implements \Bitrix\Main\Engine\Contract\Controllerable
{
	public function configureActions(): array
	{
		return [];
	}

	public function exec(): void
	{
		try
		{
			$bookingId = (int)$this->getParam('ID');
			$userId = (int)\Bitrix\Main\Engine\CurrentUser::get()->getId();
			$this->setResult('title', Loc::getMessage('BOOKING_DETAIL_PAGE_TITLE'));
			$booking = (new \Bitrix\Booking\Provider\BookingProvider())->getBookingForManager($bookingId);

			if (!$booking)
			{
				throw new \Bitrix\Main\SystemException(Loc::getMessage('BOOKING_DETAIL_PAGE_ERR_NOT_FOUND'), 404);
			}

			if ($booking->getCreatedBy() !== $userId)
			{
				throw new \Bitrix\Main\SystemException(Loc::getMessage('BOOKING_DETAIL_PAGE_ERR_FORBIDDEN'), 403);
			}

			$this->setResult('booking', $booking->toArray());
			$this->setResult('company', \Bitrix\Booking\Internals\Integration\Crm\MyCompany::getName() ?? '');
		}
		catch (Throwable $e)
		{
			$this->addError($e->getCode(), $e->getMessage());
			$this->setTemplate('error');
		}
	}
}
