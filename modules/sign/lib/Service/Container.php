<?php

namespace Bitrix\Sign\Service;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Sign\Access\AccessController\AccessControllerFactory;
use Bitrix\Sign\Access\Model\UserModelRepository;
use Bitrix\Sign\Access\Service\AccessService;
use Bitrix\Sign\Access\Service\RolePermissionService;
use Bitrix\Sign\Factory\Access\AccessibleItemFactory;
use Bitrix\Sign\Repository;
use Bitrix\Sign\Connector;
use Bitrix\Sign\Service;
use Bitrix\Sign\Callback;
use Bitrix\Sign\Contract;
use Psr\Container\ContainerInterface;

class Container
{
	private static ?ContainerInterface $serviceLocator = null;

	public static function instance(): Container
	{
		return self::getService('sign.container');
	}

	protected static function getServiceLocator(): ContainerInterface
	{
		if (self::$serviceLocator === null)
		{
			self::$serviceLocator = ServiceLocator::getInstance();
		}

		return self::$serviceLocator;
	}

	private static function getService(string $name): mixed
	{
		$prefix = 'sign.';
		if (mb_strpos($name, $prefix) !== 0)
		{
			$name = $prefix . $name;
		}

		$locator = self::getServiceLocator();

		return $locator->has($name)
			? $locator->get($name)
			: null
		;
	}

	public function getApiService(): ApiService
	{
		return self::getService('sign.service.api');
	}

	public function getUserService(): Service\UserService
	{
		return self::getService('sign.service.user');
	}

	public function getCounterService(): CounterService
	{
		return self::getService('sign.service.counter');
	}

	public function getB2eKanbanCategoryService(): Service\Sign\B2e\KanbanCategoryService
	{
		return self::getService('sign.service.b2e.kanbanCategory');
	}

	public function getApiDocumentService(): Api\DocumentService
	{
		return self::getService('sign.service.api.document');
	}

	public function getApiClientDomainService(): Api\Client\Domain
	{
		return self::getService('sign.service.api.client.domain');
	}

	public function getDocumentRepository(): Repository\DocumentRepository
	{
		return static::getService('sign.repository.document');
	}

	public function getDocumentTemplateService(): Service\Sign\Document\TemplateService
	{
		return static::getService('sign.service.document.template');
	}

	public function getEntityFileRepository(): Repository\EntityFileRepository
	{
		return static::getService('sign.repository.entity.file');
	}

	public function getBlankResourceRepository(): Repository\Blank\ResourceRepository
	{
		return static::getService('sign.repository.blank.resource');
	}

	public function getBlankRepository(): Repository\BlankRepository
	{
		return static::getService('sign.repository.blank');
	}

	public function getBlockRepository(): Repository\BlockRepository
	{
		return static::getService('sign.repository.block');
	}

	public function getApiDocumentPageService(): Api\Document\PageService
	{
		return self::getService('sign.service.api.document.page');
	}

	public function getApiDocumentSigningService(): Api\Document\SigningService
	{
		return self::getService('sign.service.api.document.signing');
	}

	public function getApiDocumentFieldService(): Api\Document\FieldService
	{
		return self::getService('sign.service.api.document.field');
	}

	public function getApiMobileService(): Api\MobileService
	{
		return self::getService('sign.service.api.mobile');
	}

	public function getExternalSignProviderService(): Api\B2e\ExternalSignProviderService
	{
		return self::getService('sign.service.api.external-sign-provider');
	}

	public function getSignBlankFileService(): Service\Sign\BlankFileService
	{
		return self::getService('sign.service.sign.blank.file');
	}

	public function getSignBlankService(): Service\Sign\BlankService
	{
		return self::getService('sign.service.sign.blank');
	}

	public function getSignBlockService(): Service\Sign\BlockService
	{
		return self::getService('sign.service.sign.block');
	}

	public function getDocumentService(): Service\Sign\DocumentService
	{
		return self::getService('sign.service.sign.document');
	}

	public function getPullService(): Service\PullService
	{
		return self::getService('sign.service.pull');
	}

	public function getCrmSignDocumentService(): Service\Integration\Crm\DocumentService
	{
		return self::getService('sign.service.integration.crm.document');
	}

	public function getSignMobileMemberService(): Service\Integration\SignMobile\MemberService
	{
		return self::getService('sign.service.integration.signmobile.member');
	}

	public function getImService(): Service\Integration\Im\ImService
	{
		return self::getService('sign.service.integration.im');
	}

