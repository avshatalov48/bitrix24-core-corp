<?php

namespace Bitrix\Crm\Integration;

use Bitrix\Crm\Integration\DocumentGenerator\DataProvider;
use Bitrix\Crm\Integration\DocumentGenerator\ProductLoader;
use Bitrix\Crm\Integration\Rest\EventManager;
use Bitrix\Crm\Service\Container;
use Bitrix\DocumentGenerator\Document;
use Bitrix\DocumentGenerator\Driver;
use Bitrix\DocumentGenerator\Model\DocumentTable;
use Bitrix\DocumentGenerator\Nameable;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Event;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\Loader;
use Bitrix\DocumentGenerator\Model\TemplateTable;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Localization\Loc;

class DocumentGeneratorManager
{
	public const PROVIDER_LIST_EVENT_NAME = 'onGetDataProviderList';
	public const DOCUMENT_CREATE_EVENT_NAME = 'onCreateDocument';
	public const DOCUMENT_UPDATE_EVENT_NAME = 'onUpdateDocument';
	public const DOCUMENT_PUBLIC_VIEW_EVENT_NAME = 'onPublicView';
	public const DOCUMENT_DELETE_EVENT_NAME = '\Bitrix\DocumentGenerator\Model\Document::OnBeforeDelete';

	protected $isEnabled;

	public function __construct()
	{
		$this->isEnabled = Loader::includeModule('documentgenerator');
	}

	/**
	 * @return DocumentGeneratorManager
	 */
	public static function getInstance(): DocumentGeneratorManager
	{
		return ServiceLocator::getInstance()->get('crm.integration.documentgeneratormanager');
	}

	public function getProductLoader(): ProductLoader
	{
		return ServiceLocator::getInstance()->get('crm.integration.documentgeneratormanager.productLoader');
	}

	/**
	 * Returns true if module documentgenerator is enabled.
	 *
	 * @return bool
	 */
	public function isEnabled(): bool
	{
		return ($this->isEnabled && Driver::getInstance()->isEnabled());
	}

	/**
	 * Returns true if current user can access to some actions with documents.
	 *
	 * @return bool
	 */
	public function isDocumentButtonAvailable(): bool
	{
		return (
			$this->isEnabled() && (
				Driver::getInstance()->getUserPermissions()->canViewDocuments() ||
				Driver::getInstance()->getUserPermissions()->canModifyTemplates()
			)
		);
	}

	/**
	 * Returns parameters for "Documents" button.
	 *
	 * @param string $className - FQN of a provider
	 * @param mixed $value
	 * @return array
	 */
	public function getDocumentButtonParameters($className, $value): array
	{
		if(!$this->isDocumentButtonAvailable())
		{
			return [];
		}
		// subscribe to changes in the list
		TemplateTable::getPullTag();
		\CJSCore::init(["documentpreview"]);
		Loc::loadMessages(__FILE__);
		$params = [
			'provider' => $className,
			'moduleId' => 'crm',
			'value' => $value,
			'templateListUrl' => $this->getAddTemplateUrl($className),
			'className' => 'crm-btn-dropdown-document',
			'menuClassName' => 'document-toolbar-menu',
			'templatesText' => Loc::getMessage('CRM_DOCUMENTGENERATOR_ADD_NEW_TEMPLATE'),
			'documentsText' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DOCUMENTS_LIST'),
		];
		$componentPath = \CComponentEngine::makeComponentPath('bitrix:crm.document.view');
		if(!empty($componentPath))
		{
			$params['loaderPath'] = getLocalPath('components'.$componentPath.'/templates/.default/images/document_view.svg');
			$documentUrl = new Uri(getLocalPath('components'.$componentPath.'/slider.php'));
			$documentUrl->addParams(['providerClassName' => $className,]);
			$params['documentUrl'] = $documentUrl->getUri();
		}

		return $params;
	}

	/**
	 * Returns url to add template.
	 *
	 * @param string $provider
	 * @return bool|string
	 */
	public function getAddTemplateUrl($provider = null)
	{
		if($this->isEnabled() && Directory::isDirectoryExists(\Bitrix\Main\Application::getInstance()->getContext()->getServer()->getDocumentRoot().'/crm/documents/'))
		{
			$path = '/crm/documents/templates/';
			$uri = new Uri($path);
			if($provider)
			{
				$uri->addParams(['entityTypeId' => $provider]);
			}
			return $uri->getLocator();
		}

		return false;
	}

