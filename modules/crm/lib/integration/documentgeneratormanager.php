<?php

namespace Bitrix\Crm\Integration;

use Bitrix\Crm\Integration\DocumentGenerator\DataProvider;
use Bitrix\Crm\Integration\DocumentGenerator\ProductLoader;
use Bitrix\Crm\Integration\DocumentGenerator\Template;
use Bitrix\Crm\Integration\Rest\EventManager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\InvoiceSettings;
use Bitrix\Crm\Timeline\DocumentController;
use Bitrix\DocumentGenerator\CreationMethod;
use Bitrix\DocumentGenerator\Document;
use Bitrix\DocumentGenerator\Driver;
use Bitrix\DocumentGenerator\Model\DocumentBindingTable;
use Bitrix\DocumentGenerator\Model\DocumentTable;
use Bitrix\DocumentGenerator\Model\TemplateProviderTable;
use Bitrix\DocumentGenerator\Model\TemplateTable;
use Bitrix\DocumentGenerator\Nameable;
use Bitrix\DocumentGenerator\Service\ActualizeQueue;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Uri;

class DocumentGeneratorManager
{
	public const PROVIDER_LIST_EVENT_NAME = 'onGetDataProviderList';
	public const DOCUMENT_CREATE_EVENT_NAME = 'onCreateDocument';
	public const DOCUMENT_UPDATE_EVENT_NAME = 'onUpdateDocument';
	public const DOCUMENT_PUBLIC_VIEW_EVENT_NAME = 'onPublicView';
	public const DOCUMENT_DELETE_EVENT_NAME = '\Bitrix\DocumentGenerator\Model\Document::OnBeforeDelete';

	public const ACTUALIZATION_POSITION_QUEUE = 'actualizationQueue';
	public const ACTUALIZATION_POSITION_BACKGROUND = 'actualizationBackground';
	public const ACTUALIZATION_POSITION_IMMEDIATELY = 'actualizationImmediately';

	public const VALUE_PAYMENT_ID = '_paymentId';

	protected $isEnabled;

	protected array $scheduledActivitiesCache = [];

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
			'sliderWidth' => 1060,
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

	public function getDocumentDetailUrl(
		int $entityTypeId,
		?int $entityId = null,
		?int $documentId = null,
		?int $templateId = null
	): ?Uri
	{
		$provider = $this->getCrmOwnerTypeProvidersMap()[$entityTypeId] ?? null;
		if (!$provider)
		{
			return null;
		}
		$componentPath = \CComponentEngine::makeComponentPath('bitrix:crm.document.view');
		if (empty($componentPath))
		{
			return null;
		}

		$documentUrl = new Uri(getLocalPath('components'.$componentPath.'/slider.php'));
		$params = [
			'providerClassName' => $provider,
		];
		if ($entityId > 0)
		{
			$params['value'] = $entityId;
		}
		if ($documentId > 0)
		{
			$params['documentId'] = $documentId;
		}
		if ($templateId > 0)
		{
			$params['templateId'] = $templateId;
		}

		return $documentUrl->addParams($params);
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
					DataProvider\Lead::class,
					DataProvider\Quote::class,
					DataProvider\Order::class,
					DataProvider\Payment::class,
					DataProvider\Shipment::class,
					DataProvider\StoreDocumentArrival::class,
					DataProvider\StoreDocumentStoreAdjustment::class,
					DataProvider\StoreDocumentMoving::class,
					DataProvider\ShipmentDocumentRealization::class,
				];
				if (InvoiceSettings::getCurrent()->isOldInvoicesEnabled())
				{
					$providers[] = DataProvider\Invoice::class;
				}
				if (InvoiceSettings::getCurrent()->isSmartInvoiceEnabled())
				{
					$providers[] = DataProvider\SmartInvoice::class;
				}
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

