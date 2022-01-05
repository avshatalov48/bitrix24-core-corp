<?php
return array(
	'controllers' => array(
		'value' => array(
			'namespaces' => array(
				'\\Bitrix\\DocumentGenerator\\Controller' => 'api',
			),
			'defaultNamespace' => '\\Bitrix\\DocumentGenerator\\Controller',
			'restIntegration' => [
				'enabled' => true,
			],
		),
		'readonly' => true,
	),

	'services' => [
		'value' => [
			'documentgenerator.integration.intranet.binding.codeBuilder' => [
				'className' => '\\Bitrix\\DocumentGenerator\\Integration\\Intranet\\Binding\\CodeBuilder',
			],
		],
		'readonly' => true,
	],
);