	public function getHrBotMessageService(): Service\HrBotMessageService
	{
		return self::getService('sign.service.hrbotmessage');
	}

	public function getEventHandlerService(): Service\Integration\Crm\EventHandlerService
	{
		return self::getService('sign.service.integration.crm.events');
	}

	public function getMemberRepository(): Repository\MemberRepository
	{
		return static::getService('sign.repository.member');
	}

	public function getMemberNodeRepository(): Repository\MemberNodeRepository
	{
		return static::getService('sign.repository.membernode');
	}

	public function getFileRepository(): Repository\FileRepository
	{
		return static::getService('sign.repository.file');
	}

	public function getMemberService(): Service\Sign\MemberService
	{
		return static::getService('sign.service.sign.member');
	}

	public function getMobileService(): Service\MobileService
	{
		return static::getService('sign.service.mobile');
	}

	public function getMemberConnectorFactory(): Connector\MemberConnectorFactory
	{
		return static::getService('sign.connector.member.factory');
	}

	public function getDocumentConnectorFactory(): Connector\DocumentConnectorFactory
	{
		return static::getService('sign.connector.document.factory');
	}

	public function getFieldConnectorFactory(): Connector\FieldConnectorFactory
	{
		return static::getService('sign.connector.field.factory');
	}

	public function getSignedFileLoadService(): Service\Api\Document\SignedFileLoadService
	{
		return static::getService('sign.service.api.document.signed.file.load');
	}

	public function getDocumentFileNameService(): Service\Sign\DocumentFileNameService
	{
		return static::getService('sign.service.sign.document.filename');
	}

	public function getDocumentAgentService(): Service\Sign\DocumentAgentService
	{
		return static::getService('sign.service.sign.document.agent');
	}

	public function getServiceUserRepository(): Repository\ServiceUserRepository
	{
		return static::getService('sign.repository.service_user');
	}

	public function getImNotificationService(): Service\Integration\Im\NotificationService
	{
		return static::getService('sign.service.integration.im.notification');
	}

	public function getServiceUserService(): Service\Api\B2e\UserService
	{
		return static::getService('sign.service.api.b2e.user');
	}

	public function getServiceMemberWebStatus(): Service\Api\Member\WebStatusService
	{
		return static::getService('sign.service.api.member.webStatus');
	}

	public function getServiceProfileProvider(): Service\Providers\ProfileProvider
	{
		return static::getService('sign.service.provider.profile');
	}

	public function getSignMemberUserService(): Service\Sign\Member\UserService
	{
		return static::getService('sign.service.sign.member.user');
	}

	public function getB2eDocumentService(): Service\Integration\Crm\B2eDocumentService
	{
		return static::getService('sign.service.integration.crm.b2e.document');
	}

	public function getCrmKanbanB2eEntityService(): Service\Integration\Crm\Kanban\B2e\EntityService
	{
		return static::getService('sign.service.integration.crm.kanban.b2e.entity');
	}

	public function getHcmLinkFieldService(): Service\Integration\HumanResources\HcmLinkFieldService
	{
		return static::getService('sign.service.integration.humanresources.hcmlink.field');
	}

	public function getHcmLinkSignedFileService(): Service\Integration\HumanResources\HcmLinkSignedFileService
	{
		return static::getService('sign.service.integration.humanresources.hcmlink.signedFile');
	}

	public function getUrlGeneratorService(): Service\Sign\UrlGeneratorService
	{
		return static::getService('sign.service.sign.url.generator');
	}

	public function getApiB2eProviderFieldsService(): Service\Api\B2e\ProviderFieldsService
	{
		return static::getService('sign.service.api.b2e.providerFields');
	}

	public function getApiB2eProviderSchemesService(): Service\Api\B2e\ProviderSchemesService
	{
		return static::getService('sign.service.api.b2e.providerSchemes');
	}

	public function getCallbackHandler(): Callback\Handler
	{
		return static::getService('sign.callback.handler');
	}

	public function getRegionDocumentTypeRepository(): Repository\RegionDocumentTypeRepository
	{
		return static::getService('sign.repository.region_document_type');
	}

	public function getUserRepository(): Repository\UserRepository
	{
		return static::getService('sign.repository.user');
	}

	public function getLegalLogRepository(): Repository\LegalLogRepository
	{
		return static::getService('sign.repository.legal_log');
	}

	public function getLegalLogService(): Service\Sign\LegalLogService
	{
		return static::getService('sign.service.sign.legal_log');
	}