	final public static function onDocumentTransformationComplete(Event $event): bool
	{
		$documentId = (int)$event->getParameter('documentId');
		if ($documentId > 0 && self::getInstance()->isEnabled())
		{
			$document = Document::loadById($documentId);
			if ($document)
			{
				$provider = $document->getProvider();
				if ($provider instanceof DataProvider\CrmEntityDataProvider)
				{
					$owner = $provider->getTimelineItemIdentifier();

					if ($owner)
					{
						DocumentController::getInstance()->onDocumentTransformationComplete(
							$documentId,
							[
								'ENTITY_TYPE_ID' => $owner->getEntityTypeId(),
								'ENTITY_ID' => $owner->getEntityId()
							],
						);
					}
				}
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

			static::getInstance()->deleteDocumentActivity($primary);
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

			\Bitrix\Crm\Activity\Provider\Document::onDocumentUpdate(
				$document->ID,
			);
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

		if (InvoiceSettings::getCurrent()->isSmartInvoiceEnabled())
		{
			$map[\CCrmOwnerType::SmartInvoice] = DataProvider\SmartInvoice::class;
			$map[\CCrmOwnerType::SuspendedSmartInvoice] = DataProvider\Suspended::class;
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
		$provider = $this->getCrmOwnerTypeProvidersMap()[$entityTypeId] ?? null;
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

	public function isEntitySupportsPaymentDocumentBinding(int $entityTypeId): bool
	{
		return $entityTypeId === \CCrmOwnerType::SmartInvoice;
	}

	public function getPaymentBoundDocumentId(int $paymentId): ?int
	{
		$binding = DocumentBindingTable::getList([
			'select' => ['DOCUMENT_ID'],
			'filter' => [
				'=ENTITY_NAME' => \CCrmOwnerType::OrderPaymentName,
				'=ENTITY_ID' => $paymentId,
			],
			'limit' => 1,
		])->fetchObject();

		return $binding ? $binding->get('DOCUMENT_ID') : null;
	}

	/**
	 * @param ItemIdentifier $item
	 * @return DocumentGenerator\Document[]
	 */
	public function getDocumentsByIdentifier(ItemIdentifier $item, int $paymentId = null): array
	{
		$result = [];

		$provider = $this->getCrmOwnerTypeProvidersMap()[$item->getEntityTypeId()] ?? null;
		if (!$provider)
		{
			return $result;
		}

		$documents = DocumentTable::getList([
			'select' => ['ID', 'TITLE', 'VALUES'],
			'order' => [
				'UPDATE_TIME' => 'DESC',
			],
			'filter' => [
				'=PROVIDER' => mb_strtolower($provider),
				'VALUE' => $item->getEntityId(),
			],
		]);
		while ($document = $documents->fetch())
		{
			$values = $document['VALUES'];
			if ($paymentId > 0)
			{
				if (!isset($values[static::VALUE_PAYMENT_ID]) || (int)$values[static::VALUE_PAYMENT_ID] !== $paymentId)
				{
					continue;
				}
			}
			$withStamps = $values[\Bitrix\DocumentGenerator\Document::STAMPS_ENABLED_PLACEHOLDER] ?? false;
			$result[] =
				(new DocumentGenerator\Document)
					->setId($document['ID'])
					->setTitle($document['TITLE'])
					->setDetailUrl($this->getDocumentDetailUrl(
						$item->getEntityTypeId(),
						$item->getEntityId(),
						$document['ID']
					))
					->setIsWithStamps($withStamps)
			;
		}

		return $result;
	}

	/**
	 * @param ItemIdentifier $item
	 * @return Template[]
	 */
	public function getTemplatesByIdentifier(ItemIdentifier $item, int $userId = null): array
	{
		$result = [];

		$provider = $this->getCrmOwnerTypeProvidersMap()[$item->getEntityTypeId()] ?? null;
		if (!$provider)
		{
			return $result;
		}

		$templates = TemplateTable::getListByClassName(
			mb_strtolower($provider),
			$userId,
			$item->getEntityId(),
		);
		foreach ($templates as $template)
		{
			$result[] =
				(new Template())
					->setId($template['ID'])
					->setTitle($template['NAME'])
					->setDocumentCreationUrl($this->getDocumentDetailUrl(
						$item->getEntityTypeId(),
						$item->getEntityId(),
						null,
						$template['ID'],
					))
					->setIsWithStamps($template['WITH_STAMPS'] === 'Y')
			;
		}

		return $result;
	}

	public function bindDocumentToPayment(int $documentId, int $paymentId): Result
	{
		if (!$this->isEnabled())
		{
			return (new Result())->addError(new Error('Document Generator is not available'));
		}

		DocumentBindingTable::deleteBindings(\CCrmOwnerType::OrderPaymentName, $paymentId);

		return DocumentBindingTable::bindDocument(
			$documentId,
			\CCrmOwnerType::OrderPaymentName,
			$paymentId
		);
	}

	public function clearPaymentBindings(int $paymentId): void
	{
		if ($this->isEnabled())
		{
			DocumentBindingTable::deleteBindings(\CCrmOwnerType::OrderPaymentName, $paymentId);
		}
	}

	public function getLastBoundPaymentDocumentTemplateId(): ?int
	{
		$lastBinding = DocumentBindingTable::getList([
			'select' => ['DOCUMENT.TEMPLATE_ID'],
			'order' => ['ID' => 'DESC'],
			'filter' => [
				'=ENTITY_NAME' => \CCrmOwnerType::OrderPaymentName,
			],
			'limit' => 1,
		])->fetch();

		return $lastBinding['DOCUMENT.TEMPLATE_ID'] ?? null;
	}

	public function createDocumentForItem(ItemIdentifier $identifier, int $templateId, int $paymentId = null): Result
	{
		$result = new Result();

		$provider = $this->getCrmOwnerTypeProvidersMap()[$identifier->getEntityTypeId()] ?? null;
		if (!$provider)
		{
			return $result->addError(new Error('Provider for entityTypeId ' . $identifier->getEntityTypeId() . ' is not found'));
		}
		$template = \Bitrix\DocumentGenerator\Template::loadById($templateId);
		if (!$template)
		{
			return $result->addError(new Error('Template ' . $templateId . ' not found'));
		}
		$template->setSourceType($provider);
		$document = \Bitrix\DocumentGenerator\Document::createByTemplate($template, $identifier->getEntityId());
		if (!$document)
		{
			return $result->addError(new Error('Could not create document'));
		}
		CreationMethod::markDocumentAsCreatedByPublic($document);
		if ($paymentId > 0)
		{
			$document->setValues([
				static::VALUE_PAYMENT_ID => $paymentId,
			]);
		}

		return $document->getFile(true, true);
	}

	public function copyTemplatesProviders(string $sourceProvider, string $destinationProvider): Result
	{
		$result = new Result();

		$templates = TemplateTable::getListByClassName($sourceProvider);
		foreach ($templates as $template)
		{
			try
			{
				$addResult = TemplateProviderTable::add([
					'TEMPLATE_ID' => $template['ID'],
					'PROVIDER' => $destinationProvider,
				]);
				if (!$addResult->isSuccess())
				{
					$result->addErrors($addResult->getErrors());
				}
			}
			catch (SqlQueryException $exception)
			{
				$result->addError(new Error($exception->getMessage()));
			}
		}

		return $result;
	}

	public function createDocumentActivity(
		Document $document,
		ItemIdentifier $itemIdentifier,
		?int $userId = null
	): Result
	{
		$provider = new \Bitrix\Crm\Activity\Provider\Document();

		if (!$userId)
		{
			$userId = Container::getInstance()->getContext()->getUserId();
		}

		return $provider->createActivity(
			\Bitrix\Crm\Activity\Provider\Document::PROVIDER_TYPE_ID_DOCUMENT,
			[
				'BINDINGS' => [
					[
						'OWNER_TYPE_ID' => $itemIdentifier->getEntityTypeId(),
						'OWNER_ID' => $itemIdentifier->getEntityId(),
					],
				],
				'ASSOCIATED_ENTITY_ID' => $document->ID,
				'SUBJECT' => $document->getTitle(),
				'COMPLETED' => 'N',
				'RESPONSIBLE_ID' => $userId,
			]
		);
	}

	protected function getActualizeQueue(): ?ActualizeQueue
	{
		if (!$this->isEnabled())
		{
			return null;
		}
		try
		{
			$queue = ServiceLocator::getInstance()->get('documentgenerator.service.actualizeQueue');
		}
		catch(\Bitrix\Main\ObjectNotFoundException $e)
		{
			$queue = null;
		}

		return $queue;
	}

	/**
	 * todo move to activity broker
	 *
	 * @param ItemIdentifier $itemIdentifier
	 * @return array
	 */
	final protected function getItemScheduledDocumentActivities(ItemIdentifier $itemIdentifier): array
	{
		if (isset($this->scheduledActivitiesCache[$itemIdentifier->getHash()]))
		{
			return $this->scheduledActivitiesCache[$itemIdentifier->getHash()];
		}
		$result = [];
		$filter = [
			'STATUS' => \CCrmActivityStatus::Waiting,
			'OWNER_TYPE_ID' => $itemIdentifier->getEntityTypeId(),
			'OWNER_ID' => $itemIdentifier->getEntityId(),
			'COMPLETED' => 'N',
			'TYPE_ID' => \CCrmActivityType::Provider,
			'PROVIDER_ID' => \Bitrix\Crm\Activity\Provider\Document::getId(),
			'PROVIDER_TYPE_ID' => \Bitrix\Crm\Activity\Provider\Document::PROVIDER_TYPE_ID_DOCUMENT,
		];
		$list = \CCrmActivity::GetList(
			['DEADLINE' => 'ASC'],
			$filter,
			false,
			false,
		);
		while ($activity = $list->fetch())
		{
			$result[] = $activity;
		}

		$this->scheduledActivitiesCache[$itemIdentifier->getHash()] = $result;

		return $result;
	}

	private function removeActivityFromCache(array $activity): void
	{
		$ownerTypeId = (int)($activity['OWNER_TYPE_ID'] ?? \CCrmOwnerType::Undefined);
		$ownerId = (int)($activity['OWNER_ID'] ?? 0);

		if (\CCrmOwnerType::isCorrectEntityTypeId($ownerTypeId) && $ownerId > 0)
		{
			$owner = new ItemIdentifier($ownerTypeId, $ownerId);

			unset($this->scheduledActivitiesCache[$owner->getHash()]);
		}
	}

	private function deleteDocumentActivity(int $documentId): void
	{
		$activity = \CCrmActivity::GetList(
			[],
			[
				'TYPE_ID' => \CCrmActivityType::Provider,
				'PROVIDER_ID' => \Bitrix\Crm\Activity\Provider\Document::getId(),
				'PROVIDER_TYPE_ID' => \Bitrix\Crm\Activity\Provider\Document::PROVIDER_TYPE_ID_DOCUMENT,
				'ASSOCIATED_ENTITY_ID' => $documentId,
			],
			false,
			false,
		)->Fetch();

		if ($activity)
		{
			\CCrmActivity::Delete($activity['ID'], false);
			$this->removeActivityFromCache($activity);
		}
	}

	final protected function enqueueDocumentForActualization(
		int $documentId,
		?int $userId = null,
		string $position = self::ACTUALIZATION_POSITION_QUEUE
	): void
	{
		$queue = $this->getActualizeQueue();
		if (!$queue)
		{
			return;
		}

		$task = new ActualizeQueue\Task($documentId);
		if ($userId > 0)
		{
			$task->setUserId($userId);
		}
		$task->setPosition($position);

		$queue->addTask($task);
	}

	/**
	 * Add scheduled documents found by $itemIdentifier to the queue for actualization on $position.
	 *
	 * @param ItemIdentifier $itemIdentifier
	 * @param int|null $userId
	 * @param string $position
	 * @return void
	 */
	final public function enqueueItemScheduledDocumentsForActualization(
		ItemIdentifier $itemIdentifier,
		int $userId = null,
		string $position = self::ACTUALIZATION_POSITION_QUEUE
	): void
	{
		$queue = $this->getActualizeQueue();
		if (!$queue)
		{
			return;
		}

		$activities = $this->getItemScheduledDocumentActivities($itemIdentifier);

		foreach ($activities as $activity)
		{
			$documentId = (int)($activity['ASSOCIATED_ENTITY_ID'] ?? 0);
			if ($documentId > 0)
			{
				$this->enqueueDocumentForActualization(
					$documentId,
					$userId,
					$position
				);
			}
		}
	}

	public function actualizeDocumentImmediately(Document $document): void
	{
		$queue = $this->getActualizeQueue();
		if (!$queue)
		{
			return;
		}

		$queue->addTask(
			ActualizeQueue\Task::createByDocument($document)
				->setPosition(ActualizeQueue\Task::ACTUALIZATION_POSITION_IMMEDIATELY)
		);
	}
}
