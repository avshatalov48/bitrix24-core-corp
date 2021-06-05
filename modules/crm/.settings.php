<?php
return array(
	'controllers' => array(
		'value' => array(
			'defaultNamespace' => '\\Bitrix\\Crm\\Controller',
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
			'crm.service.container' => [
				'className' => '\\Bitrix\\Crm\\Service\\Container',
			],
			'crm.service.localization' => [
				'className' => '\\Bitrix\\Crm\\Service\\Localization',
			],
			'crm.service.router' => [
				'className' => '\\Bitrix\\Crm\\Service\\Router',
			],
			'crm.service.context' => [
				'className' => '\\Bitrix\\Crm\\Service\\Context',
			],
			'crm.service.factory.quote' => [
				'className' => '\\Bitrix\\Crm\\Service\\Factory\\Quote',
			],
			'crm.service.factory.deal' => [
				'className' => '\\Bitrix\\Crm\\Service\\Factory\\Deal',
			],
			'crm.type.factory' => [
				'className' => '\\Bitrix\\Crm\\Model\\Dynamic\\Factory',
			],
			'crm.service.converter.ormObject' => [
				'className' => '\\Bitrix\\Crm\\Service\\Converter\\OrmObject',
			],
			'crm.service.converter.item' => [
				'className' => '\\Bitrix\\Crm\\Service\\Converter\\Item',
			],
			'crm.service.converter.stage' => [
				'className' => '\\Bitrix\\Crm\\Service\\Converter\\Stage',
			],
			'crm.service.broker.user' => [
				'className' => '\\Bitrix\\Crm\\Service\\Broker\\User',
			],
			'crm.service.broker.company' => [
				'className' => '\\Bitrix\\Crm\\Service\\Broker\\Company',
			],
			'crm.service.broker.contact' => [
				'className' => '\\Bitrix\\Crm\\Service\\Broker\\Contact',
			],
			'crm.service.director' => [
				'className' => '\\Bitrix\\Crm\\Service\\Director',
			],
			'crm.service.eventhistory' => [
				'className' => '\\Bitrix\\Crm\\Service\\EventHistory',
			],
			'crm.service.typesMap' => [
				'className' => '\\Bitrix\\Crm\\Service\\TypesMap',
			],
			'crm.service.dynamicTypesMap' => [
				'className' => '\\Bitrix\\Crm\\Service\\DynamicTypesMap',
			],
			'crm.relation.relationManager' => [
				'className' => '\\Bitrix\\Crm\\Relation\\RelationManager',
			],
			'crm.service.broker.typePreset' => [
				'className' => '\\Bitrix\\Crm\\Service\\Broker\\TypePreset',
			],
			'crm.service.parentFieldManager' => [
				'className' => '\\Bitrix\\Crm\\Service\\ParentFieldManager',
			],
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
			'crm.kanban.entity.dynamic' => [
				'className' => '\\Bitrix\\Crm\\Kanban\\Entity\\Dynamic',
			],
			'crm.integration.documentgeneratormanager' => [
				'className' => '\\Bitrix\\Crm\\Integration\\DocumentGeneratorManager',
			],
			'crm.integration.pullmanager' => [
				'className' => '\\Bitrix\\Crm\\Integration\\PullManager',
			],
			'crm.recycling.dynamicRelationManager' => [
				'className' => '\\Bitrix\\Crm\\Recycling\\DynamicRelationManager',
			],
			'crm.recycling.dynamicController' => [
				'className' => '\\Bitrix\\Crm\\Recycling\\DynamicController',
			]
		],
		'readonly' => true,
	],
	'ui.entity-selector' => [
		'value' => [
			'entities' => [
				[
					'entityId' => 'company',
					'provider' => [
						'moduleId' => 'crm',
						'className' => '\\Bitrix\\Crm\\Integration\\UI\\EntitySelector\\CompanyProvider'
					],
				],
				[
					'entityId' => 'deal',
					'provider' => [
						'moduleId' => 'crm',
						'className' => '\\Bitrix\\Crm\\Integration\\UI\\EntitySelector\\DealProvider'
					],
				],
				[
					'entityId' => 'lead',
					'provider' => [
						'moduleId' => 'crm',
						'className' => '\\Bitrix\\Crm\\Integration\\UI\\EntitySelector\\LeadProvider'
					],
				],
				[
					'entityId' => 'quote',
					'provider' => [
						'moduleId' => 'crm',
						'className' => '\\Bitrix\\Crm\\Integration\\UI\\EntitySelector\\QuoteProvider'
					],
				],
				[
					'entityId' => 'order',
					'provider' => [
						'moduleId' => 'crm',
						'className' => '\\Bitrix\\Crm\\Integration\\UI\\EntitySelector\\OrderProvider'
					],
				],
				[
					'entityId' => 'dynamic',
					'provider' => [
						'moduleId' => 'crm',
						'className' => '\\Bitrix\\Crm\\Integration\\UI\\EntitySelector\\DynamicProvider'
					],
				],
			],
		],
		'readonly' => true,
	],
	'userField' => [
		'value' => [
			'access' => '\\Bitrix\\Crm\\UserField\\Access',
		],
	],
	'intranet.customSection' => [
		'value' => [
			'provider' => '\\Bitrix\\Crm\\Integration\\Intranet\\CustomSectionProvider',
		],
	],
);
