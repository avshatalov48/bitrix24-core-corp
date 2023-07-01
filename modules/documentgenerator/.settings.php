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
			'documentgenerator.service.actualizeQueue' => [
				'className' => '\\Bitrix\\DocumentGenerator\\Service\\ActualizeQueue',
			],
		],
		'readonly' => true,
	],

	'ui.entity-selector' => [
		'value' => [
			'entities' => [
				[
					'entityId' => 'documentgenerator-template',
					'provider' => [
						'moduleId' => 'documentgenerator',
						'className' => '\\Bitrix\\DocumentGenerator\\Integration\\UI\\EntitySelector\\TemplateProvider'
					],
				],
			],
		],
		'readonly' => true,
	],
);
