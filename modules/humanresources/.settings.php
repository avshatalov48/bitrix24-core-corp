<?php

return [
	'services' => [
		'value' => [
			'humanresources.container' => [
				'className' => \Bitrix\HumanResources\Service\Container::class,
			],
			'humanresources.repository.node' => [
				'className' => \Bitrix\HumanResources\Repository\NodeRepository::class,
			],
			'humanresources.repository.node.access.code' => [
				'className' => \Bitrix\HumanResources\Repository\NodeAccessCodeRepository::class,
			],
			'humanresources.repository.role' => [
				'className' => \Bitrix\HumanResources\Repository\RoleRepository::class,
			],
			'humanresources.service.role.helper' => [
				'className' => \Bitrix\HumanResources\Service\RoleHelperService::class,
			],
			'humanresources.repository.node.member' => [
				'className' => \Bitrix\HumanResources\Repository\NodeMemberRepository::class,
			],
			'humanresources.repository.node.relation' => [
				'className' => \Bitrix\HumanResources\Repository\NodeRelationRepository::class,
			],
			'humanresources.repository.structure' => [
				'className' => \Bitrix\HumanResources\Repository\StructureRepository::class,
			],
			'humanresources.service.semaphore' => [
				'className' => \Bitrix\HumanResources\Service\SimpleSemaphoreService::class,
			],
			'humanresources.service.node.member' => [
				'className' => \Bitrix\HumanResources\Service\NodeMemberService::class,
			],
			'humanresources.service.event.sender' => [
				'className' => \Bitrix\HumanResources\Service\EventSenderService::class,
			],
			'humanresources.service.structure.walker' => [
				'className' => \Bitrix\HumanResources\Service\StructureWalkerService::class,
			],
			'humanresources.service.node' => [
				'className' => \Bitrix\HumanResources\Service\NodeService::class,
			],
			'humanresources.service.node.relation' => [
				'className' => \Bitrix\HumanResources\Service\NodeRelationService::class,
			],
			'humanresources.util.cache' => [
				'className' => \Bitrix\HumanResources\Util\CacheManager::class
			],
			'humanresources.service.access.rolePermission' => [
				'className' => \Bitrix\HumanResources\Service\Access\RolePermissionService::class,
			],
			'humanresources.service.access.roleRelation' => [
				'className' => \Bitrix\HumanResources\Service\Access\RoleRelationService::class,
			],
			'humanresources.repository.access.permission' => [
				'className' => \Bitrix\HumanResources\Repository\Access\PermissionRepository::class,
			],
			'humanresources.repository.access.role' => [
				'className' => \Bitrix\HumanResources\Repository\Access\RoleRepository::class,
			],
			'humanresources.repository.access.roleRelation' => [
				'className' => \Bitrix\HumanResources\Repository\Access\RoleRelationRepository::class,
			],
			'humanresources.compatibility.converter' => [
				'className' => \Bitrix\HumanResources\Compatibility\Converter\StructureBackwardConverter::class,
			],
			'humanresources.compatibility.converter.user' => [
				'className' => \Bitrix\HumanResources\Compatibility\Converter\UserBackwardConverter::class,
			],
			'humanresources.util.database.logger' => [
				'className' => \Bitrix\HumanResources\Util\DatabaseLogger::class
			],
			'humanresources.service.user' => [
				'className' => \Bitrix\HumanResources\Service\UserService::class,
			],
			'humanresources.repository.user' => [
				'className' => \Bitrix\HumanResources\Repository\UserRepository::class,
			],
			'humanresources.helper.node.member.counter' => [
				'className' => \Bitrix\HumanResources\Util\NodeMemberCounterHelper::class,
			],
			'humanresources.repository.access.accessNodeRepository' => [
				'className' => \Bitrix\HumanResources\Repository\Access\AccessNodeRepository::class
			],
		]
	],
	'controllers' => [
		'value' => [
			'namespaces' => [
				'\\Bitrix\\HumanResources\\Controller' => 'api',
			],
			'defaultNamespace' => '\\Bitrix\\HumanResources\\Controller'
		],
		'readonly' => true
	],
];
