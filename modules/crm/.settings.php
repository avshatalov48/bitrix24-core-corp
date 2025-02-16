<?php

use Bitrix\Crm\Copilot\CallAssessment\EntitySelector\CallScriptProvider;
use Bitrix\Crm\Integration\UI\EntitySelector\ActivityProvider;
use Bitrix\Crm\Integration\UI\EntitySelector\CopilotLanguageProvider;
use Bitrix\Crm\Integration\UI\EntitySelector\CountryProvider;
use Bitrix\Crm\Integration\UI\EntitySelector\DynamicMultipleProvider;
use Bitrix\Crm\Integration\UI\EntitySelector\MessageTemplateProvider;
use Bitrix\Crm\Integration\UI\EntitySelector\MailRecipientProvider;
use Bitrix\Crm\Integration\UI\EntitySelector\PlaceholderProvider;
use Bitrix\Crm\Integration\UI\EntitySelector\TimelinePingProvider;

$uiEntitySelectorConfig = [
	'value' => [
		'entities' => [
			[
				'entityId' => 'activity',
				'provider' => [
					'moduleId' => 'crm',
					'className' => ActivityProvider::class,
				],
			],
			[
				'entityId' => 'company',
				'provider' => [
					'moduleId' => 'crm',
					'className' => '\\Bitrix\\Crm\\Integration\\UI\\EntitySelector\\CompanyProvider',
				],
			],
			[
				'entityId' => 'contact',
				'provider' => [
					'moduleId' => 'crm',
					'className' => '\\Bitrix\\Crm\\Integration\\UI\\EntitySelector\\CompanyProvider',
				],
			],
			[
				'entityId' => 'user',
				'provider' => [
					'moduleId' => 'crm',
					'className' => '\\Bitrix\\Crm\\Integration\\UI\\EntitySelector\\CompanyProvider',
				],
			],
			[
				'entityId' => 'contact',
				'provider' => [
					'moduleId' => 'crm',
					'className' => '\\Bitrix\\Crm\\Integration\\UI\\EntitySelector\\ContactProvider',
				],
			],
			[
				'entityId' => 'deal',
				'provider' => [
					'moduleId' => 'crm',
					'className' => '\\Bitrix\\Crm\\Integration\\UI\\EntitySelector\\DealProvider',
				],
			],
			[
				'entityId' => 'lead',
				'provider' => [
					'moduleId' => 'crm',
					'className' => '\\Bitrix\\Crm\\Integration\\UI\\EntitySelector\\LeadProvider',
				],
			],
			[
				'entityId' => 'quote',
				'provider' => [
					'moduleId' => 'crm',
					'className' => '\\Bitrix\\Crm\\Integration\\UI\\EntitySelector\\QuoteProvider',
				],
			],
			[
				'entityId' => 'order',
				'provider' => [
					'moduleId' => 'crm',
					'className' => '\\Bitrix\\Crm\\Integration\\UI\\EntitySelector\\OrderProvider',
				],
			],
			[
				'entityId' => 'dynamic',
				'provider' => [
					'moduleId' => 'crm',
					'className' => '\\Bitrix\\Crm\\Integration\\UI\\EntitySelector\\DynamicProvider',
				],
			],
			[
				'entityId' => 'dynamic_multiple',
				'provider' => [
					'moduleId' => 'crm',
					'className' => DynamicMultipleProvider::class,
				],
			],
			[
				'entityId' => 'dynamic_type',
				'provider' => [
					'moduleId' => 'crm',
					'className' => '\\Bitrix\\Crm\\Integration\\UI\\EntitySelector\\DynamicTypeProvider',
				],
			],
			[
				'entityId' => 'smart_invoice',
				'provider' => [
					'moduleId' => 'crm',
					'className' => '\\Bitrix\\Crm\\Integration\\UI\\EntitySelector\\SmartInvoice',
				],
			],
			[
				'entityId' => 'smart_document',
				'provider' => [
					'moduleId' => 'crm',
					'className' => '\\Bitrix\\Crm\\Integration\\UI\\EntitySelector\\SmartDocument',
				],
			],
			[
				'entityId' => 'mail_recipient',
				'provider' => [
					'moduleId' => 'crm',
					'className' => MailRecipientProvider::class,
				],
			],
			[
				'entityId' => 'copilot_language',
				'provider' => [
					'moduleId' => 'crm',
					'className' => CopilotLanguageProvider::class,
				],
			],
			[
				'entityId' => 'country',
				'provider' => [
					'moduleId' => 'crm',
					'className' => CountryProvider::class,
				],
			],
			[
				'entityId' => 'timeline_ping',
				'provider' => [
					'moduleId' => 'crm',
					'className' => TimelinePingProvider::class,
				],
			],
			[
				'entityId' => 'placeholder',
				'provider' => [
					'moduleId' => 'crm',
					'className' => PlaceholderProvider::class,
				],
			],
			[
				'entityId' => 'message_template',
				'provider' => [
					'moduleId' => 'crm',
					'className' => MessageTemplateProvider::class,
				],
			],
			[
				'entityId' => 'copilot_call_script',
				'provider' => [
					'moduleId' => 'crm',
					'className' => CallScriptProvider::class,
				],
			],
		],
		'extensions' => ['crm.entity-selector'],
	],
	'readonly' => true,
];


