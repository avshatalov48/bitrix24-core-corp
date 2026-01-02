<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals;

use Bitrix\Booking\Internals\Integration\Catalog\CatalogSkuDataLoader;
use Bitrix\Booking\Internals\Integration\Catalog\ServiceSkuProvider;
use Bitrix\Booking\Internals\Integration\Crm\Contact\ContactService;
use Bitrix\Booking\Internals\Integration\Crm\CrmDealDataLoader;
use Bitrix\Booking\Internals\Integration\Crm\DealService;
use Bitrix\Booking\Internals\Integration\Intranet\BookingTool;
use Bitrix\Booking\Internals\Repository\BookingMessageRepositoryInterface;
use Bitrix\Booking\Internals\Repository\ClientTypeRepositoryInterface;
use Bitrix\Booking\Internals\Repository\ORM\DelayedTaskRepository;
use Bitrix\Booking\Internals\Repository\ORM\Mapper\ClientTypeMapper;
use Bitrix\Booking\Internals\Repository\ORM\Mapper\WaitListItemMapper;
use Bitrix\Booking\Internals\Repository\ORM\ResourceLinkedEntityRepository;
use Bitrix\Booking\Internals\Repository\WaitListItemRepositoryInterface;
use Bitrix\Booking\Internals\Service\BookingService;
use Bitrix\Booking\Internals\Service\ClientService;
use Bitrix\Booking\Internals\Service\DelayedTask\DelayedTaskService;
use Bitrix\Booking\Internals\Service\EventForBookingService;
use Bitrix\Booking\Internals\Service\ExternalDataService;
use Bitrix\Booking\Internals\Service\Journal\JournalServiceInterface;
use Bitrix\Booking\Internals\Repository\AdvertisingResourceTypeRepository;
use Bitrix\Booking\Internals\Repository\BookingClientRepositoryInterface;
use Bitrix\Booking\Internals\Repository\BookingRepositoryInterface;
use Bitrix\Booking\Internals\Repository\CounterRepositoryInterface;
use Bitrix\Booking\Internals\Repository\FavoritesRepositoryInterface;
use Bitrix\Booking\Internals\Repository\JournalRepositoryInterface;
use Bitrix\Booking\Internals\Repository\OptionRepositoryInterface;
use Bitrix\Booking\Internals\Repository\ORM\BookingExternalDataRepository;
use Bitrix\Booking\Internals\Repository\ORM\BookingResourceRepository;
use Bitrix\Booking\Internals\Repository\ORM\Mapper\BookingMapper;
use Bitrix\Booking\Internals\Repository\ORM\Mapper\ClientMapper;
use Bitrix\Booking\Internals\Repository\ORM\Mapper\ExternalDataItemMapper;
use Bitrix\Booking\Internals\Repository\ORM\Mapper\ResourceDataMapper;
use Bitrix\Booking\Internals\Repository\ORM\Mapper\ResourceMapper;
use Bitrix\Booking\Internals\Repository\ORM\Mapper\ResourceTypeMapper;
use Bitrix\Booking\Internals\Repository\ResourceRepositoryInterface;
use Bitrix\Booking\Internals\Repository\ResourceSlotRepositoryInterface;
use Bitrix\Booking\Internals\Repository\ResourceTypeRepositoryInterface;
use Bitrix\Booking\Internals\Repository\TransactionHandlerInterface;
use Bitrix\Booking\Internals\Service\Notifications\MessageSender;
use Bitrix\Booking\Internals\Service\Overbooking\OverbookingService;
use Bitrix\Booking\Internals\Service\Overbooking\OverlapPolicy;
use Bitrix\Booking\Internals\Service\ProviderManager;
use Bitrix\Booking\Internals\Service\ResourceService;
use Bitrix\Booking\Internals\Service\WaitListItemService;
use Bitrix\Booking\Internals\Service\Yandex\AvailableTimeSlotsProvider;
use Bitrix\Booking\Internals\Service\Yandex\ResourceProvider;
use Bitrix\Booking\Internals\Service\Yandex\ServiceProvider;
use Bitrix\Booking\Provider\BookingProvider;
use Bitrix\Main\Access\BaseAccessController;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Booking\Internals\Service\Yandex;

