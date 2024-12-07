<?php

use Bitrix\SignMobile\Service;
use Bitrix\SignMobile\Repository;

return [
	'controllers' => [
		'value' => [
			'defaultNamespace' => '\\Bitrix\\SignMobile\\Controller',
			'namespaces' => [
				'\\Bitrix\\SignMobile\\Controller' => 'api',
			],
			'restIntegration' => [
				'enabled' => false,
			],
		],
		'readonly' => true,
	],
	'services' => [
		'value' => [
			'signmobile.container' => [
				'className' => Service\Container::class,
			],
			'signmobile.service.event' => [
				'className' => Service\EventService::class,
			],
			'signmobile.repository.notification' => [
				'className' => Repository\NotificationRepository::class,
			],
			'signmobile.repository.notification.priority.queue' => [
				'className' => Repository\NotificationPriorityQueueRepository::class,
			],
		],
	],
];