	/**
	 * Returns list of providers and their descriptions (@see \Bitrix\DocumentGenerator\Registry\DataProvider::getList())
	 *
	 * @return array
	 */
	public static function getDataProviders(): array
	{
		static $result;
		if($result === null)
		{
			$result = [];
			if(static::getInstance()->isEnabled())
			{
				$providers = [
					DataProvider\Company::class,
					DataProvider\Contact::class,
					DataProvider\Deal::class,
					DataProvider\Invoice::class,
					DataProvider\Lead::class,
					DataProvider\Quote::class,
					DataProvider\Order::class,
					DataProvider\Payment::class,
					DataProvider\Shipment::class,
				];
				$providers = array_merge($providers, array_values(static::getDynamicProviders(true)));
				foreach($providers as $provider)
				{
					/** @var Nameable $provider */
					$className = mb_strtolower($provider);
					$result[$className] = [
						'NAME' => $provider::getLangName(),
						'CLASS' => $className,
						'MODULE' => 'crm',
					];
				}
			}
		}

		return $result;
	}

	/**
	 * @param Event $event
	 * @return bool
	 */
	public static function onCreateDocument(Event $event): bool
	{
		$document = $event->getParameter('document');
		/** @var Document $document */
		if($document)
		{
			$provider = $document->getProvider();
			if($provider && $provider instanceof DataProvider\CrmEntityDataProvider)
			{
				$provider->onDocumentCreate($document);

				$event = new Event('crm', EventManager::EVENT_DOCUMENTGENERATOR_DOCUMENT_ADD, [
					'document' => $document,
					'entityTypeId' => $provider->getCrmOwnerType(),
					'entityId' => (int)$provider->getSource(),
				]);
				$event->send();
			}
		}

		return true;
	}

	/**
	 * @param Event $event
	 * @return bool
	 */
	public static function onDeleteDocument(Event $event): bool
	{
		$primary = $event->getParameter('primary');
		if(is_array($primary))
		{
			$primary = $primary['ID'] ?? 0;
		}
		$document = Document::loadById($primary);
		if($document)
		{
			$provider = $document->getProvider();
			if($provider && $provider instanceof DataProvider\CrmEntityDataProvider)
			{
				$provider->onDocumentDelete($document);

				$event = new Event('crm', EventManager::EVENT_DOCUMENTGENERATOR_DOCUMENT_DELETE, [
					'document' => $document,
					'entityTypeId' => $provider->getCrmOwnerType(),
					'entityId' => (int)$provider->getSource(),
				]);
				$event->send();
			}
		}

		return true;
	}

	/**
	 * @param Event $event
	 * @return bool
	 */
	public static function onUpdateDocument(Event $event): bool
	{
		$document = $event->getParameter('document');
		/** @var Document $document */
		if($document)
		{
			$provider = $document->getProvider();
			if($provider && $provider instanceof DataProvider\CrmEntityDataProvider)
			{
				$provider->onDocumentUpdate($document);

				$event = new Event('crm', EventManager::EVENT_DOCUMENTGENERATOR_DOCUMENT_UPDATE, [
					'document' => $document,
					'entityTypeId' => $provider->getCrmOwnerType(),
					'entityId' => (int)$provider->getSource(),
				]);
				$event->send();
			}
		}

		return true;
	}

	/**
	 * @param Event $event
	 * @return bool
	 */
	public static function onPublicView(Event $event): bool
	{
		$document = $event->getParameter('document');
		$isFirstTime = ($event->getParameter('isFirstTime') === true);
		/** @var Document $document */
		if($document)
		{
			$provider = $document->getProvider();
			if($provider && $provider instanceof DataProvider\CrmEntityDataProvider)
			{
				$provider->onPublicView($document, $isFirstTime);
			}
		}

		return true;
	}

