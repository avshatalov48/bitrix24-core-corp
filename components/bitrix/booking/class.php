<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Booking\Service\BookingFeature;
use Bitrix\Booking\Component;
use Bitrix\Booking\Internals\Integration\Pull\PushService;
use Bitrix\Booking\Internals\Service\Journal\EventProcessor\PushPull\PushPullCommandType;
use Bitrix\Booking\Provider\AhaMomentProvider;
use Bitrix\Booking\Provider\ClientStatisticsProvider;
use Bitrix\Booking\Provider\MoneyStatisticsProvider;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;

class BookingComponent extends CBitrixComponent
{
	private const EDITING_BOOKING_ID_PARAM = 'editingBookingId';

	public function executeComponent(): void
	{
		if (
			!Loader::includeModule('booking')
			|| !Loader::includeModule('crm')
			|| !BookingFeature::isOn()
		)
		{
			ShowError('Mandatory modules are not installed: booking, crm');

			return;
		}

		$userId = (int)CurrentUser::get()->getId();

		$this->arResult['currentUserId'] = $userId;

		$this->arResult['isFeatureEnabled'] = BookingFeature::isFeatureEnabled();
		$this->arResult['canTurnOnTrial'] = BookingFeature::canTurnOnTrial();
		$this->arResult['canTurnOnDemo'] = BookingFeature::canTurnOnDemo();

		$this->arResult['timezone'] = $this->getTimezone();
		$this->arResult['IS_SLIDER'] = $this->request->get('IFRAME') === 'Y';
		$this->arResult['FILTER_ID'] = Component\Booking\Filter::getId();
		$this->arResult['editingBookingId'] = $this->getEditingBookingId();
		$this->arResult['AHA_MOMENTS'] = (new AhaMomentProvider())->get($userId);

		$clientStatisticsProvider = new ClientStatisticsProvider();
		$this->arResult['TOTAL_CLIENTS'] = $clientStatisticsProvider->getTotalClients();
		$this->arResult['TOTAL_CLIENTS_TODAY'] = $clientStatisticsProvider->getTotalClientsToday($userId);
		$this->arResult['MONEY_STATISTICS'] = (new MoneyStatisticsProvider())->get($userId);

		$this->subscribeToPull($userId);

		$this->includeComponentTemplate();
	}

	private function getEditingBookingId(): int
	{
		$request = Context::getCurrent()->getRequest();

		return (int)$request->getQueryList()->get(self::EDITING_BOOKING_ID_PARAM);
	}

	private function subscribeToPull(int $userId): void
	{
		$tags = [];
		foreach (PushPullCommandType::cases() as $commandType)
		{
			$tag = $commandType->getTag();
			$tags[$tag] = $tag;
		}

		$pushService = new PushService();

		foreach ($tags as $tag)
		{
			$pushService->subscribeByTag(
				tag: $tag,
				userId: $userId
			);
		}
	}

	/**
	 * todo This is a temporary solution. Please change it when a global solution appears.
	 */
	private function getTimezone(): string
	{
		global $USER;

		if (!is_object($USER))
		{
			return '';
		}

		$timeZone = '';
		$autoTimeZone = $USER->GetParam('AUTO_TIME_ZONE') ?: '';

		if (\CTimeZone::IsAutoTimeZone(trim($autoTimeZone)))
		{
			if (($cookie = \CTimeZone::getTzCookie()) !== null)
			{
				// auto time zone from the cookie
				$timeZone = $cookie;
			}
		}
		else
		{
			// user set time zone manually
			$timeZone = $USER->GetParam('TIME_ZONE');
		}

		return (string)$timeZone;
	}
}
