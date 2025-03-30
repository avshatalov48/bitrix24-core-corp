<?php

use Bitrix\Disk\Bitrix24Disk\SubscriberManager;
use Bitrix\Disk\Document\DocumentHandlersManager;
use Bitrix\Disk\Document\OnlyOffice;
use Bitrix\Disk\Internals\DeletedLogManager;
use Bitrix\Disk\Internals\DeletionNotifyManager;
use Bitrix\Disk\Internals\Runtime\StorageRuntimeCache;
use Bitrix\Disk\RecentlyUsedManager;
use Bitrix\Disk\Rest\RestManager;
use Bitrix\Disk\RightsManager;
use Bitrix\Disk\Search\IndexManager;
use Bitrix\Disk\Uf\UserFieldManager;
use Bitrix\Disk\UrlManager;
use Bitrix\Disk\TrackedObjectManager;

return [
	'controllers' => [
		'value' => [
			'namespaces' => [
				'\\Bitrix\\Disk\\Controller' => 'api',
			],
			'defaultNamespace' => '\\Bitrix\\Disk\\Controller',
		],
		'readonly' => true,
	],
	'services' => [
		'value' => [
			'disk.onlyofficeConfiguration' => [
				'className' => OnlyOffice\Configuration::class,
			],
			'disk.urlManager' => [
				'className' => UrlManager::class,
			],
			'disk.storageRuntimeCache' => [
				'className' => StorageRuntimeCache::class,
			],
			'disk.documentHandlersManager' => [
				'className' => DocumentHandlersManager::class,
				'constructorParams' => static function() {
					global $USER;

					return [
						'userId' => $USER,
					];
				},
			],
			'disk.rightsManager' => [
				'className' => RightsManager::class,
			],
			'disk.ufManager' => [
				'className' => UserFieldManager::class,
			],
			'disk.indexManager' => [
				'className' => IndexManager::class,
			],
			'disk.recentlyUsedManager' => [
				'className' => RecentlyUsedManager::class,
			],
			'disk.restManager' => [
				'className' => RestManager::class,
			],

			'disk.subscriberManager' => [
				'className' => SubscriberManager::class,
			],
			'disk.deletedLogManager' => [
				'className' => DeletedLogManager::class,
			],

			'disk.deletionNotifyManager' => [
				'className' => DeletionNotifyManager::class,
			],
			'disk.trackedObjectManager' => [
				'className' => TrackedObjectManager::class,
			],
		],
		'readonly' => true,
	],
	'b24documents' => [
		'value' => [
			'serverListEndpoint' => 'https://oo-proxy.bitrix.info/settings/config.json',
		],
		'readonly' => true,
	],
	'ui.uploader' => [
		'value' => [
			'allowUseControllers' => true,
		],
		'readonly' => true,
	],
	'boards' => [
		'value' => [
			'client_token_header_lookup' => 'X-Permissions',
			'api_host' => 'https://flip-backend',
			'jwt_secret' => 'secret_token',
			'jwt_ttl' => 30,
			'app_url' => 'https://flip_backend/app',
			'save_delta_time' => 30,
			'save_probability_coef' => 0.1,
			'webhook_url' => '/bitrix/services/main/ajax.php?action=disk.integration.flipchart.webhook',
		],
		'readonly' => true,
	]
];