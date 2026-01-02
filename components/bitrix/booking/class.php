<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Service\Enum\AhaMoment;
use Bitrix\Booking\Provider\OptionProvider;
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
use Bitrix\Main\Localization\Loc;

class BookingComponent extends CBitrixComponent
{
	private const EDITING_BOOKING_ID_PARAM = 'editingBookingId';
	private const EDITING_WAIT_LIST_ITEM_ID_PARAM = 'editingWaitListItemId';

	public function executeComponent(): void
	{
		if (
			!Loader::includeModule('booking')
			|| !Loader::includeModule('crm')
		)
		{
			ShowError('Mandatory modules are not installed: booking, crm');

			return;
		}

		if (Container::getIntranetBookingTool()->isDisabled())
		{
			$this->includeComponentTemplate('tool_disabled');

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
		$editingWaitListItemId = $this->getEditingWaitListItemId();
		$this->arResult['editingWaitListItemId'] = $editingWaitListItemId;
		$this->arResult['AHA_MOMENTS'] = (new AhaMomentProvider())->get($userId);

		$optionProvider = (new OptionProvider());
		$this->arResult['isCalendarExpanded'] = $optionProvider->isCalendarExpanded($userId);
		// if wait list item being edited, wait list should be expanded regardless option value
		$this->arResult['isWaitListExpanded'] = $editingWaitListItemId || $optionProvider->isWaitListExpanded($userId);

		$clientStatisticsProvider = new ClientStatisticsProvider();
		$this->arResult['TOTAL_CLIENTS'] = $clientStatisticsProvider->getTotalClients();
		$this->arResult['TOTAL_CLIENTS_TODAY'] = $clientStatisticsProvider->getTotalClientsToday($userId);
		$this->arResult['MONEY_STATISTICS'] = (new MoneyStatisticsProvider())->get($userId);

		$this->arResult['embedItems'] = $this->getEmbedItems();
		$this->arResult['MENU_ITEMS'] = $this->getTopMenuItems();

		$this->checkIfShouldShowBanner();

		$this->subscribeToPull($userId);

		$this->includeComponentTemplate();
	}

	private function getEditingBookingId(): int
	{
		$request = Context::getCurrent()->getRequest();

		return (int)$request->getQueryList()->get(self::EDITING_BOOKING_ID_PARAM);
	}

	private function getEditingWaitListItemId(): int
	{
		$request = Context::getCurrent()->getRequest();

		return (int)$request->getQueryList()->get(self::EDITING_WAIT_LIST_ITEM_ID_PARAM);
	}

	private function getEmbedItems(): array
	{
		$embedParams = Context::getCurrent()?->getRequest()->getQueryList()->get('embed');

		if (!$embedParams)
		{
			return [];
		}

		$decodedEmbedParams = \Bitrix\Main\Web\Json::decode($embedParams);

		if (!is_array($decodedEmbedParams))
		{
			return [];
		}

		$result = [];

		foreach ($decodedEmbedParams as $item)
		{
			$result[] = [
				'module' =>	$item['module'] ?? '',
				'code' => $item['code'] ?? '',
				'id' => (int)($item['id'] ?? 0),
			];
		}

		return $result;
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

	/**
	 * Check if banner should be shown depend on embedded items.
	 */
	private function checkIfShouldShowBanner(): void
	{
		// if banner already shown before, then not need to continue
		$shouldShowBanner = (bool)($this->arResult['AHA_MOMENTS'][AhaMoment::Banner->value] ?? false);
		if (!$shouldShowBanner)
		{
			return;
		}

		// if query has embedded item crm-deal means booking window opens from crm-deal page,
		// so banner already shown there, no need to show it again
		$isDealEmbed = array_filter(
			$this->arResult['embedItems'] ?? [],
			static fn (array $embedItem) => $embedItem['module'] === 'crm' && $embedItem['code'] === 'DEAL',
		);
		if (!$isDealEmbed)
		{
			return;
		}

		// force set banner aha-moment to not show in this request,
		// but it will be shown in future requests anyway
		$this->arResult['AHA_MOMENTS'][AhaMoment::Banner->value] = false;
	}

	private function getTopMenuItems(): array
	{
		return [
			[
				'ID' => 'records',
				'TEXT' => Loc::getMessage('BOOKING_TOP_MENU_ITEM_RECORDS'),
				'IS_ACTIVE' => true,
			],
		];
	}
}