	/**
	 * Returns array where key - id from \CCrmOwnerType and value - data provider class name
	 *
	 * @param bool $isSourceEntitiesOnly. If true - returns only those providers which can be source for a new document.
	 * @return array
	 */
	public function getCrmOwnerTypeProvidersMap(bool $isSourceEntitiesOnly = true): array
	{
		$map = [
			\CCrmOwnerType::Lead => DataProvider\Lead::class,
			\CCrmOwnerType::Deal => DataProvider\Deal::class,
			\CCrmOwnerType::Contact => DataProvider\Contact::class,
			\CCrmOwnerType::Company => DataProvider\Company::class,
			\CCrmOwnerType::Invoice => DataProvider\Invoice::class,
			\CCrmOwnerType::Quote => DataProvider\Quote::class,
			\CCrmOwnerType::Order => DataProvider\Order::class,
			\CCrmOwnerType::OrderPayment => DataProvider\Payment::class,
			\CCrmOwnerType::OrderShipment => DataProvider\Shipment::class,

			\CCrmOwnerType::SuspendedLead => DataProvider\Suspended::class,
			\CCrmOwnerType::SuspendedDeal => DataProvider\Suspended::class,
			\CCrmOwnerType::SuspendedContact => DataProvider\Suspended::class,
			\CCrmOwnerType::SuspendedCompany => DataProvider\Suspended::class,
			\CCrmOwnerType::SuspendedQuote => DataProvider\Suspended::class,
			\CCrmOwnerType::SuspendedInvoice => DataProvider\Suspended::class,
			\CCrmOwnerType::SuspendedOrder => DataProvider\Suspended::class,
		];

		foreach (static::getDynamicProviders($isSourceEntitiesOnly) as $entityTypeId => $provider)
		{
			$map[$entityTypeId] = $provider;
			$map[\CCrmOwnerType::getSuspendedDynamicTypeId($entityTypeId)] = DataProvider\Suspended::class;
		}

		return $map;
	}

	protected static function getDynamicProviders(bool $isSourceEntitiesOnly): array
	{
		$providers = [];

		$typesMap = Container::getInstance()->getDynamicTypesMap()->load([
			'isLoadCategories' => false,
			'isLoadStages' => false,
		]);
		foreach ($typesMap->getTypes() as $type)
		{
			if (
				!$isSourceEntitiesOnly
				|| (
					$isSourceEntitiesOnly
					&& $type->getIsDocumentsEnabled()
				)
			)
			{
				$entityTypeId = $type->getEntityTypeId();
				$providers[$entityTypeId] = DataProvider\Dynamic::getProviderCode($entityTypeId);
			}
		}

		return $providers;
	}

	/**
	 * @param $oldEntityTypeID
	 * @param $oldEntityID
	 * @param $newEntityTypeID
	 * @param $newEntityID
	 */
	public function transferDocumentsOwnership($oldEntityTypeID, $oldEntityID, $newEntityTypeID, $newEntityID): void
	{
		if(!$this->isEnabled)
		{
			return;
		}

		$providersMap = $this->getCrmOwnerTypeProvidersMap();
		$oldProvider = $providersMap[$oldEntityTypeID];
		$newProvider = $providersMap[$newEntityTypeID];
		if($oldProvider && $newProvider)
		{
			DocumentTable::transferOwnership($oldProvider, $oldEntityID, $newProvider, $newEntityID);
		}
	}

	/**
	 * @param $entityTypeId
	 * @param $entityId
	 * @return \Bitrix\Main\Result|null
	 */
	public function deleteDocumentsByOwner($entityTypeId, $entityId): ?Result
	{
		if(!$this->isEnabled)
		{
			return null;
		}

		$entityId = (int) $entityId;
		$provider = $this->getCrmOwnerTypeProvidersMap()[$entityTypeId];
		if($provider && $entityId > 0)
		{
			return DocumentTable::deleteList([
				'=PROVIDER' => mb_strtolower($provider),
				'VALUE' => $entityId
			]);
		}

		return null;
	}

	public function getEntityTypeIdByProvider(string $provider): int
	{
		$providersMap = $this->getCrmOwnerTypeProvidersMap();

		$entityTypeId = array_search($provider, $providersMap, true);

		if (!is_int($entityTypeId))
		{
			$entityTypeId = \CCrmOwnerType::Undefined;
		}

		return $entityTypeId;
	}
}
