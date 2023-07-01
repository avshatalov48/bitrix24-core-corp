<?php
return [
	'controllers' => [
		'value' => [
			'defaultNamespace' => '\\Bitrix\\CrmMobile\\Controller',
			'restIntegration' => [
				'enabled' => false,
			],
		],
		'readonly' => true,
	],
	'services' => [
		'value' => [
			'crmmobile.kanban.entity.lead' => [
				'className' => '\\Bitrix\\CrmMobile\\Kanban\\Entity\\Lead',
			],
			'crmmobile.kanban.entity.deal' => [
				'className' => '\\Bitrix\\CrmMobile\\Kanban\\Entity\\Deal',
			],
			'crmmobile.kanban.entity.quote' => [
				'className' => '\\Bitrix\\CrmMobile\\Kanban\\Entity\\Quote',
			],
			'crmmobile.kanban.entity.smartInvoice' => [
				'className' => '\\Bitrix\\CrmMobile\\Kanban\\Entity\\SmartInvoice',
			],
			'crmmobile.kanban.entity.dynamicTypeBasedStatic' => [
				'className' => '\\Bitrix\\CrmMobile\\Kanban\\Entity\\DynamicTypeBasedStatic',
			],
			'crmmobile.kanban.entity.dynamic' => [
				'className' => '\\Bitrix\\CrmMobile\\Kanban\\Entity\\Dynamic',
			],
			'crmmobile.kanban.entity.contact' => [
				'className' => '\\Bitrix\\CrmMobile\\Kanban\\Entity\\Contact',
			],
			'crmmobile.kanban.entity.company' => [
				'className' => '\\Bitrix\\CrmMobile\\Kanban\\Entity\\Company',
			],
		],
		'readonly' => true,
	],
];