	/**
	 * @return RolePermissionService|null return null if crm module is not included
	 */
	public function getRolePermissionService(): ?RolePermissionService
	{
		return static::getService('sign.service.access.rolePermission');
	}

	public function getAccessService(): AccessService
	{
		return static::getService('sign.access.service.access');
	}

	public function getApiProviderCodeService(): Service\Api\B2e\ProviderCodeService
	{
		return static::getService('sign.service.api.b2e.providerCode');
	}

	public function getProviderCodeService(): Service\Sign\Document\ProviderCodeService
	{
		return static::getService('sign.service.sign.document.providerCode');
	}

	public function getRequiredFieldRepository(): Repository\RequiredFieldRepository
	{
		return static::getService('sign.repository.required_field');
	}

	public function getLicenseService(): Service\LicenseService
	{
		return static::getService('sign.service.license');
	}

	public function getB2eTariffRestrictionService(): Service\B2e\B2eTariffRestrictionService
	{
		return static::getService('sign.service.b2e.tariffRestriction');
	}

	public function getCacheManager(): Contract\Util\Cache
	{
		return static::getService('sign.util.cache');
	}

	public function getGroupChatService(): Service\Integration\Im\GroupChatService
	{
		return static::getService('sign.service.integration.im.groupChat');
	}

	public function getFrontendBlockService(): Service\Sign\Block\FrontendBlockService
	{
		return static::getService('sign.service.block.frontendBlock');
	}

	public function getDocumentChatRepository(): Repository\DocumentChatRepository
	{
		return static::getService('sign.repository.documentChat');
	}

	public function getChatTypeConverterService(): Service\Sign\DocumentChat\ChatTypeConverterService
	{
		return static::getService('sign.service.sign.documentChat.chatTypeConverter');
	}

	public function getDocumentTemplateRepository(): Repository\Document\TemplateRepository
	{
		return static::getService('sign.repository.document.template');
	}

	public function getCrmMyCompanyService(): Service\Integration\Crm\MyCompanyService
	{
		return static::getService('sign.service.integration.crm.myCompany');
	}

	public function getDocumentGroupRepository(): Repository\Document\GroupRepository
	{
		return static::getService('sign.repository.document.group');
	}

	public function getDocumentGroupService(): Service\Sign\Document\GroupService
	{
		return static::getService('sign.service.document.group');
	}

	public function getMemberDynamicFieldProvider(): Service\Providers\MemberDynamicFieldInfoProvider
	{
		return static::getService('sign.service.provider.memberDynamic');
	}

	public function getMyDocumentService():Service\B2e\MyDocumentsGrid\DataService
	{
		return static::getService('sign.service.b2e.myDocumentsGrid.data');
	}

	public function getFieldValueRepository(): Repository\FieldValueRepository
	{
		return static::getService('sign.repository.fieldValue');
	}

	public function getAccessibleItemFactory(): AccessibleItemFactory
	{
		return static::getService('sign.accessibleItem.factory');
	}

	public function getHcmLinkService(): Service\Integration\HumanResources\HcmLinkService
	{
		return static::getService('sign.service.integration.humanresources.hcmlink');
	}

	public function getLegalInfoProvider(): Service\Providers\LegalInfoProvider
	{
		return static::getService('sign.service.provider.legal');
	}

	public function getPermissionsService(): Service\Sign\PermissionsService
	{
		return static::getService('sign.service.sign.permissions');
	}

	public function getMyDocumentGridEventService(): Service\B2e\MyDocumentsGrid\EventService
	{
		return static::getService('sign.service.b2e.myDocument.event');
	}

	public function getTourService(): Service\Tour
	{
		return static::getService('sign.service.tour');
	}

	public function getActionStatusService(): Service\B2e\MyDocumentsGrid\ActionStatusService
	{
		return static::getService('sign.service.b2e.myDocumentsGrid.actionStatus');
	}

	public function getAnalyticService(): Service\Analytic\AnalyticService
	{
		return static::getService('sign.service.analytic.analytic');
	}

	public function getPresetTemplatesService(): Service\Sign\PresetTemplatesService
	{
		return static::getService('sign.service.preset.templates');
	}

	public function getAccessUserModelRepository(): UserModelRepository
	{
		return static::getService('sign.repository.access.userModel');
	}

	public function getAccessControllerFactory(): AccessControllerFactory
	{
		return static::getService('sign.access.controller.factory');
	}
}
