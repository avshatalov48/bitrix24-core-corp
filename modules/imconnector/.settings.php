<?php
return [
	'services' => [
		'value' => [
			'ImConnector.toolsFbInstagramDirect' => [
				'className' => \Bitrix\ImConnector\Tools\Connectors\FbInstagramDirect::class,
			],
			'ImConnector.toolsWeChat' => [
				'className' => \Bitrix\ImConnector\Tools\Connectors\WeChat::class,
			],
			'ImConnector.toolsNetwork' => [
				'className' => \Bitrix\ImConnector\Tools\Connectors\Network::class,
			],
			'ImConnector.toolsNotifications' => [
				'className' => \Bitrix\ImConnector\Tools\Connectors\Notifications::class,
			],
		],
		'readonly' => true,
	],
];