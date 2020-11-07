<?php
return array(
	'controllers' => array(
		'value' => array(
			'namespaces' => array(
				'\\Bitrix\\Crm\\Controller\\DocumentGenerator' => 'documentgenerator',
				'\\Bitrix\\Crm\\Controller' => 'api',
				'\\Bitrix\\Crm\\Integration' => 'integration',
				'\\Bitrix\\Crm\\Controller\\Site' => 'site',
				'\\Bitrix\\Crm\\Controller\\Requisite' => 'requisite'
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
	],
	'entityFormScope' => [
		'value' => [
			'access' => '\\Bitrix\\Crm\\EntityForm\\ScopeAccess',
		],
	],
	'services' => [
		'value' => [
			'crm.kanban.entity.lead' => [
				'className' => '\\Bitrix\\Crm\\Kanban\\Entity\\Lead',
			],
			'crm.kanban.entity.deal' => [
				'className' => '\\Bitrix\\Crm\\Kanban\\Entity\\Deal',
			],
			'crm.kanban.entity.invoice' => [
				'className' => '\\Bitrix\\Crm\\Kanban\\Entity\\Invoice',
			],
			'crm.kanban.entity.quote' => [
				'className' => '\\Bitrix\\Crm\\Kanban\\Entity\\Quote',
			],
			'crm.kanban.entity.order' => [
				'className' => '\\Bitrix\\Crm\\Kanban\\Entity\\Order',
			],
		],
	],
);