return array(
	'controllers' => array(
		'value' => array(
			'defaultNamespace' => '\\Bitrix\\Crm\\Controller',
			'namespaces' => [
				'\\Bitrix\\Crm\\Controller\\DocumentGenerator' => 'documentgenerator',
				'\\Bitrix\\Crm\\Controller' => 'api',
				'\\Bitrix\\Crm\\Integration' => 'integration',
				'\\Bitrix\\Crm\\Controller\\Site' => 'site',
				'\\Bitrix\\Crm\\Controller\\Requisite' => 'requisite',
				'\\Bitrix\\Crm\\Controller\\Status' => 'status',
				'\\Bitrix\\Crm\\Controller\\Ads' => 'ads',
				'\\Bitrix\\Crm\\Controller\\Mobile' => 'mobile',
				'\\Bitrix\\Crm\\Controller\\Mail' => 'mail',
				'\\Bitrix\\Crm\\Controller\\Timeline' => 'timeline',
			],
			'restIntegration' => [
				'enabled' => true,
			],
		),
		'readonly' => true,
	),
	'ui.selector' => [
		'value' => [
			'crm.selector',
		],
		'readonly' => true,
	],
	'ui.uploader' => [
		'value' => [
			'allowUseControllers' => true,
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
			'crm.service.factory.lead' => [
				'className' => '\\Bitrix\\Crm\\Service\\Factory\\Lead',
			],
			'crm.service.factory.contact' => [
				'className' => '\\Bitrix\\Crm\\Service\\Factory\\Contact',
			],
			'crm.service.factory.company' => [
				'className' => '\\Bitrix\\Crm\\Service\\Factory\\Company',
			],
			'crm.service.factory.smartInvoice' => [
				'className' => '\\Bitrix\\Crm\\Service\\Factory\\SmartInvoice',
			],
			'crm.service.factory.order' => [
				'className' => '\\Bitrix\\Crm\\Service\\Factory\\Order',
			],
			'crm.service.factory.smartDocument' => [
				'className' => '\\Bitrix\\Crm\\Service\\Factory\\SmartDocument',
			],
			'crm.type.factory' => [
				'className' => '\\Bitrix\\Crm\\Model\\Dynamic\\Factory',
			],
			'crm.service.webform.scenario' => [
				'className' => '\\Bitrix\\Crm\\Service\\WebForm\\WebFormScenarioService',
				'constructorParams' => static function () : array {
					return [
						\Bitrix\Main\Engine\CurrentUser::get(),
						\Bitrix\Main\Context::getCurrent()->getCulture(),
					];
				},
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
			'crm.service.converter.type' => [
				'className' => '\\Bitrix\\Crm\\Service\\Converter\\Type',
			],
			'crm.service.converter.productRow' => [
				'className' => '\\Bitrix\\Crm\\Service\\Converter\\ProductRow',
			],
			'crm.service.converter.category' => [
				'className' => '\\Bitrix\\Crm\\Service\\Converter\\Category',
			],
			'crm.service.converter.automatedSolution' => [
				'className' => '\\Bitrix\\Crm\\Service\\Converter\\AutomatedSolution',
			],
			'crm.service.converter.caseCache' => [
				'className' => '\\Bitrix\\Crm\\Service\\Converter\\InMemoryCaseCache',
			],
			'crm.service.broker.user' => [
				'className' => '\\Bitrix\\Crm\\Service\\Broker\\User',
			],
			'crm.service.broker.enumeration' => [
				'className' => '\\Bitrix\\Crm\\Service\\Broker\\Enumeration',
			],
			'crm.service.broker.file' => [
				'className' => '\\Bitrix\\Crm\\Service\\Broker\\File',
			],
			'crm.service.broker.iblockelement' => [
				'className' => '\\Bitrix\\Crm\\Service\\Broker\\IBlockElement',
			],
			'crm.service.broker.iblocksection' => [
				'className' => '\\Bitrix\\Crm\\Service\\Broker\\IBlockSection',
			],
			'crm.service.broker.company' => [
				'className' => '\\Bitrix\\Crm\\Service\\Broker\\Company',
			],
			'crm.service.broker.contact' => [
				'className' => '\\Bitrix\\Crm\\Service\\Broker\\Contact',
			],
			'crm.service.broker.lead' => [
				'className' => '\\Bitrix\\Crm\\Service\\Broker\\Lead',
			],
			'crm.service.broker.deal' => [
				'className' => '\\Bitrix\\Crm\\Service\\Broker\\Deal',
			],
			'crm.service.broker.order' => [
				'className' => '\\Bitrix\\Crm\\Service\\Broker\\Order',
			],
			'crm.service.broker.dynamic' => [
				'className' => '\\Bitrix\\Crm\\Service\\Broker\\Dynamic',
			],
			'crm.service.broker.activity' => [
				'className' => '\\Bitrix\\Crm\\Service\\Broker\\Activity',
			],
			'crm.service.broker.quote' => [
				'className' => '\\Bitrix\\Crm\\Service\\Broker\\Quote',
			],
			'crm.service.sign.b2e.type' => [
				'className' => \Bitrix\Crm\Service\Sign\B2e\TypeService::class,
			],
			'crm.service.sign.b2e.language' => [
				'className' => \Bitrix\Crm\Service\Sign\B2e\LanguageService::class,
			],
			'crm.service.sign.b2e.trigger' => [
				'className' => \Bitrix\Crm\Service\Sign\B2e\TriggerService::class,
			],
			'crm.service.sign.b2e.stage' => [
				'className' => \Bitrix\Crm\Service\Sign\B2e\StageService::class,
			],
			'crm.service.sign.b2e.item' => [
				'className' => \Bitrix\Crm\Service\Sign\B2e\ItemService::class,
			],
			'crm.service.sign.b2e.status' => [
				'className' => \Bitrix\Crm\Service\Sign\B2e\StatusService::class,
			],
			'crm.service.sign.document' => [
				'className' => \Bitrix\Crm\Service\Sign\DocumentService::class,
			],
			'crm.service.sign.member' => [
				'className' => \Bitrix\Crm\Service\Sign\MemberService::class,
			],
			'crm.service.director' => [
				'className' => '\\Bitrix\\Crm\\Service\\Director',
			],
			'crm.service.eventhistory' => [
				'className' => '\\Bitrix\\Crm\\Service\\EventHistory',
			],
			'crm.service.relation.registrar' => [
				'className' => '\\Bitrix\\Crm\\Relation\\Registrar',
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
			'crm.service.accounting' => [
				'className' => '\\Bitrix\\Crm\\Service\\Accounting',
			],
			'crm.service.fileUploader' => [
				'className' => '\\Bitrix\\Crm\\Service\\FileUploader',
			],
			'crm.service.multifieldStorage' => [
				'className' => '\\Bitrix\\Crm\\Service\\MultifieldStorage',
			],
			'crm.kanban.entity.lead' => [
				'className' => '\\Bitrix\\Crm\\Kanban\\Entity\\Lead',
			],
			'crm.kanban.entity.lead.activities' => [
				'className' => '\\Bitrix\\Crm\\Kanban\\Entity\\LeadActivities',
			],
			'crm.kanban.entity.deal' => [
				'className' => '\\Bitrix\\Crm\\Kanban\\Entity\\Deal',
			],
			'crm.kanban.entity.deal.activities' => [
				'className' => '\\Bitrix\\Crm\\Kanban\\Entity\\DealActivities',
			],
			'crm.kanban.entity.invoice' => [
				'className' => '\\Bitrix\\Crm\\Kanban\\Entity\\Invoice',
			],
			'crm.kanban.entity.contact' => [
				'className' => '\\Bitrix\\Crm\\Kanban\\Entity\\Contact',
			],
			'crm.kanban.entity.company' => [
				'className' => '\\Bitrix\\Crm\\Kanban\\Entity\\Company',
			],
			'crm.kanban.entity.quote' => [
				'className' => '\\Bitrix\\Crm\\Kanban\\Entity\\Quote',
			],
			'crm.kanban.entity.quote.deadlines' => [
				'className' => '\\Bitrix\\Crm\\Kanban\\Entity\\QuoteDeadlines',
			],
			'crm.kanban.entity.order' => [
				'className' => '\\Bitrix\\Crm\\Kanban\\Entity\\Order',
			],
			'crm.kanban.entity.activity' => [
				'className' => '\\Bitrix\\Crm\\Kanban\\Entity\\Activity',
			],
			'crm.kanban.entity.dynamic' => [
				'className' => '\\Bitrix\\Crm\\Kanban\\Entity\\Dynamic',
			],
			'crm.kanban.entity.smartInvoice' => [
				'className' => '\\Bitrix\\Crm\\Kanban\\Entity\\SmartInvoice',
			],
			'crm.kanban.entity.smartInvoiceDeadlines' => [
				'className' => '\\Bitrix\\Crm\\Kanban\\Entity\\SmartInvoiceDeadlines',
			],
			'crm.kanban.entity.smartDocument' => [
				'className' => '\\Bitrix\\Crm\\Kanban\\Entity\\SmartDocument',
			],
			'crm.kanban.entity.smartB2eDocument' => [
				'className' => \Bitrix\Crm\Kanban\Entity\SmartB2eDocument::class ,
			],
			'crm.listEntity.entity.lead' => [
				'className' => '\\Bitrix\\Crm\\listEntity\\Entity\\Lead',
			],
			'crm.listEntity.entity.deal' => [
				'className' => '\\Bitrix\\Crm\\ListEntity\\Entity\\Deal',
			],
			'crm.listEntity.entity.invoice' => [
				'className' => '\\Bitrix\\Crm\\listEntity\\Entity\\Invoice',
			],
			'crm.listEntity.entity.quote' => [
				'className' => '\\Bitrix\\Crm\\listEntity\\Entity\\Quote',
			],
			'crm.listEntity.entity.order' => [
				'className' => '\\Bitrix\\Crm\\listEntity\\Entity\\Order',
			],
			'crm.listEntity.entity.activity' => [
				'className' => '\\Bitrix\\Crm\\listEntity\\Entity\\Activity',
			],
			'crm.listEntity.entity.contact' => [
				'className' => '\\Bitrix\\Crm\\listEntity\\Entity\\Contact',
			],
			'crm.listEntity.entity.company' => [
				'className' => '\\Bitrix\\Crm\\listEntity\\Entity\\Company',
			],
			'crm.listEntity.entity.dynamic' => [
				'className' => '\\Bitrix\\Crm\\listEntity\\Entity\\Dynamic',
			],
			'crm.listEntity.entity.smartInvoice' => [
				'className' => '\\Bitrix\\Crm\\listEntity\\Entity\\SmartInvoice',
			],
			'crm.integration.documentgeneratormanager' => [
				'className' => '\\Bitrix\\Crm\\Integration\\DocumentGeneratorManager',
			],
			'crm.integration.documentgeneratormanager.productLoader' => [
				'className' => '\\Bitrix\\Crm\\Integration\\DocumentGenerator\\ProductLoader',
			],
			'crm.integration.pullmanager' => [
				'className' => '\\Bitrix\\Crm\\Integration\\PullManager',
			],
			'crm.integration.intranet.toolsManager' => [
				'className' => '\\Bitrix\\Crm\\Integration\\Intranet\\ToolsManager',
			],
			'crm.integration.rest.eventManager' => [
				'className' => '\\Bitrix\\Crm\\Integration\\Rest\\EventManager',
			],
			'crm.recycling.dynamicRelationManager' => [
				'className' => '\\Bitrix\\Crm\\Recycling\\DynamicRelationManager',
			],
			'crm.recycling.dynamicController' => [
				'className' => '\\Bitrix\\Crm\\Recycling\\DynamicController',
			],
			'crm.service.ads.conversion.facebook' => [
				'className' => '\\Bitrix\\Crm\\Ads\\Pixel\\ConversionWrapper',
				'constructorParams' => static function () {
					$locator = \Bitrix\Main\DI\ServiceLocator::getInstance();
					if (\Bitrix\Main\Loader::includeModule('seo') && $locator->has('seo.business.conversion'))
					{
						return [$locator->get('seo.business.conversion')];
					}

					return [null];
				},
			],
			'crm.service.ads.conversion.configurator' => [
				'className' => '\\Bitrix\\Crm\\Ads\\Pixel\\Configuration\\Configurator',
			],
			'crm.service.integration.sign.kanban.pull' => [
				'className' => \Bitrix\Crm\Service\Integration\Sign\Kanban\PullService::class,
			],
			'crm.service.integration.sign.b2e.type' => [
				'className' => \Bitrix\Crm\Service\Integration\Sign\B2e\TypeService::class,
			],
			'crm.entity.paymentDocumentsRepository' => [
				'className' => '\\Bitrix\\Crm\\Entity\\PaymentDocumentsRepository',
			],
			'crm.filter.factory' => [
				'className' => '\\Bitrix\\Crm\\Filter\\Factory',
			],
			'crm.timeline.timelineEntry.facade' => [
				'className' => '\\Bitrix\\Crm\\Timeline\\TimelineEntry\\Facade',
			],
			'crm.timeline.pusher' => [
				'className' => '\\Bitrix\\Crm\\Timeline\\Pusher',
			],
			'crm.timeline.historyDataModel.maker' => [
				'className' => '\\Bitrix\\Crm\\Timeline\\HistoryDataModel\\Maker',
			],
			'crm.timeline.factory.scheduledItem' => [
				'className' => '\\Bitrix\\Crm\\Service\\Timeline\\Item\\Factory\\ScheduledItem',
			],
			'crm.timeline.factory.historyItem' => [
				'className' => '\\Bitrix\\Crm\\Service\\Timeline\\Item\\Factory\\HistoryItem',
			],
			'crm.timeline.factory.activityItem' => [
				'className' => '\\Bitrix\\Crm\\Service\\Timeline\\Item\\Factory\\ConfigurableActivity',
			],
			'crm.conversion.mapper' => [
				'className' => '\\Bitrix\\Crm\\Conversion\\Mapper',
			],
			'crm.model.fieldRepository' => [
				'className' => '\\Bitrix\\Crm\\Model\\FieldRepository',
			],
			'crm.shipment.product' => [
				'className' => \Bitrix\Crm\Service\Sale\Shipment\ProductService::class,
				// TODO: 'autowire' => true,
				'constructorParams' => static function() {
					return [
						\Bitrix\Main\DI\ServiceLocator::getInstance()->get('crm.basket'),
					];
				},
			],
			'crm.reservation' => [
				'className' => \Bitrix\Crm\Service\Sale\Reservation\ReservationService::class,
			],
			'crm.reservation.shipment' => [
				'className' => \Bitrix\Crm\Service\Sale\Reservation\ShipmentService::class,
				// TODO: 'autowire' => true,
				'constructorParams' => static function() {
					return [
						\Bitrix\Main\DI\ServiceLocator::getInstance()->get('crm.basket'),
						\Bitrix\Main\DI\ServiceLocator::getInstance()->get('crm.shipment.product'),
					];
				},
			],
			'crm.basket' => [
				'className' => \Bitrix\Crm\Service\Sale\BasketService::class,
			],
			'crm.order.buyer' => [
				'className' => \Bitrix\Crm\Service\Sale\Order\BuyerService::class,
			],
			'crm.sale.entity.linkBuilder' => [
				'className' => Bitrix\Crm\Service\Sale\EntityLinkBuilder\EntityLinkBuilder::class,
			],
			'crm.integration.sign' => [
				'className' => \Bitrix\Crm\Service\Integration\Sign::class,
			],
			'crm.activity.actcounterlighttimerepo' => [
				'className' => \Bitrix\Crm\Activity\LightCounter\ActCounterLightTimeRepo::class,
			],
			'crm.filter.fieldsTransform.userBasedField' => [
				'className' => \Bitrix\Crm\Filter\FieldsTransform\UserBasedField::class,
			],
			'crm.fieldContext.contextManager' => [
				'className' => \Bitrix\Crm\FieldContext\ContextManager::class,
			],
			'crm.terminal.payment' => [
				'className' => \Bitrix\Crm\Service\Sale\Terminal\PaymentService::class,
			],
			'crm.customSection.automatedSolutionManager' => [
				'className' => \Bitrix\Crm\AutomatedSolution\AutomatedSolutionManager::class,
			],
			'crm.summary.summaryFactory' => [
				'className' => \Bitrix\Crm\Summary\SummaryFactory::class,
			],
			'crm.binding.clientBinder' => [
				'className' => \Bitrix\Crm\Binding\ClientBinder::class,
			],
			'crm.service.communication.rankingFactory' => [
				'className' => \Bitrix\Crm\Service\Communication\Search\Ranking\RankingFactory::class,
			],
			'crm.service.integration.im' => [
				'className' => \Bitrix\Crm\Integration\Im\ImService::class,
			],
		],
		'readonly' => true,
	],
	'ui.entity-selector' => $uiEntitySelectorConfig,
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
	'documentgenerator.intranet.binding' => [
		'value' => [
			'menuCodeResolver' => static function (string $provider): string {
				$entityTypeId =
					\Bitrix\Crm\Integration\DocumentGeneratorManager::getInstance()->getEntityTypeIdByProvider($provider);

				return \Bitrix\Crm\Integration\Intranet\BindingMenu\CodeBuilder::getMenuCode($entityTypeId);
			},
		],
	],
	'loggers' => [
		'value' => [
			'Default' => static fn() => (new \Bitrix\Crm\Service\Logger\DbLogger('Default', 168))->setLevel(\Psr\Log\LogLevel::ERROR),
			'Integration.AI' => static function () {
				if (\Bitrix\Main\Loader::includeModule('bitrix24') || \Bitrix\Main\Config\Option::get('crm', 'USE_ADDM2LOG_FOR_AI', false))
				{
					$logger = new \Bitrix\Crm\Service\Logger\Message2LogLogger('crm.integration.AI', 9);
					$logger->setLevel(\Bitrix\Main\Config\Option::get('crm', 'log_integration_ai_level', \Psr\Log\LogLevel::CRITICAL));

					return $logger;
				}
				return new \Psr\Log\NullLogger();
			},
			'Permissions' => static fn() => (new \Bitrix\Crm\Service\Logger\DbLogger(
				'Permissions',
				(int)\Bitrix\Main\Config\Option::get('crm', 'permissions_logger_ttl', 24*30))
			)->setLevel(\Bitrix\Main\Config\Option::get('crm', 'permissions_logger_level', \Psr\Log\LogLevel::INFO)),
		],
	],
);
