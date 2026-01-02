<?php

use Bitrix\HumanResources\Repository\NodeSettingsRepository;
use Bitrix\HumanResources\Type\StructureAction;

return [
	'services' => [
		'value' => [
			'humanresources.container' => [
				'className' => \Bitrix\HumanResources\Service\Container::class,
			],
			'humanresources.public.container' => [
				'className' => \Bitrix\HumanResources\Public\Service\Container::class,
			],
			'humanresources.internal.container' => [
				'className' => \Bitrix\HumanResources\Internals\Service\Container::class,
			],
			'humanresources.service.internal.nodeChatService' => [
				'className' => \Bitrix\HumanResources\Internals\Service\Structure\NodeChatService::class,
			],
			'humanresources.service.internal.nodeCollabService' => [
				'className' => \Bitrix\HumanResources\Internals\Service\Structure\NodeCollabService::class,
			],
			'humanresources.internal.service.nodeMemberService' => [
				'className' => \Bitrix\HumanResources\Internals\Service\Structure\NodeMemberService::class,
			],
			'humanresources.service.internal.nodeCollabService' => [
				'className' => \Bitrix\HumanResources\Internals\Service\Structure\NodeCollabService::class,
			],
			'humanresources.service.internal.nodeSettingsService' => [
				'className' => \Bitrix\HumanResources\Internals\Service\Structure\NodeSettingsService::class,
			],
			'humanresources.service.internal.accessService' => [
				'className' => \Bitrix\HumanResources\Internals\Service\Structure\AccessService::class,
			],
			'humanresources.repository.node' => [
				'className' => \Bitrix\HumanResources\Repository\NodeRepository::class,
			],
			'humanresources.repository.permission.restricted.node' => [
				'className' => \Bitrix\HumanResources\Repository\Access\PermissionRestrictedNodeRepository::class,
			],
			'humanresources.repository.createPermission.restricted.node' => [
				'className' => \Bitrix\HumanResources\Repository\Access\PermissionRestrictedNodeRepository::class,
				'constructorParams' => static function() {
					return [
						'structureAction' => StructureAction::CreateAction,
					];
				},
			],
			'humanresources.repository.updatePermission.restricted.node' => [
				'className' => \Bitrix\HumanResources\Repository\Access\PermissionRestrictedNodeRepository::class,
				'constructorParams' => static function() {
					return [
						'structureAction' => StructureAction::UpdateAction,
					];
				},
			],
			'humanresources.repository.deletePermission.restricted.node' => [
				'className' => \Bitrix\HumanResources\Repository\Access\PermissionRestrictedNodeRepository::class,
				'constructorParams' => static function() {
					return [
						'structureAction' => StructureAction::DeleteAction,
					];
				},
			],
			'humanresources.repository.addEmployeePermission.restricted.node' => [
				'className' => \Bitrix\HumanResources\Repository\Access\PermissionRestrictedNodeRepository::class,
				'constructorParams' => static function() {
					return [
						'structureAction' => StructureAction::AddMemberAction,
					];
				},
			],
			'humanresources.repository.removeEmployeePermission.restricted.node' => [
				'className' => \Bitrix\HumanResources\Repository\Access\PermissionRestrictedNodeRepository::class,
				'constructorParams' => static function() {
					return [
						'structureAction' => StructureAction::RemoveMemberAction,
					];
				},
			],
			'humanresources.repository.inviteEmployeePermission.restricted.node' => [
				'className' => \Bitrix\HumanResources\Repository\Access\PermissionRestrictedNodeRepository::class,
				'constructorParams' => static function() {
					return [
						'structureAction' => StructureAction::InviteUserAction,
					];
				},
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
			'humanresources.service.node.branch' => [
				'className' => \Bitrix\HumanResources\Service\NodeBranchService::class,
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
			'humanresources.service.public.nodeSettings' => [
				'className' => \Bitrix\HumanResources\Public\Service\NodeSettingsService::class,
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
			'humanresources.service.hcmlink.field' => [
				'className' => \Bitrix\HumanResources\Service\HcmLink\FieldService::class,
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
			'humanresources.service.member.departmentUserSearchService' => [
				'className' => \Bitrix\HumanResources\Service\Member\DepartmentUserSearchService::class,
			],
			'humanresources.repository.node.path' => [
				'className' => \Bitrix\HumanResources\Repository\NodePathRepository::class,
			],
			'humanresources.repository.nodeSettings' => [
				'className' => \Bitrix\HumanResources\Repository\NodeSettingsRepository::class,
			],
			'humanresources.service.access.structure.structureAccessService' => [
				'className' => \Bitrix\HumanResources\Service\Access\Structure\StructureAccessService::class,
			],
			'humanresources.intergation.im.chatService' => [
				'className' => \Bitrix\HumanResources\Integration\Im\ChatService::class,
			],
			'humanresources.intergation.socialnetwork.collabService' => [
				'className' => \Bitrix\HumanResources\Integration\Socialnetwork\CollabService::class,
			],
			'humanresources.intergation.pull.pushMessageService' => [
				'className' => \Bitrix\HumanResources\Integration\Pull\PushMessageService::class,
			],
			'humanresources.access.authProvider.structureAuthProvider' => [
				'className' => \Bitrix\HumanResources\Access\AuthProvider\StructureAuthProvider::class,
			],
			'humanresources.service.public.team.userService' => [
				'className' => \Bitrix\HumanResources\Public\Service\Team\UserService::class,
			],
			'humanresources.service.public.department.userService' => [
				'className' => \Bitrix\HumanResources\Public\Service\Department\UserService::class,
			],
			'humanresources.repository.internal.nodeRepository' => [
				'className' => \Bitrix\HumanResources\Internals\Repository\Structure\Node\NodeRepository::class,
			],
			'humanresources.internal.repository.structure.nodeAccessCode' => [
				'className' => \Bitrix\HumanResources\Internals\Repository\Structure\NodeAccessCodeRepository::class,
			],
			'humanresources.internal.service.structure.nodeAccessService' => [
				'className' => \Bitrix\HumanResources\Internals\Service\Structure\NodeAccessCodeService::class,
			],
			'humanresources.internal.repository.structure.node.nodeMemberRepository' => [
				'className' => \Bitrix\HumanResources\Internals\Repository\Structure\Node\NodeMemberRepository::class,
			],
			'humanresources.public.service.node.userService' => [
				'className' => \Bitrix\HumanResources\Public\Service\Node\UserService::class,
			],
		],
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
						'className' => \Bitrix\HumanResources\Integration\UI\DepartmentProvider::class,
					],
				],
				[
					'entityId' => 'structure-role',
					'provider' => [
						'moduleId' => 'humanresources',
						'className' => \Bitrix\HumanResources\Integration\UI\StructureRoleProvider::class,
					],
					'options' => [
						'dynamicLoad' => true,
						'dynamicSearch' => true,
					],
				],
				[
					'entityId' => 'user-groups',
					'provider' => [
						'moduleId' => 'humanresources',
						'className' => \Bitrix\HumanResources\Integration\UI\EntitySelector\UserGroupProvider::class,
					],
				],
				[
					'entityId' => 'hcmlink-person-data',
					'provider' => [
						'moduleId' => 'humanresources',
						'className' => \Bitrix\HumanResources\Integration\UI\EntitySelector\HcmLink\PersonDataProvider::class,
					],
				],
				[
					'entityId' => 'structure-node-role',
					'provider' => [
						'moduleId' => 'humanresources',
						'className' => \Bitrix\HumanResources\Integration\UI\RoleProvider::class,
					],
					'options' => [
						'dynamicLoad' => true,
						'dynamicSearch' => true,
					],
				],
			],
			'extensions' => ['humanresources.entity-selector'],
		],
		'readonly' => true,
	],
];
