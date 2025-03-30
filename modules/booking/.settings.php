<?php

use Bitrix\Booking\Internals\Integration\Ui\EntitySelector;

return [
	'controllers' => [
		'value' => [
			'namespaces' => [
				'\\Bitrix\\Booking\\Controller' => 'api',
				'\\Bitrix\\Booking\\Controller\\V1' => 'api_v1',
			],
			'defaultNamespace' => '\\Bitrix\\Booking\\Controller',
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
		],
	],
];
