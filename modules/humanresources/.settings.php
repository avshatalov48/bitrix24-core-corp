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
			'humanresources.repository.permission.restricted.node' => [
				'className' => \Bitrix\HumanResources\Repository\Access\PermissionRestrictedNodeRepository::class,
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
			'humanresources.repository.hcmlink.company' => [
				'className' => \Bitrix\HumanResources\Repository\HcmLink\CompanyRepository::class,
			],
			'humanresources.repository.hcmlink.field' => [
				'className' => \Bitrix\HumanResources\Repository\HcmLink\FieldRepository::class,
			],
			'humanresources.repository.hcmlink.person' => [
				'className' => \Bitrix\HumanResources\Repository\HcmLink\PersonRepository::class,
			],
			'humanresources.repository.hcmlink.employee' => [
				'className' => \Bitrix\HumanResources\Repository\HcmLink\EmployeeRepository::class,
			],
			'humanresources.repository.hcmlink.field.value' => [
				'className' => \Bitrix\HumanResources\Repository\HcmLink\FieldValueRepository::class,
			],
			'humanresources.repository.hcmlink.job' => [
				'className' => \Bitrix\HumanResources\Repository\HcmLink\JobRepository::class,
			],
			'humanresources.repository.hcmlink.user' => [
				'className' => \Bitrix\HumanResources\Repository\HcmLink\UserRepository::class,
			],
			'humanresources.service.hcmlink.field.value' => [
				'className' => \Bitrix\HumanResources\Service\HcmLink\FieldValueService::class,
			],
			'humanresources.service.hcmlink.job' => [
				'className' => \Bitrix\HumanResources\Service\HcmLink\JobService::class,
				'constructorParams' => static function() {
					return [
						'jobRepository' => \Bitrix\HumanResources\Service\Container::getHcmLinkJobRepository(),
						'companyRepository' => \Bitrix\HumanResources\Service\Container::getHcmLinkCompanyRepository(),
						'fieldRepository' => \Bitrix\HumanResources\Service\Container::getHcmLinkFieldRepository(),
					];
				},
			],
			'humanresources.service.hcmlink.mapper' => [
				'className' => \Bitrix\HumanResources\Service\HcmLink\MapperService::class,
				'constructorParams' => static function() {
					return [
						'companyRepository' => \Bitrix\HumanResources\Service\Container::getHcmLinkCompanyRepository(),
						'personRepository' => \Bitrix\HumanResources\Service\Container::getHcmLinkPersonRepository(),
						'employeeRepository' => \Bitrix\HumanResources\Service\Container::getHcmLinkEmployeeRepository(),
					];
				},
			],
			'humanresources.service.hcmlink.counter.company' => [
				'className' => \Bitrix\HumanResources\Service\HcmLink\Counter\CompanyCounterService::class,
				'constructorParams' => static function() {
					return [
						'personRepository' => \Bitrix\HumanResources\Service\Container::getHcmLinkPersonRepository(),
					];
				},
			],
			'humanresources.service.hcmlink.access' => [
				'className' => \Bitrix\HumanResources\Service\HcmLink\AccessService::class,
			],
			'humanresources.service.hcmlink.job.killer' => [
				'className' => \Bitrix\HumanResources\Service\HcmLink\JobKillerService::class,
			],
			'humanresources.service.hcmlink.placement.salaryAndVacation' => [
				'className' => \Bitrix\HumanResources\Service\HcmLink\Placement\SalaryVacationService::class,
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
	'ui.entity-selector' => [
		'value' => [
			'entities' => [
				[
					'options' => [
						'dynamicLoad' => true,
						'dynamicSearch' => true,
					],
					'entityId' => 'structure-node',
					'provider' => [
						'moduleId' => 'humanresources',
						'className' => \Bitrix\HumanResources\Integration\UI\DepartmentProvider::class
					],
				],
				[
					'entityId' => 'hcmlink-person-data',
					'provider' => [
						'moduleId' => 'humanresources',
						'className' => \Bitrix\HumanResources\Ui\EntitySelector\HcmLink\PersonDataProvider::class,
					],
				],
			],
			'extensions' => ['humanresources.entity-selector'],
		],
		'readonly' => true,
	],
];
