<?php

use Bitrix\Sign\Access\Model\UserModelRepository;
use Bitrix\Sign\Access\Service\AccessService;
use Bitrix\Sign\Service;
use Bitrix\Sign\Config;
use Bitrix\Sign\Connector;
use Bitrix\Sign\Repository;
use Bitrix\Sign\Callback;
use Bitrix\Sign\Service\Sign\Document\GroupService;
use Bitrix\Sign\Service\CounterService;
use Bitrix\Sign\Util;

return [
	'controllers' => [
		'value' => [
			'namespaces' => [
				'\\Bitrix\\Sign\\Controller' => 'api',
				'\\Bitrix\\Sign\\Controllers\\V1' => 'api_v1',
			],
			'defaultNamespace' => '\\Bitrix\\Sign\\Controller'
		],
		'readonly' => true
	],
	'ui.uploader' => [
		'value' => [
			'allowUseControllers' => true,
		],
		'readonly' => true,
	],
	'service.address' => [
		'value' => [
			'by' => 'https://sign.bitrix24.ru',
			'ru' => 'https://sign.bitrix24.ru',

			'eu' => 'https://sign.bitrix24.eu',
			'de' => 'https://sign.bitrix24.eu',
			'fr' => 'https://sign.bitrix24.eu',
			'it' => 'https://sign.bitrix24.eu',
			'pl' => 'https://sign.bitrix24.eu',
			'uk' => 'https://sign.bitrix24.eu',
			'ur' => 'https://sign.bitrix24.eu',

			'us' => 'https://sign.bitrix24.com',
			'en' => 'https://sign.bitrix24.com',
			'br' => 'https://sign.bitrix24.com',
			'la' => 'https://sign.bitrix24.com',
			'tr' => 'https://sign.bitrix24.com',
			'jp' => 'https://sign.bitrix24.com',
			'tc' => 'https://sign.bitrix24.com',
			'sc' => 'https://sign.bitrix24.com',
			'hi' => 'https://sign.bitrix24.com',
			'vn' => 'https://sign.bitrix24.com',
			'id' => 'https://sign.bitrix24.com',
			'ms' => 'https://sign.bitrix24.com',
			'th' => 'https://sign.bitrix24.com',
			'cn' => 'https://sign.bitrix24.com',
			'in' => 'https://sign.bitrix24.com',
			'co' => 'https://sign.bitrix24.com',
			'mx' => 'https://sign.bitrix24.com',
		],
		'readonly' => true,
	],
	'bitrix.service.domain.address' => [
		'value' => [
			'by' => 'https://www.bitrix24.ru',
			'ru' => 'https://www.bitrix24.by',

			'eu' => 'https://www.bitrix24.eu',
			'de' => 'https://www.bitrix24.de',
			'fr' => 'https://www.bitrix24.fr',
			'it' => 'https://www.bitrix24.it',
			'pl' => 'https://www.bitrix24.pl',
			'uk' => 'https://www.bitrix24.uk',

			'us' => 'https://www.bitrix24.com',
			'en' => 'https://www.bitrix24.com',
			'es' => 'https://www.bitrix24.es',
			'br' => 'https://www.bitrix24.com.br',
			'la' => 'https://www.bitrix24.com',
			'tr' => 'https://www.bitrix24.com.tr',
			'jp' => 'https://www.bitrix24.jp',
			'tc' => 'https://www.bitrix24.cn',
			'sc' => 'https://www.bitrix24.com',
			'hi' => 'https://www.bitrix24.com',
			'vn' => 'https://www.bitrix24.vn',
			'id' => 'https://www.bitrix24.id',
			'ms' => 'https://www.bitrix24.com',
			'th' => 'https://www.bitrix24.com',
			'cn' => 'https://www.bitrix24.cn',
			'in' => 'https://www.bitrix24.in',
			'co' => 'https://www.bitrix24.co',
			'mx' => 'https://www.bitrix24.mx',
		],
		'readonly' => true,
	],
	'service.b2e.publicity' => [
		'value' => [
			'ru' => true,
			'by' => false,
			'eu' => true,
			'de' => true,
			'fr' => true,
			'it' => true,
			'pl' => true,
			'uk' => true,
			'ur' => true,
			'us' => true,
			'en' => true,
			'br' => true,
			'la' => true,
			'tr' => true,
			'jp' => true,
			'tc' => true,
			'sc' => true,
			'hi' => true,
			'vn' => true,
			'id' => true,
			'ms' => true,
			'th' => true,
			'cn' => true,
			'in' => true,
			'co' => true,
			'mx' => true,
		],
		'readonly' => true,
	],
	'service.b2e.init-by-employee.publicity' => [
		'value' => [
			'ru' => true,
			'by' => false,
			'eu' => false,
			'de' => false,
			'fr' => false,
			'it' => false,
			'pl' => false,
			'uk' => false,
			'ur' => false,
			'us' => false,
			'en' => false,
			'br' => false,
			'la' => false,
			'tr' => false,
			'jp' => false,
			'tc' => false,
			'sc' => false,
			'hi' => false,
			'vn' => false,
			'id' => false,
			'ms' => false,
			'th' => false,
			'cn' => false,
			'in' => false,
			'co' => false,
			'mx' => false,
		],
		'readonly' => true,
	],
	'service.publicity' => [
		'value' => [
			'ru' => true,
			'by' => true,
			'eu' => true,
			'de' => true,
			'fr' => true,
			'it' => true,
			'pl' => true,
			'uk' => true,
			'ur' => true,
			'us' => true,
			'en' => true,
			'br' => true,
			'la' => true,
			'tr' => true,
			'jp' => true,
			'tc' => true,
			'sc' => true,
			'hi' => true,
			'vn' => true,
			'id' => true,
			'ms' => true,
			'th' => true,
			'cn' => true,
			'in' => true,
			'co' => true,
			'mx' => true,
		],
		'readonly' => true,
	],
	'service.doc.link' => [
		'value' => '#address#/#doc_hash#/#member_hash#/',
		'readonly' => true,
	],
	'services' => [
		'value' => [
			'sign.service.integration.crm.document' => [
				'className' => \Bitrix\Sign\Service\Integration\Crm\BaseDocumentService::class,
				'constructorParams' => static function() {
					return [
						'signDocumentService' => Service\Container::instance()->getDocumentService(),
						'blankService' => Service\Container::instance()->getSignBlankService(),
						'memberService' => Service\Container::instance()->getMemberService(),
					];
				},
			],
			'sign.service.integration.crm.kanban.b2e.entity' => [
				'className' => Service\Integration\Crm\Kanban\B2e\EntityService::class,
			],
			'sign.service.analytic.analytic' => [
				'className' => Service\Analytic\AnalyticService::class,
			],
			'sign.service.counter' => [
				'className' => CounterService::class,
			],
			'sign.service.b2e.kanbanCategory' => [
				'className' => Service\Sign\B2e\KanbanCategoryService::class,
			],
			'sign.service.integration.crm.b2e.document' => [
				'className' => Service\Integration\Crm\B2eDocumentService::class
			],
			'sign.service.integration.im' => [
				'className' => Service\Integration\Im\ImService::class
			],
			'sign.service.hrbotmessage' => [
				'className' => Service\HrBotMessageService::class
			],
			'sign.service.integration.im.groupChat' => [
				'className' => Service\Integration\Im\GroupChatService::class
			],
			'sign.service.sign.documentChat.chatTypeConverter' => [
				'className' => Service\Sign\DocumentChat\ChatTypeConverterService::class
			],
			'sign.service.integration.crm.events' => [
				'className' => \Bitrix\Sign\Service\Integration\Crm\EventHandlerService::class,
			],
			'sign.service.integration.signmobile.member' => [
				'className' => \Bitrix\Sign\Service\Integration\SignMobile\MemberService::class,
			],
			'sign.container' => [
				'className' => Service\Container::class,
			],
			'sign.service.api' => [
				'className' => Service\ApiService::class,
				'constructorParams' => static function() {
					return [
						'apiEndpoint' => Config\Storage::instance()->getApiEndpoint()
					];
				},
			],
			'sign.service.api.document' => [
				'className' => Service\Api\DocumentService::class,
				'constructorParams' => static function() {
					return [
						'api' => Service\Container::instance()->getApiService(),
						'serializer' => new \Bitrix\Sign\Serializer\ItemPropertyJsonSerializer
					];
				},
			],
			'sign.service.api.client.domain' => [
				'className' => Service\Api\Client\Domain::class,
				'constructorParams' => static function() {
					return [
						'api' => Service\Container::instance()->getApiService(),
						'serializer' => new \Bitrix\Sign\Serializer\ItemPropertyJsonSerializer
					];
				},
			],
			'sign.service.user' => [
				'className' => Service\UserService::class,
			],
			'sign.repository.document' => [
				'className' => Repository\DocumentRepository::class,
			],
			'sign.repository.entity.file' => [
				'className' => Repository\EntityFileRepository::class,
			],
			'sign.repository.blank' => [
				'className' => Repository\BlankRepository::class,
			],
			'sign.repository.blank.resource' => [
				'className' => Repository\Blank\ResourceRepository::class,
			],
			'sign.repository.block' => [
				'className' => Repository\BlockRepository::class,
				'constructorParams' => static function() {
					return [
						'serializer' => new \Bitrix\Sign\Serializer\ItemPropertyJsonSerializer(),
					];
				},
			],
			'sign.service.api.document.page' => [
				'className' => Service\Api\Document\PageService::class,
				'constructorParams' => static function() {
					return [
						'api' => Service\Container::instance()->getApiService(),
					];
				},
			],
			'sign.service.api.document.signed.file.load' => [
				'className' => Service\Api\Document\SignedFileLoadService::class,
				'constructorParams' => static function() {
					return [
						'api' => Service\Container::instance()->getApiService(),
					];
				},
			],
			'sign.service.api.document.signing' => [
				'className' => Service\Api\Document\SigningService::class,
				'constructorParams' => static function() {
					return [
						'api' => Service\Container::instance()->getApiService(),
						'serializer' => new \Bitrix\Sign\Serializer\ItemPropertyJsonSerializer
					];
				},
			],
			'sign.service.api.document.field' => [
				'className' => Service\Api\Document\FieldService::class,
				'constructorParams' => static function() {
					return [
						'api' => Service\Container::instance()->getApiService(),
						'serializer' => new \Bitrix\Sign\Serializer\ItemPropertyJsonSerializer
					];
				},
			],
			'sign.service.api.mobile' => [
				'className' => Service\Api\MobileService::class,
				'constructorParams' => static function() {
					return [
						'api' => Service\Container::instance()->getApiService(),
						'serializer' => new \Bitrix\Sign\Serializer\ItemPropertyJsonSerializer
					];
				},
			],
			'sign.service.api.external-sign-provider' => [
				'className' => Service\Api\B2e\ExternalSignProviderService::class,
				'constructorParams' => static fn() => [
						'api' => Service\Container::instance()->getApiService(),
				],
			],
			'sign.service.sign.blank.file' => [
				'className' => Service\Sign\BlankFileService::class,
			],
			'sign.service.sign.blank' => [
				'className' => Service\Sign\BlankService::class,
			],
			'sign.service.sign.document' => [
				'className' => Service\Sign\DocumentService::class,
			],
			'sign.service.pull' => [
				'className' => Service\PullService::class,
			],
			'sign.service.sign.block' => [
				'className' => Service\Sign\BlockService::class,
			],
			'sign.repository.member' => [
				'className' => Repository\MemberRepository::class,
			],
			'sign.repository.documentChat' => [
				'className' => Repository\DocumentChatRepository::class,
			],
			'sign.repository.membernode' => [
				'className' => Repository\MemberNodeRepository::class,
			],
			'sign.service.sign.document.agent' => [
				'className' =>  Service\Sign\DocumentAgentService::class,
			],
			'sign.service.integration.im.notification' => [
				'className' =>  Service\Integration\Im\NotificationService::class,
			],
			'sign.repository.file' => [
				'className' => Repository\FileRepository::class,
				// TODO: 'autowire' => true,
				'constructorParams' => static function() {
					return [
						'path' => 'sign_doc_projects',
					];
				},
			],
			'sign.service.mobile' => [
				'className' => Service\MobileService::class,
			],
			'sign.service.sign.member' => [
				'className' => Service\Sign\MemberService::class,
			],
			'sign.service.sign.document.filename' => [
				'className' => Service\Sign\DocumentFileNameService::class,
			],
			'sign.connector.field.factory' => [
				'className' => Connector\FieldConnectorFactory::class,
				'constructorParams' => static fn () => [
					'memberConnectorFactory' => Service\Container::instance()->getMemberConnectorFactory(),
					'fileRepository' => Service\Container::instance()->getFileRepository(),
					'documentRepository' => Service\Container::instance()->getDocumentRepository(),
				],
			],
			'sign.connector.member.factory' => [
				'className' => Connector\MemberConnectorFactory::class,
			],
			'sign.connector.document.factory' => [
				'className' => Connector\DocumentConnectorFactory::class,
			],
			'sign.accessibleItem.factory' => [
				'className' => \Bitrix\Sign\Factory\Access\AccessibleItemFactory::class,
			],
			'sign.repository.service_user' => [
				'className' => Repository\ServiceUserRepository::class,
			],
			'sign.service.api.b2e.user' => [
				'className' => Service\Api\B2e\UserService::class,
				'constructorParams' => static fn() => [
					'api' => Service\Container::instance()->getApiService(),
					'repository' =>  Service\Container::instance()->getServiceUserRepository(),
				],
			],
			'sign.service.api.member.webStatus' => [
				'className' => Service\Api\Member\WebStatusService::class,
				'constructorParams' => static fn() => [
					'api' => Service\Container::instance()->getApiService(),
				],
			],
			'sign.service.provider.profile' => [
				'className' => Service\Providers\ProfileProvider::class,
			],
			'sign.service.sign.member.user' => [
				'className' => Service\Sign\Member\UserService::class,
			],
			'sign.service.sign.url.generator' => [
				'className' => Service\Sign\UrlGeneratorService::class,
			],
			'sign.service.document.template' => [
				'className' => Service\Sign\Document\TemplateService::class,
			],
			'sign.service.api.b2e.providerFields' => [
				'className' => Service\Api\B2e\ProviderFieldsService::class,
				'constructorParams' => static fn() => [
					'api' => Service\Container::instance()->getApiService(),
				],
			],
			'sign.service.api.b2e.providerSchemes' => [
				'className' => Service\Api\B2e\ProviderSchemesService::class,
				'constructorParams' => static fn() => [
					'api' => Service\Container::instance()->getApiService(),
				],
			],
			'sign.service.api.b2e.providerCode' => [
				'className' => Service\Api\B2e\ProviderCodeService::class,
				'constructorParams' => static fn() => [
					'api' => Service\Container::instance()->getApiService(),
				],
			],
			'sign.service.sign.document.providerCode' => [
				'className' => Service\Sign\Document\ProviderCodeService::class,
			],
			'sign.callback.handler' => [
				'className' => Callback\Handler::class,
				'constructorParams' => static fn() => [
					'documentRepository' => Service\Container::instance()->getDocumentRepository(),
					'memberRepository' => Service\Container::instance()->getMemberRepository(),
				],
			],
			'sign.repository.region_document_type' => [
				'className' => Repository\RegionDocumentTypeRepository::class,
			],
			'sign.repository.user' => [
				'className' => Repository\UserRepository::class,
			],
			'sign.repository.legal_log' => [
				'className' => Repository\LegalLogRepository::class,
			],
			'sign.service.sign.legal_log' => [
				'className' => Service\Sign\LegalLogService::class,
				'constructorParams' => static fn() => [
					'logRepository' => Service\Container::instance()->getLegalLogRepository(),
					'userService' => Service\Container::instance()->getUserService(),
					'memberService' => Service\Container::instance()->getMemberService(),
				],
			],
			'sign.service.access.rolePermission' => [
				'constructor' => static function() {
					return \Bitrix\Main\Loader::includeModule('crm')
						? new \Bitrix\Sign\Access\Service\RolePermissionService()
						: null
					;
				},
			],
			'sign.access.service.access' => [
				'className' => AccessService::class,
			],
			'sign.service.document.group' => [
				'className' => GroupService::class,
			],
			'sign.repository.required_field' => [
				'className' => Repository\RequiredFieldRepository::class,
			],
			'sign.repository.document.template' => [
				'className' => Repository\Document\TemplateRepository::class,
			],
			'sign.repository.document.group' => [
				'className' => Repository\Document\GroupRepository::class,
			],
			'sign.service.license' => [
				'className' => Service\LicenseService::class,
				'constructorParams' => static fn() => [
					'cacheManager' => Service\Container::instance()->getCacheManager(),
				],
			],
			'sign.service.b2e.tariffRestriction' => [
				'className' => Service\B2e\B2eTariffRestrictionService::class,
				'constructorParams' => static fn() => [
					'licenseService' => Service\Container::instance()->getLicenseService(),
				],
			],
			'sign.util.cache' => [
				'className' => Util\MainCache::class,
			],
			'sign.service.block.frontendBlock' => [
				'className' => Service\Sign\Block\FrontendBlockService::class,
			],
			'sign.service.integration.crm.myCompany' => [
				'className' => Service\Integration\Crm\MyCompanyService::class,
			],
			'sign.service.provider.memberDynamic' => [
				'className' => Service\Providers\MemberDynamicFieldInfoProvider::class,
			],
			'sign.repository.fieldValue' => [
				'className' => Repository\FieldValueRepository::class,
			],
			'sign.service.integration.humanresources.hcmlink' => [
				'className' => Service\Integration\HumanResources\HcmLinkService::class,
			],
			'sign.service.integration.humanresources.hcmlink.field' => [
				'className' => Service\Integration\HumanResources\HcmLinkFieldService::class,
			],
			'sign.service.integration.humanresources.hcmlink.signedFile' => [
				'className' => Service\Integration\HumanResources\HcmLinkSignedFileService::class,
			],
			'sign.service.provider.legal' => [
				'className' => Service\Providers\LegalInfoProvider::class,
			],
			'sign.service.sign.permissions' => [
				'className' => Service\Sign\PermissionsService::class,
			],
			'sign.service.b2e.myDocumentsGrid.data' => [
				'className' => Service\B2e\MyDocumentsGrid\DataService::class,
			],
			'sign.service.b2e.myDocument.event' => [
				'className' => Service\B2e\MyDocumentsGrid\EventService::class,
			],
			'sign.service.tour' => [
				'className' => Service\Tour::class,
			],
			'sign.service.b2e.myDocumentsGrid.actionStatus' => [
				'className' => Service\B2e\MyDocumentsGrid\ActionStatusService::class,
			],
			'sign.service.preset.templates' => [
				'className' => Service\Sign\PresetTemplatesService::class,
			],
			'sign.repository.access.userModel' => [
				'className' => UserModelRepository::class,
			],
			'sign.access.controller.factory' => [
				'className' => \Bitrix\Sign\Access\AccessController\AccessControllerFactory::class,
			],
		]
	],
	'service.new.ui' => [ 'value' => true,],
	'ui.entity-selector' => [
		'value' => [
			'entities' => [
				[
					'entityId' => 'sign-mycompany',
					'provider' => [
						'moduleId' => 'sign',
						'className' => \Bitrix\Sign\Integration\Ui\EntitySelector\MyCompanyDataProvider::class,
					],
				],
			],
		],
	],
	'userField' => [
		'value' => [
			'access' => '\\Bitrix\\Sign\\UserFields\\UserFieldAccess',
		],
	],
];
