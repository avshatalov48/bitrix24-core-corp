<?php

use Bitrix\Booking\Internals\Integration\Ui\EntitySelector;
use Bitrix\Booking\Provider\TimeProvider;

return [
	'controllers' => [
		'value' => [
			'namespaces' => [
				'\\Bitrix\\Booking\\Controller' => 'api',
				'\\Bitrix\\Booking\\Controller\\V1' => 'api_v1',
				'\\Bitrix\\Booking\\Rest\\V1\\Controller' => 'v1',
			],
			'defaultNamespace' => '\\Bitrix\\Booking\\Controller',
			'restIntegration' => [
				'enabled' => true,
			],
		],
		'readonly' => true,
	],
	'ui.entity-selector' => [
		'value' => [
			'entities' => [
				[
					'entityId' => 'resource',
					'provider' => [
						'moduleId' => 'booking',
						'className' => EntitySelector\ResourceProvider::class,
					],
				],
				[
					'entityId' => 'resource-type', // todo wtf EntitySelector\EntityId
					'provider' => [
						'moduleId' => 'booking',
						'className' => EntitySelector\ResourceTypeProvider::class,
					],
				],
			],
		],
	],
	'services' => [
		'value' => [
			'booking.container' => [
				'className' => \Bitrix\Booking\Internals\Container::class,
			],
			'booking.transaction.handler' => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\TransactionHandler::class,
			],
			'booking.resource.type.access.controller' => [
				'className' => \Bitrix\Booking\Access\ResourceTypeAccessController::class,
				'constructorParams' => static function() {
					return [
						'userId' => 0,
					];
				},
			],
			'booking.resource.access.controller' => [
				'className' => \Bitrix\Booking\Access\ResourceAccessController::class,
				'constructorParams' => static function() {
					return [
						'userId' => 0,
					];
				},
			],
			'booking.booking.repository' => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\BookingRepository::class,
				'constructorParams' => static function() {
					return [
						\Bitrix\Booking\Internals\Container::getBookingRepositoryMapper(),
					];
				},
			],
			'booking.booking.repository.mapper' => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\Mapper\BookingMapper::class,
				'constructorParams' => static function() {
					return [
						'resourceMapper' => \Bitrix\Booking\Internals\Container::getResourceRepositoryMapper(),
						'clientMapper' => \Bitrix\Booking\Internals\Container::getClientRepositoryMapper(),
						'externalDataItemMapper' => \Bitrix\Booking\Internals\Container::getExternalDataItemRepositoryMapper(),
					];
				},
			],
			'booking.client.repository' => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\BookingClientRepository::class,
			],
			'booking.external.data.repository' => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\BookingExternalDataRepository::class,
			],
			'booking.resource.repository.mapper' => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\Mapper\ResourceMapper::class,
			],
			'booking.resource.data.repository.mapper' => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\Mapper\ResourceDataMapper::class,
			],
			'booking.resource.repository' => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\ResourceRepository::class,
				'constructorParams' => static function() {
					return [
						\Bitrix\Booking\Internals\Container::getResourceRepositoryMapper(),
						\Bitrix\Booking\Internals\Container::getResourceDataRepositoryMapper(),
					];
				},
			],
			'booking.resource.type.repository' => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\ResourceTypeRepository::class,
				'constructorParams' => static function() {
					return [
						'mapper' => \Bitrix\Booking\Internals\Container::getResourceTypeRepositoryMapper(),
					];
				},
			],
			'booking.advertising.type.repository' => [
				'className' => \Bitrix\Booking\Internals\Repository\AdvertisingResourceTypeRepository::class,
			],
			'booking.journal.repository' => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\JournalRepository::class,
			],
			'booking.journal.service' => [
				'className' => \Bitrix\Booking\Internals\Service\Journal\JournalService::class,
			],
			'booking.resource.slot.repository' => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\ResourceSlotRepository::class,
				'constructorParams' => static function() {
					return [
						'mapper' => new \Bitrix\Booking\Internals\Repository\ORM\Mapper\ResourceSlotMapper(),
					];
				},
			],
			'booking.booking.resource.repository' => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\BookingResourceRepository::class,
			],
			'booking.resource.repository.type.mapper' => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\Mapper\ResourceTypeMapper::class,
			],
			'booking.client.repository.mapper' => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\Mapper\ClientMapper::class,
			],
			'booking.external.data.item.repository.mapper' => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\Mapper\ExternalDataItemMapper::class,
			],
			'booking.favorites.repository' => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\FavoritesRepository::class,
				'constructorParams' => static function() {
					return [
						'mapper' => new \Bitrix\Booking\Internals\Repository\ORM\Mapper\FavoritesMapper(),
					];
				},
			],
			'booking.counter.repository' => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\CounterRepository::class,
			],
			'booking.provider.manager' => [
				'className' => \Bitrix\Booking\Internals\Service\ProviderManager::class,
			],
			'booking.option.repository' => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\OptionRepository::class,
			],
			'booking.message.sender' => [
				'className' => \Bitrix\Booking\Internals\Service\Notifications\MessageSender::class,
			],
			'booking.wait.list.item.repository.mapper' => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\Mapper\WaitListItemMapper::class,
				'constructorParams' => static function() {
					return [
						'clientMapper' => \Bitrix\Booking\Internals\Container::getClientRepositoryMapper(),
						'externalDataItemMapper' => \Bitrix\Booking\Internals\Container::getExternalDataItemRepositoryMapper(),
					];
				},
			],
			'booking.wait.list.item.repository' => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\WaitListItemRepository::class,
				'constructorParams' => static function() {
					return [
						\Bitrix\Booking\Internals\Container::getWaitListItemRepositoryMapper(),
					];
				},
			],
			'booking.overbooking.overlap.policy' => [
				'className' => \Bitrix\Booking\Internals\Service\Overbooking\OverlapPolicy::class,
			],
			'booking.client.type.repository' => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\ClientTypeRepository::class,
				'constructorParams' => static function() {
					return [
						\Bitrix\Booking\Internals\Container::getClientTypeRepositoryMapper(),
					];
				},
			],
			'booking.client.type.repository.mapper' => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\Mapper\ClientTypeMapper::class,
			],
			'booking.internals.booking.service' => [
				'className' => \Bitrix\Booking\Internals\Service\BookingService::class,
				'constructorParams' => static function() {
					return [
						'bookingRepository' => \Bitrix\Booking\Internals\Container::getBookingRepository(),
						'resourceService' => \Bitrix\Booking\Internals\Container::getResourceService(),
						'clientService' => \Bitrix\Booking\Internals\Container::getClientService(),
						'externalDataService' => \Bitrix\Booking\Internals\Container::getExternalDataService(),
						'overbookingOverlapPolicy' => \Bitrix\Booking\Internals\Container::getOverBookingOverlapPolicy(),
					];
				},
			],
			'booking.internals.client.service' => [
				'className' => \Bitrix\Booking\Internals\Service\ClientService::class,
				'constructorParams' => static function() {
					return [
						'bookingClientRepository' => \Bitrix\Booking\Internals\Container::getBookingClientRepository(),
					];
				},
			],
			'booking.internals.external.data.service' => [
				'className' => \Bitrix\Booking\Internals\Service\ExternalDataService::class,
				'constructorParams' => static function() {
					return [
						'bookingClientRepository' => \Bitrix\Booking\Internals\Container::getBookingClientRepository(),
						'bookingExternalDataRepository' => \Bitrix\Booking\Internals\Container::getBookingExternalDataRepository(),
						'clientService' => \Bitrix\Booking\Internals\Container::getClientService(),
					];
				},
			],
			'booking.internals.resource.service' => [
				'className' => \Bitrix\Booking\Internals\Service\ResourceService::class,
				'constructorParams' => static function() {
					return [
						'bookingResourceRepository' => \Bitrix\Booking\Internals\Container::getBookingResourceRepository(),
						'resourceRepository' => \Bitrix\Booking\Internals\Container::getResourceRepository(),
						'resourceTypeRepository' => \Bitrix\Booking\Internals\Container::getResourceTypeRepository(),
						'resourceLinkedEntityRepository' => \Bitrix\Booking\Internals\Container::getResourceLinkedEntityRepository(),
					];
				},
			],
			'booking.internals.wait.list.item.service' => [
				'className' => \Bitrix\Booking\Internals\Service\WaitListItemService::class,
				'constructorParams' => static function() {
					return [
						'waitListItemRepository' => \Bitrix\Booking\Internals\Container::getWaitListItemRepository(),
						'clientService' => \Bitrix\Booking\Internals\Container::getClientService(),
						'externalDataService' => \Bitrix\Booking\Internals\Container::getExternalDataService(),
					];
				},
			],
			'booking.message.repository' => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\BookingMessageRepository::class,
			],
			'booking.internals.overbooking.service' => [
				'className' => \Bitrix\Booking\Internals\Service\Overbooking\OverbookingService::class,
				'constructorParams' => static function() {
					return [
						'bookingRepository' => \Bitrix\Booking\Internals\Container::getBookingRepository(),
					];
				},
			],
			'booking.internals.event_for_booking.service' => [
				'className' => \Bitrix\Booking\Internals\Service\EventForBookingService::class,
				'constructorParams' => static function() {
					return [
						'externalDataRepository' => \Bitrix\Booking\Internals\Container::getBookingExternalDataRepository(),
					];
				},
			],
			'booking.internals.delayed_task.repository' => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\DelayedTaskRepository::class,
				'constructorParams' => static function() {
					return [
						'mapper' => new \Bitrix\Booking\Internals\Repository\ORM\Mapper\DelayedTaskMapper(),
					];
				},
			],
			'booking.internals.delayed_task.service' => [
				'className' => \Bitrix\Booking\Internals\Service\DelayedTask\DelayedTaskService::class,
				'constructorParams' => static function() {
					return [
						'delayedTaskRepository' => \Bitrix\Booking\Internals\Container::getDelayedTaskRepository(),
					];
				},
			],
			'booking.internals.resource_linked_entity.repository' => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\ResourceLinkedEntityRepository::class,
			],
			'booking.internals.service.yandex.resource.provider' => [
				'className' => Bitrix\Booking\Internals\Service\Yandex\ResourceProvider::class,
				'constructorParams' => static function() {
					return [
						'companyRepository' => \Bitrix\Booking\Internals\Container::getYandexCompanyRepository(),
						'resourceRepository' => \Bitrix\Booking\Internals\Container::getResourceRepository(),
						'serviceSkuProvider' => \Bitrix\Booking\Internals\Container::getCatalogServiceSkuProvider(),
					];
				},
			],
			'booking.internals.service.yandex.service.provider' => [
				'className' => Bitrix\Booking\Internals\Service\Yandex\ServiceProvider::class,
				'constructorParams' => static function() {
					return [
						'companyRepository' => \Bitrix\Booking\Internals\Container::getYandexCompanyRepository(),
						'resourceRepository' => \Bitrix\Booking\Internals\Container::getResourceRepository(),
						'serviceSkuProvider' => \Bitrix\Booking\Internals\Container::getCatalogServiceSkuProvider(),
					];
				},
			],
			'booking.internals.service.yandex.available.time.slots.provider' => [
				'className' => Bitrix\Booking\Internals\Service\Yandex\AvailableTimeSlotsProvider::class,
				'constructorParams' => static function() {
					return [
						'companyRepository' => \Bitrix\Booking\Internals\Container::getYandexCompanyRepository(),
						'resourceRepository' => \Bitrix\Booking\Internals\Container::getResourceRepository(),
						'bookingRepository' => \Bitrix\Booking\Internals\Container::getBookingRepository(),
						'serviceSkuProvider' => \Bitrix\Booking\Internals\Container::getCatalogServiceSkuProvider(),
						'timeProvider' => new TimeProvider(),
					];
				},
			],
			'booking.internals.service.yandex.available.dates.provider' => [
				'className' => \Bitrix\Booking\Internals\Service\Yandex\AvailableDatesProvider::class,
				'constructorParams' => static function() {
					return [
						'companyRepository' => \Bitrix\Booking\Internals\Container::getYandexCompanyRepository(),
						'resourceRepository' => \Bitrix\Booking\Internals\Container::getResourceRepository(),
						'bookingRepository' => \Bitrix\Booking\Internals\Container::getBookingRepository(),
						'serviceSkuProvider' => \Bitrix\Booking\Internals\Container::getCatalogServiceSkuProvider(),
						'timeProvider' => new TimeProvider(),
					];
				},
			],
			'booking.internals.service.yandex.delete.booking.service' => [
				'className' => Bitrix\Booking\Internals\Service\Yandex\DeleteBookingService::class,
				'constructorParams' => static function() {
					return [
						'bookingRepository' => \Bitrix\Booking\Internals\Container::getBookingRepository(),
					];
				},
			],
			'booking.internals.service.yandex.create.booking.service' => [
				'className' => Bitrix\Booking\Internals\Service\Yandex\CreateBookingService::class,
				'constructorParams' => static function() {
					return [
						'bookingRepository' => \Bitrix\Booking\Internals\Container::getBookingRepository(),
						'resourceRepository' => \Bitrix\Booking\Internals\Container::getResourceRepository(),
						'serviceSkuProvider' => \Bitrix\Booking\Internals\Container::getCatalogServiceSkuProvider(),
						'contactService' => \Bitrix\Booking\Internals\Container::getCrmContactService(),
						'dealService' => \Bitrix\Booking\Internals\Container::getCrmDealService(),
					];
				},
			],
			'booking.internals.integration.crm.contact.service' => [
				'className' => Bitrix\Booking\Internals\Integration\Crm\Contact\ContactService::class,
			],
			'booking.internals.integration.crm.deal.service' => [
				'className' => Bitrix\Booking\Internals\Integration\Crm\DealService::class,
				'constructorParams' => static function() {
					return [
						'serviceSkuProvider' => \Bitrix\Booking\Internals\Container::getCatalogServiceSkuProvider(),
					];
				},
			],
			'booking.internals.integration.catalog.service.sku.provider' => [
				'className' => \Bitrix\Booking\Internals\Integration\Catalog\ServiceSkuProvider::class,
			],
			'booking.internals.service.yandex.booking.provider' => [
				'className' => Bitrix\Booking\Internals\Service\Yandex\BookingProvider::class,
				'constructorParams' => static function() {
					return [
						'bookingRepository' => \Bitrix\Booking\Internals\Container::getBookingRepository(),
					];
				},
			],
			'booking.internals.service.yandex.company.feed.provider' => [
				'className' => Bitrix\Booking\Internals\Service\Yandex\CompanyFeedProvider::class,
				'constructorParams' => static function() {
					return [
						'companyRepository' => \Bitrix\Booking\Internals\Container::getYandexCompanyRepository(),
					];
				},
			],
			'booking.internals.service.yandex.company.repository' => [
				'className' => Bitrix\Booking\Internals\Service\Yandex\CompanyRepository::class,
			],
			'booking.internals.service.yandex.api.client' => [
				'className' => Bitrix\Booking\Internals\Service\Yandex\ApiClient::class,
			],
			'booking.internals.service.yandex.account' => [
				'className' => Bitrix\Booking\Internals\Service\Yandex\Account::class,
				'constructorParams' => static function() {
					return [
						'apiClient' => \Bitrix\Booking\Internals\Container::getYandexApiClient(),
					];
				},
			],
			'booking.internals.service.yandex.company.feed.sender' => [
				'className' => Bitrix\Booking\Internals\Service\Yandex\CompanyFeedSender::class,
				'constructorParams' => static function() {
					return [
						'apiClient' => \Bitrix\Booking\Internals\Container::getYandexApiClient(),
					];
				},
			],
			'booking.internals.integration.catalog.sku.data.loader' => [
				'className' => Bitrix\Booking\Internals\Integration\Catalog\CatalogSkuDataLoader::class,
				'constructorParams' => static function() {
					return [
						'serviceSkuProvider' => \Bitrix\Booking\Internals\Container::getCatalogServiceSkuProvider(),
					];
				},
			],
			'booking.booking.provider' => [
				'className' => \Bitrix\Booking\Provider\BookingProvider::class,
			],
			'booking.internals.integration.crm.deal.data.loader' => [
				'className' => Bitrix\Booking\Internals\Integration\Crm\CrmDealDataLoader::class,
			],
			'booking.internals.integration.intranet.booking.tool' => [
				'className' => \Bitrix\Booking\Internals\Integration\Intranet\BookingTool::class,
			],
			\Bitrix\Booking\Internals\Repository\BookingRepositoryInterface::class => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\BookingRepository::class,
			],
			\Bitrix\Booking\Internals\Repository\ResourceRepositoryInterface::class => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\ResourceRepository::class,
			],
			\Bitrix\Booking\Internals\Repository\ResourceTypeRepositoryInterface::class => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\ResourceTypeRepository::class,
			],
			\Bitrix\Booking\Internals\Repository\BookingClientRepositoryInterface::class => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\BookingClientRepository::class,
			],
			\Bitrix\Booking\Internals\Repository\BookingMessageRepositoryInterface::class => [
				'className' => \Bitrix\Booking\Internals\Repository\ORM\BookingMessageRepository::class,
			],
		],
	],
];
