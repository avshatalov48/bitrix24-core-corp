<?php

return array(
	'controllers' => array(
		'value' => array(
			'namespaces' => array(
				'\\Bitrix\\SalesCenter\\Controller' => 'api',
			),
			'defaultNamespace' => '\\Bitrix\\SalesCenter\\Controller',
			'restIntegration' => [
				'enabled' => true,
			],
		),
		'readonly' => true,
	),
	'services' => [
		'value' => [
			'salecenter.component.paysystem' => [
				'className' => \Bitrix\SalesCenter\Component\PaySystem::class,
			],
			'salecenter.integration.salemanager' => [
				'className' => static function() {
					return \Bitrix\SalesCenter\Integration\SaleManager::getInstance();
				}
			]
		],
	],
);