class Container
{
	public static function instance(): Container
	{
		return self::getService('booking.container');
	}

	private static function getService(string $name): mixed
	{
		$prefix = 'booking.';
		if (mb_strpos($name, $prefix) !== 0)
		{
			$name = $prefix . $name;
		}
		$locator = ServiceLocator::getInstance();

		return $locator->has($name)
			? $locator->get($name)
			: null
		;
	}

	public static function getTransactionHandler(): TransactionHandlerInterface
	{
		return self::getService('booking.transaction.handler');
	}

	public static function getBookingRepository(): BookingRepositoryInterface
	{
		return self::getService('booking.booking.repository');
	}

	public static function getBookingRepositoryMapper(): BookingMapper
	{
		return self::getService('booking.booking.repository.mapper');
	}

	public static function getResourceRepository(): ResourceRepositoryInterface
	{
		return self::getService('booking.resource.repository');
	}

	public static function getResourceRepositoryMapper(): ResourceMapper
	{
		return self::getService('booking.resource.repository.mapper');
	}

	public static function getClientRepositoryMapper(): ClientMapper
	{
		return self::getService('booking.client.repository.mapper');
	}

	public static function getExternalDataItemRepositoryMapper(): ExternalDataItemMapper
	{
		return self::getService('booking.external.data.item.repository.mapper');
	}

	public static function getResourceDataRepositoryMapper(): ResourceDataMapper
	{
		return self::getService('booking.resource.data.repository.mapper');
	}

	public static function getResourceTypeAccessController(): BaseAccessController
	{
		return self::getService('booking.resource.type.access.controller');
	}

	public static function getResourceAccessController(): BaseAccessController
	{
		return self::getService('booking.resource.access.controller');
	}

	public static function getResourceTypeRepository(): ResourceTypeRepositoryInterface
	{
		return self::getService('booking.resource.type.repository');
	}

	public static function getAdvertisingTypeRepository(): AdvertisingResourceTypeRepository
	{
		return self::getService('booking.advertising.type.repository');
	}

	public static function getResourceTypeRepositoryMapper(): ResourceTypeMapper
	{
		return self::getService('booking.resource.repository.type.mapper');
	}

	public static function getJournalRepository(): JournalRepositoryInterface
	{
		return self::getService('booking.journal.repository');
	}

	public static function getJournalService(): JournalServiceInterface
	{
		return self::getService('booking.journal.service');
	}

	public static function getResourceSlotRepository(): ResourceSlotRepositoryInterface
	{
		return self::getService('booking.resource.slot.repository');
	}

	public static function getBookingResourceRepository(): BookingResourceRepository
	{
		return self::getService('booking.booking.resource.repository');
	}

	public static function getFavoritesRepository(): FavoritesRepositoryInterface
	{
		return self::getService('booking.favorites.repository');
	}

	public static function getBookingClientRepository(): BookingClientRepositoryInterface
	{
		return self::getService('booking.client.repository');
	}

	public static function getBookingExternalDataRepository(): BookingExternalDataRepository
	{
		return self::getService('booking.external.data.repository');
	}

	public static function getCounterRepository(): CounterRepositoryInterface
	{
		return self::getService('booking.counter.repository');
	}

	public static function getProviderManager(): ProviderManager
	{
		return self::getService('booking.provider.manager');
	}

	public static function getOptionRepository(): OptionRepositoryInterface
	{
		return self::getService('booking.option.repository');
	}

	public static function getMessageSender(): MessageSender
	{
		return self::getService('booking.message.sender');
	}

	public static function getWaitListItemRepositoryMapper(): WaitListItemMapper
	{
		return self::getService('booking.wait.list.item.repository.mapper');
	}

	public static function getWaitListItemRepository(): WaitListItemRepositoryInterface
	{
		return self::getService('booking.wait.list.item.repository');
	}

	public static function getClientTypeRepository(): ClientTypeRepositoryInterface
	{
		return self::getService('booking.client.type.repository');
	}

	public static function getClientTypeRepositoryMapper(): ClientTypeMapper
	{
		return self::getService('booking.client.type.repository.mapper');
	}

	public static function getBookingService(): BookingService
	{
		return self::getService('booking.internals.booking.service');
	}

	public static function getClientService(): ClientService
	{
		return self::getService('booking.internals.client.service');
	}

