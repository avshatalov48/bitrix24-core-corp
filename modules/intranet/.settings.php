<?php
return [
	'controllers' => [
		'value' => [
			'defaultNamespace' => '\\Bitrix\\Intranet\\Controller',
			'namespaces' => [
				'\\Bitrix\\Intranet\\Infrastructure\\Controller' => 'v2',
			],
			'restIntegration' => [
				'enabled' => true,
			],
		],
		'readonly' => true,
	],
	'ui.selector' => [
		'value' => [
			'intranet.selector'
		],
		'readonly' => true,
	],
	'ui.entity-selector' => [
		'value' => [
			'entities' => [
				[
					'entityId' => 'department',
					'provider' => [
						'moduleId' => 'intranet',
						'className' => '\\Bitrix\\Intranet\\Integration\\UI\\EntitySelector\\DepartmentProvider'
					],
				],
			],
			'extensions' => ['intranet.entity-selector'],
		],
		'readonly' => true,
	],
	'services' => [
		'value' => [
			'intranet.customSection.manager' => [
				'className' => '\\Bitrix\\Intranet\\CustomSection\\Manager',
			],
			'intranet.customSection.provider.registry' => [
				'className' => '\\Bitrix\\Intranet\\CustomSection\\Provider\\Registry',
			],
			'intranet.customSection.repository' => [
				'className' => '\\Bitrix\\Intranet\\CustomSection\\Repository',
			],
			'intranet.option.mobile_app' => [
				'constructor' => function () {
					return new \Bitrix\Intranet\Service\MobileAppSettings(
						new \Bitrix\Intranet\Service\IntranetOption(),
						\Bitrix\Intranet\CurrentUser::get(),
						new \Bitrix\Intranet\Internal\Service\EntitySelectorCodeService(),
					);
				},
			],
			'intranet.repository.iblock.department' => [
				'className' => \Bitrix\Intranet\Repository\IblockDepartmentRepository::class,
			],
			'intranet.repository.hr.department' => [
				'className' => \Bitrix\Intranet\Repository\HrDepartmentRepository::class,
			],
			'intranet.repository.department' => [
				'constructor' => function () {
					if (\Bitrix\Main\Loader::includeModule('humanresources')
					&& (new \Bitrix\Intranet\Service\IntranetOption)->get('humanresources_enabled') === 'Y')
					{
						return new \Bitrix\Intranet\Repository\HrDepartmentRepository();
					}

					return new \Bitrix\Intranet\Repository\IblockDepartmentRepository();
				}
			],
			'intranet.repository.invitation' => [
				'className' => \Bitrix\Intranet\Internal\Repository\InvitationRepository::class,
			],
			'intranet.repository.invitation.link' => [
				'className' => \Bitrix\Intranet\Repository\InvitationLinkRepository::class,
			],
			'intranet.service.invitation.token' => [
				'className' => \Bitrix\Intranet\Service\InviteTokenService::class,
			],
			'intranet.repository.user' => [
				'className' => \Bitrix\Intranet\Repository\UserRepository::class,
			],
			'intranet.service.user' => [
				'className' => \Bitrix\Intranet\Service\UserService::class,
			],
//			'intranet.service.invitation' => [
//				'constructor' => fn () => \Bitrix\Intranet\Service\InviteService::createByDefault(),
//			],
			'intranet.service.user.mini-profile' => [
				'className' => \Bitrix\Intranet\Service\User\MiniProfileService::class,
				'constructorParams' => static fn() => [
					'chatFacade' => new \Bitrix\Intranet\Integration\Im\ChatFacade(),
					'departmentService' => new \Bitrix\Intranet\Integration\HumanResources\Department(),
					'teamService' => new \Bitrix\Intranet\Internal\Integration\Humanresources\TeamRepository(),
				],
			],
			'intranet.service.invite.status' => [
				'className' => \Bitrix\Intranet\Service\InviteStatusService::class
			],
			'intranet.portal.settings.name' => [
				'constructor' => function () {
					return new \Bitrix\Intranet\Portal\Settings\PortalNameSettings(
						new \Bitrix\Intranet\Service\SiteOption(SITE_ID, 'main')
					);
				},
			],
			'intranet.portal.settings.title' => [
				'constructor' => function () {
					return new \Bitrix\Intranet\Portal\Settings\PortalTitleSettings(
						new \Bitrix\Intranet\Service\SiteOption(SITE_ID, 'bitrix24')
					);
				},
			],
			'intranet.portal.settings.logo' => [
				'constructor' => function () {
					return new \Bitrix\Intranet\Portal\Settings\LogoSettings(
						new \Bitrix\Intranet\Service\SiteOption(SITE_ID, 'bitrix24')
					);
				},
			],
			'intranet.portal.settings.logo24' => [
				'constructor' => function () {
					return new \Bitrix\Intranet\Portal\Settings\Logo24Settings(
						new \Bitrix\Intranet\Service\SiteOption(SITE_ID, 'bitrix24')
					);
				},
			],
		],
		'readonly' => true,
	],
];