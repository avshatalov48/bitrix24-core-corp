<?php
return array(
	'controllers' => array(
		'value' => array(
			'namespaces' => array(
				'\\Bitrix\\Crm\\Controller\\DocumentGenerator' => 'documentgenerator',
				'\\Bitrix\\Crm\\Controller' => 'api',
				'\\Bitrix\\Crm\\Integration' => 'integration',
				'\\Bitrix\\Crm\\Controller\\Site' => 'site',
			),
			'restIntegration' => [
				'enabled' => true,
			],
		),
		'readonly' => true,
	),
	'ui.selector' => [
		'value' => [
			'crm.selector'
		],
		'readonly' => true,
	]
);