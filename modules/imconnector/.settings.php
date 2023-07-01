<?php
use Bitrix\ImConnector\Tools;

return [
	'services' => [
		'value' => [
			//@deprecated
			'ImConnector.toolsFbInstagramDirect' => [
				'className' => Tools\Connectors\FbInstagramDirect::class,
			],
			'ImConnector.toolsWeChat' => [
				'className' => Tools\Connectors\WeChat::class,
			],
			'ImConnector.toolsNetwork' => [
				'className' => Tools\Connectors\Network::class,
			],
			'ImConnector.toolsNotifications' => [
				'className' => Tools\Connectors\Notifications::class,
			],
			'ImConnector.toolsMessageservice' => [
				'className' => Tools\Connectors\Messageservice::class,
			],
			'toolsConnector' => [
				'className' => Tools\Connector::class,
			],
		],
		'readonly' => true,
	],
	'controllers' => [
		'value' => [
			'defaultNamespace' => '\\Bitrix\\ImConnector\\Controller',
			'restIntegration' => [
				'enabled' => true,
			]
		],
		'readonly' => true,
	],
];