	public static function getExternalDataService(): ExternalDataService
	{
		return self::getService('booking.internals.external.data.service');
	}

	public static function getResourceService(): ResourceService
	{
		return self::getService('booking.internals.resource.service');
	}

	public static function getWaitListItemService(): WaitListItemService
	{
		return self::getService('booking.internals.wait.list.item.service');
	}

	public static function getBookingMessageRepository(): BookingMessageRepositoryInterface
	{
		return self::getService('booking.message.repository');
	}

	public static function getOverBookingOverlapPolicy(): OverlapPolicy
	{
		return self::getService('booking.overbooking.overlap.policy');
	}

	public static function getOverbookingService(): OverbookingService
	{
		return self::getService('booking.internals.overbooking.service');
	}

	public static function getEventForBookingService(): EventForBookingService
	{
		return self::getService('booking.internals.event_for_booking.service');
	}

	public static function getDelayedTaskRepository(): DelayedTaskRepository
	{
		return self::getService('booking.internals.delayed_task.repository');
	}

	public static function getDelayedTaskService(): DelayedTaskService
	{
		return self::getService('booking.internals.delayed_task.service');
	}

	public static function getResourceLinkedEntityRepository(): ResourceLinkedEntityRepository
	{
		return self::getService('booking.internals.resource_linked_entity.repository');
	}

	public static function getYandexResourceProvider(): ResourceProvider
	{
		return self::getService('booking.internals.service.yandex.resource.provider');
	}

	public static function getYandexServiceProvider(): ServiceProvider
	{
		return self::getService('booking.internals.service.yandex.service.provider');
	}

	public static function getYandexAvailableTimeSlotsProvider(): AvailableTimeSlotsProvider
	{
		return self::getService('booking.internals.service.yandex.available.time.slots.provider');
	}

	public static function getYandexAvailableDatesProvider(): Yandex\AvailableDatesProvider
	{
		return self::getService('booking.internals.service.yandex.available.dates.provider');
	}

	public static function getYandexDeleteBookingService(): Yandex\DeleteBookingService
	{
		return self::getService('booking.internals.service.yandex.delete.booking.service');
	}

	public static function getYandexCreateBookingService(): Yandex\CreateBookingService
	{
		return self::getService('booking.internals.service.yandex.create.booking.service');
	}

	public static function getCrmContactService(): ContactService
	{
		return self::getService('booking.internals.integration.crm.contact.service');
	}

	public static function getCrmDealService(): DealService
	{
		return self::getService('booking.internals.integration.crm.deal.service');
	}

	public static function getCatalogServiceSkuProvider(): ServiceSkuProvider
	{
		return self::getService('booking.internals.integration.catalog.service.sku.provider');
	}

	public static function getYandexBookingProvider(): Yandex\BookingProvider
	{
		return self::getService('booking.internals.service.yandex.booking.provider');
	}

	public static function getYandexCompanyFeedProvider(): Yandex\CompanyFeedProvider
	{
		return self::getService('booking.internals.service.yandex.company.feed.provider');
	}

	public static function getYandexCompanyRepository(): Yandex\CompanyRepository
	{
		return self::getService('booking.internals.service.yandex.company.repository');
	}

	public static function getYandexApiClient(): Yandex\ApiClient
	{
		return self::getService('booking.internals.service.yandex.api.client');
	}

	public static function getYandexAccount(): Yandex\Account
	{
		return self::getService('booking.internals.service.yandex.account');
	}

	public static function getYandexCompanyFeedSender(): Yandex\CompanyFeedSender
	{
		return self::getService('booking.internals.service.yandex.company.feed.sender');
	}

	public static function getCatalogSkuDataLoader(): CatalogSkuDataLoader
	{
		return self::getService('booking.internals.integration.catalog.sku.data.loader');
	}

	public static function getCrmDealDataLoader(): CrmDealDataLoader
	{
		return self::getService('booking.internals.integration.crm.deal.data.loader');
	}

	public static function getBookingProvider(): BookingProvider
	{
		return self::getService('booking.booking.provider');
	}

	public static function getIntranetBookingTool(): BookingTool
	{
		return self::getService('booking.internals.integration.intranet.booking.tool');
	}
}
