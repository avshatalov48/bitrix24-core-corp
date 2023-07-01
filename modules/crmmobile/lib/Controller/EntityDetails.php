<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Controller;

use Bitrix\Crm\Component\EntityDetails\ComponentMode;
use Bitrix\Crm\Engine\ActionFilter\CheckReadMyCompanyPermission;
use Bitrix\Crm\Engine\ActionFilter\CheckReadPermission;
use Bitrix\Crm\Engine\ActionFilter\CheckWritePermission;
use Bitrix\Crm\Entity\PaymentDocumentsRepository;
use Bitrix\Crm\Exclusion\Manager;
use Bitrix\Crm\Field\Collection;
use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Crm\Item;
use Bitrix\Crm\Item\Company;
use Bitrix\Crm\Item\Contact;
use Bitrix\Crm\Kanban\EntityActivityCounter;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Settings\HistorySettings;
use Bitrix\Crm\Timeline\TimelineEntry;
use Bitrix\Crm\UI\EntitySelector;
use Bitrix\CrmMobile\AhaMoments\GoToChat;
use Bitrix\CrmMobile\Command\SaveEntityCommand;
use Bitrix\CrmMobile\Controller\Filter\CheckRestrictions;
use Bitrix\CrmMobile\Entity\FactoryProvider;
use Bitrix\CrmMobile\ProductGrid\ProductGridQuery;
use Bitrix\CrmMobile\Query\EntityEditor;
use Bitrix\ImOpenlines\Security\Permissions;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\EntitySelector\EntityUsageTable;
use Bitrix\Mobile\Helpers\ReadsApplicationErrors;
use Bitrix\Mobile\UI\DetailCard\Configurator;
use Bitrix\Mobile\UI\DetailCard\Controller;
use Bitrix\Mobile\UI\DetailCard\Tabs;
use Bitrix\Crm\Conversion;

Loader::requireModule('crm');

/**
 * Class EntityDetails
 *
 * @package Bitrix\CrmMobile\Controller
 */
class EntityDetails extends Controller
{
	use ReadsApplicationErrors;
	use PrimaryAutoWiredEntity;
	use PublicErrorsTrait;

	/** @var Factory */
	private $factory;

	/** @var Configurator */
	private $tabConfigurator;
	private ?array $header = null;

	private const ALLOWED_ENTITY_TYPES_WITH_TODO_NOTIFICATION = [
		\CCrmOwnerType::DealName,
		\CCrmOwnerType::LeadName,
	];

	private const STATIC_CONVERSION_QUERY_PARAMS = [
		'lead_id',
		'quote_id',
		'deal_id',
		Conversion\QuoteConversionWizard::QUERY_PARAM_SRC_ID,
		Conversion\DealConversionWizard::QUERY_PARAM_SRC_ID,
	];

	public function configureActions(): array
	{
		$actions = parent::configureActions();

		$actions['getAvailableEntityTypes'] = [
			'+prefilters' => [new CloseSession()],
		];

		$readActions = [
			self::getTabActionName('main'),
			self::getTabActionName('products'),
			self::getTabActionName('timeline'),
			'loadTabCounters',
			'loadToDoNotificationParams',
		];
		foreach ($readActions as $action)
		{
			$actions[$action] = [
				'+prefilters' => [
					new CloseSession(),
					new CheckReadPermission(),
					new CheckReadMyCompanyPermission(),
				],
			];
		}

		$writeActions = ['add', 'addInternal', 'update', 'updateInternal'];
		foreach ($writeActions as $action)
		{
			$actions[$action] = [
				'+prefilters' => [
					new CheckWritePermission(),
				],
			];
		}

		foreach ($actions as &$action)
		{
			$action['+prefilters'][] = new CheckRestrictions();
		}

		return $actions;
	}

	public function loadTabConfigAction(): array
	{
		return $this->getConfigurator()->toArray();
	}

	public function loadTabCountersAction(Item $entity): array
	{
		return $this->getConfigurator()->mapTabs(function (Tabs\Base $tab) use ($entity) {
			$value = 0;

			switch ($tab->getId())
			{
				case 'timeline':
					$entityId = $entity->getId();
					$counter = new EntityActivityCounter($entity->getEntityTypeId(), [$entityId]);
					$deadlinesCount = $counter->getDeadlinesCount($entityId);
					$incomingCount = $counter->getIncomingCount($entityId);
					$value = $deadlinesCount + $incomingCount;
					break;
			}

			return [
				'id' => $tab->getId(),
				'counter' => $value,
			];
		});
	}

	public function getTabIds(): array
	{
		$tabs = $this->getConfigurator()->toArray();

		return array_column($tabs['tabs'], 'id');
	}

	public function getAvailableEntityTypesAction(): array
	{
		return FactoryProvider::getFactoriesMetaData();
	}

	private function getEntityIdFromSourceList(): int
	{
		return (int)$this->findInSourceParametersList('entityId');
	}

	private function getEntityTypeIdFromSourceList(): ?int
	{
		$entityTypeId = (int)$this->findInSourceParametersList('entityTypeId');
		if (!$entityTypeId)
		{
			$entityTypeName = $this->findInSourceParametersList('entityTypeName');
			if ($entityTypeName)
			{
				$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);
			}
		}

		return $entityTypeId ?: null;
	}

	private function getFactory(): Factory
	{
		if ($this->factory === null)
		{
			$entityTypeId = $this->getEntityTypeIdFromSourceList();
			if ($entityTypeId)
			{
				$this->factory = Container::getInstance()->getFactory($entityTypeId);
			}

			if (!$this->factory)
			{
				throw new \DomainException('Could not load factory instance.');
			}
		}

		return $this->factory;
	}

	private function loadEntity(): ?Item
	{
		$entityId = $this->getEntityIdFromSourceList();

		if ($entityId)
		{
			return $this->getFactory()->getItem($entityId);
		}

		return null;
	}

	private function getConfigurator(): Configurator
	{
		if ($this->tabConfigurator === null)
		{
			$configurator = new Configurator($this);
			$configurator->addTab((new Tabs\Editor('main')));
			$configurator->addTab(new Tabs\Timeline('timeline'));

			if ($this->getFactory()->isLinkWithProductsEnabled())
			{
				$configurator->addTab(new Tabs\CrmProduct('products'));
			}

			$this->tabConfigurator = $configurator;
		}

		return $this->tabConfigurator;
	}

	/**
	 * @param Factory $factory
	 * @param Item $entity
	 * @param CurrentUser $currentUser
	 * @return array|null
	 */
	public function loadMainAction(Factory $factory, Item $entity, CurrentUser $currentUser): array
	{
		$this->registerEntityViewedEvent($factory, $entity);

		$entityEditorQuery = new EntityEditor($factory, $entity, $this->getEditorParams($entity));
		$result = [
			'editor' => $entityEditorQuery->execute(),
		];

		if ($entity->isNew())
		{
			return $result;
		}

		$permissions = $this->getPermissions($entity);

		return array_merge(
			$result,
			[
				'header' => $this->getEntityHeader($entity),
				'params' => [
					'permissions' => $permissions,
					'restrictions' => [
						'conversion' => RestrictionManager::isConversionPermitted(),
					],
					'qrUrl' => $this->getDesktopLink($entity),
					'timelinePushTag' => $this->subscribeToTimelinePushEvents($entity, $currentUser),
					'todoNotificationParams' => $this->getTodoNotificationParams($factory, $entity, $permissions),
					'isAutomationAvailable' => $this->getIsAutomationAvailable($entity->getEntityTypeId()),
					'isDocumentPreviewerAvailable' => Option::get('crmmobile', 'release-spring-2023', true),
					'documentGeneratorProvider' => $this->getDocumentGeneratorProvider($entity->getEntityTypeId()),
					'shouldShowAutomationMenuItem' => Option::get('crmmobile', 'release-spring-2023', true),
					'isAvailableReceivePayment' => Option::get('crmmobile', 'release-spring-2023', true),
					'isGoToChatAvailable' => Option::get('crmmobile', 'release-spring-2023', true),
					'ahaMoments' => [
						'goToChat' => (GoToChat::getInstance())->canShow(),
					],
				],
			]
		);
	}

	private function getEditorParams(Item $entity): array
	{
		$params = [
			'ENABLE_SEARCH_HISTORY' => 'N',
			'ENTITY_TYPE_ID' => $entity->getEntityTypeId(),
			'ENTITY_ID' => $entity->getId(),
		];

		if ($entity->isCategoriesSupported())
		{
			$params['CATEGORY_ID'] = $entity->getCategoryId();
		}

		$categoryId = $this->findInSourceParametersList('categoryId');
		if ($this->findInSourceParametersList('categoryId'))
		{
			$params['CATEGORY_ID'] = (int)($categoryId ?? 0);
		}

		if ($this->isCopyMode())
		{
			$params['COMPONENT_MODE'] = ComponentMode::COPING;
		}

		$this->prepareEditorConversionParams($params, $entity->getEntityTypeId());

		$contactId = $this->findInSourceParametersList('contact_id');
		if ($contactId)
		{
			$params['DEFAULT_CONTACT_ID'] = $contactId;
		}

		$phone = $this->findInSourceParametersList('phone');
		if ($phone)
		{
			$params['DEFAULT_PHONE_VALUE'] = $phone;
		}

		$originId = $this->findInSourceParametersList('origin_id');
		if ($originId)
		{
			$params['ORIGIN_ID'] = $originId;
		}

		return $params;
	}

	private function prepareEditorConversionParams(&$params, $entityTypeId)
	{
		$conversionWizard = $this->getConversionWizard();
		if ($conversionWizard !== null)
		{
			$conversionContextParams = $conversionWizard->prepareEditorContextParams(\CCrmOwnerType::ResolveName($entityTypeId));
			$params = array_merge($params, $conversionContextParams);
		}
	}

	private function isCopyMode(): bool
	{
		return (bool)$this->findInSourceParametersList('copy');
	}

	public function loadToDoNotificationParamsAction(Factory $factory, Item $entity): ?array
	{
		return $this->getTodoNotificationParams($factory, $entity);
	}

	protected function getTodoNotificationParams(Factory $factory, Item $entity, array $permissions = null): ?array
	{
		if (!is_array($permissions))
		{
			$permissions = $this->getPermissions($entity);
		}

		if (
			empty($permissions['update'])
			|| !in_array($factory->getEntityName(), self::ALLOWED_ENTITY_TYPES_WITH_TODO_NOTIFICATION, true)
		)
		{
			return null;
		}

		if (!$factory->isStagesEnabled())
		{
			return null;
		}

		$counter = new EntityActivityCounter($entity->getEntityTypeId(), [$entity->getId()]);

		return [
			'notificationSupported' => $factory->isSmartActivityNotificationSupported(),
			'notificationEnabled' => $factory->isSmartActivityNotificationEnabled(),
			'plannedActivityCounter' => $counter->getCounters()[$entity->getId()]['N'] ?? 0,
			'user' => \CCrmViewHelper::getUserInfo(),
			'isFinalStage' => (
				$factory->isStagesSupported()
				&& $factory->getStageSemantics($entity->getStageId()) !== PhaseSemantics::PROCESS
			),
		];
	}

	private function getIsAutomationAvailable($entityTypeId): bool
	{
		return \Bitrix\Crm\Automation\Factory::isAutomationAvailable($entityTypeId);
	}

	private function getDocumentGeneratorProvider(int $entityTypeId): ?string
	{
		$manager = DocumentGeneratorManager::getInstance();
		if (!$manager->isEnabled())
		{
			return null;
		}

		$providersMap = $manager->getCrmOwnerTypeProvidersMap();
		return $providersMap[$entityTypeId] ?? null;
	}

	private function getPermissions(Item $entity): array
	{
		$userPermissions = Container::getInstance()->getUserPermissions();
		$crmPermissions = $userPermissions->getCrmPermissions();

		$entityTypeId = $entity->getEntityTypeId();
		$entityId = $entity->getId();

		$openLinesAccess = $this->hasOpenLinesAccess();

		if ($entityTypeId === \CCrmOwnerType::Company && \CCrmCompany::isMyCompany($entityId))
		{
			$myCompanyPermissions = $userPermissions->getMyCompanyPermissions();

			return [
				'add' => $myCompanyPermissions->canAdd(),
				'read' => $myCompanyPermissions->canRead(),
				'update' => $myCompanyPermissions->canUpdate(),
				'delete' => $myCompanyPermissions->canDelete(),
				'exclude' => false,
				'openLinesAccess' => $openLinesAccess,
			];
		}

		$categoryId = $entity->isCategoriesSupported() ? $entity->getCategoryId() : null;

		return [
			'add' => $userPermissions->checkAddPermissions($entityTypeId, $categoryId),
			'read' => $userPermissions->checkReadPermissions($entityTypeId, $entityId, $categoryId),
			'update' => $userPermissions->checkUpdatePermissions($entityTypeId, $entityId, $categoryId),
			'delete' => $userPermissions->checkDeletePermissions($entityTypeId, $entityId, $categoryId),
			'exclude' => !$crmPermissions->HavePerm('EXCLUSION', BX_CRM_PERM_NONE, 'WRITE'),
			'openLinesAccess' => $openLinesAccess,
		];
	}

	private function hasOpenLinesAccess(): bool
	{
		if (!Loader::includeModule('imopenlines'))
		{
			return false;
		}

		return Permissions::createWithCurrentUser()
			->canPerform(Permissions::ENTITY_LINES, Permissions::ACTION_MODIFY)
		;
	}

	public function getDesktopLink(Item $entity): ?string
	{
		$entityTypeId = $entity->getEntityTypeId();
		$entityId = $entity->getId();
		$categoryId = $entity->isCategoriesSupported() ? $entity->getCategoryId() : null;

		$url = Container::getInstance()->getRouter()->getItemDetailUrl($entityTypeId, $entityId, $categoryId);
		if ($url)
		{
			return $url->getLocator();
		}

		return null;
	}

	private function subscribeToTimelinePushEvents(Item $entity, CurrentUser $currentUser): ?string
	{
		$pushTag = null;
		if (Loader::includeModule('pull'))
		{
			$pushTag = TimelineEntry::prepareEntityPushTag($entity->getEntityTypeId(), $entity->getId());
			\CPullWatch::Add($currentUser->getId(), $pushTag);
		}

		return $pushTag;
	}

	private function registerEntityViewedEvent(Factory $factory, Item $entity): void
	{
		if (!$entity->isNew() && HistorySettings::getCurrent()->isViewEventEnabled())
		{
			$trackedObject = $factory->getTrackedObject($entity);
			Container::getInstance()->getEventHistory()->registerView($trackedObject);
		}
	}

	public function loadProductsAction(Item $entity, ?string $currencyId = null): array
	{
		$this->prepareConversionItemProducts($entity);

		return (new ProductGridQuery($entity, $currencyId))->execute();
	}

	private function prepareConversionItemProducts(Item $entity): void
	{
		$conversionWizard = $this->getConversionWizard();
		if ($conversionWizard !== null)
		{
			$conversionWizard->converter->fillDestinationItemWithDataFromSourceItem(
				$entity,
				[Item::FIELD_NAME_PRODUCTS]
			);
		}
	}

	public function loadTimelineAction(Factory $factory, Item $entity, CurrentUser $currentUser): array
	{
		return array_merge(
			$this->forward(Timeline::class, 'loadTimeline'),
			[
				'params' => [
					'todoNotificationParams' => $this->getTodoNotificationParams($factory, $entity),
				],
			],
		);
	}

	/**
	 * @param Factory $factory
	 * @param Item|null $entity
	 * @param array $fieldCodes
	 * @return array
	 */
	public function getRequiredFieldsAction(Factory $factory, Item $entity, array $fieldCodes = []): array
	{
		$entityEditorQuery = new EntityEditor($factory, $entity, $this->getEditorParams($entity));

		return $entityEditorQuery->execute($fieldCodes);
	}

	public function excludeEntityAction(Item $entity): void
	{
		try
		{
			// permissions are checked inside
			Manager::excludeEntity($entity->getEntityTypeId(), $entity->getId());
		}
		catch (SystemException $e)
		{
			$error = new Error($e->getMessage());
			$errors = $this->markErrorsAsPublic([$error]);
			$this->addErrors($errors);
		}
	}

	public function deleteEntityAction(Factory $factory, Item $entity): void
	{
		$operation = $factory->getDeleteOperation($entity);
		// permissions are checked inside
		$result = $operation->launch();
		if (!$result->isSuccess())
		{
			$errors = $this->markErrorsAsPublic($result->getErrors());
			$this->addErrors($errors);
		}
	}

	public function addInternalAction(
		Factory $factory,
		Item $entity,
		array $data,
		?int $categoryId = null,
		bool $isCreationFromSelector = false,
		?array $client = []
	): ?int
	{
		// setCompatibleData overrides all previous filled fields, so we need to fill category explicitly
		if ($categoryId !== null && $entity->isCategoriesSupported())
		{
			$data[Item::FIELD_NAME_CATEGORY_ID] = $categoryId;
		}
		$entityTypeName = $factory->getEntityName();

		//When converting from a lead, you do not need to create a link between the elements, because it happens in crm.lead.show
		$isNeedAttachConversionItem = true;
		$conversionWizard = $this->getConversionWizard();
		if ($conversionWizard !== null)
		{
			$isNeedAttachConversionItem = $this->prepareConversionData($conversionWizard, $entityTypeName, $data);

			if (!isset($data[Item::FIELD_NAME_PRODUCTS]) || !is_array($data[Item::FIELD_NAME_PRODUCTS]))
			{
				$this->prepareConversionItemProducts($entity);
			}
		}

		if (!empty($client['company']) && $entity->getEntityTypeId() === \CCrmOwnerType::Contact)
		{
			$data['COMPANY_IDS'] = $client['company'];
		}

		if ($this->isCopyMode())
		{
			$this->prepareCopyData($factory, $entity, $data);
		}

		$command = new SaveEntityCommand($factory, $entity, $data);
		$result = $command->execute();

		if (!$result->isSuccess())
		{
			$errors = $this->markErrorsAsPublic($result->getErrors());
			$this->addErrors($errors);

			return null;
		}

		$entityId = (int)$result->getData()['ID'];

		if ($conversionWizard !== null && $isNeedAttachConversionItem)
		{
			$conversionWizard->attachNewlyCreatedEntity($entityTypeName, $entityId);
		}

		if ($isCreationFromSelector && $entityId)
		{
			$this->saveEntityInSelectorRecent($factory, $entityId);
		}

		return $entityId;
	}

	private function getConversionParams(): ?array
	{
		$entityId = null;
		$entityTypeId = null;
		$conversionQueryParams = Conversion\EntityConversionWizard::getQueryParamSource();
		$conversionParamList = [...self::STATIC_CONVERSION_QUERY_PARAMS, $conversionQueryParams['ENTITY_ID']];
		foreach ($conversionParamList as $key)
		{
			$value = (int)($this->findInSourceParametersList($key) ?? 0);
			if ($value > 0)
			{
				$entityId = $value;
			}
			else
			{
				continue;
			}

			if ($key === $conversionQueryParams['ENTITY_ID'])
			{
				$entityTypeId = $this->findInSourceParametersList($conversionQueryParams['ENTITY_TYPE_ID']);
			}
			elseif ($key === Conversion\QuoteConversionWizard::QUERY_PARAM_SRC_ID || $key === 'quote_id')
			{
				$entityTypeId = \CCrmOwnerType::Quote;
			}
			elseif ($key === 'lead_id')
			{
				$entityTypeId = \CCrmOwnerType::Lead;
			}
			elseif ($key === Conversion\DealConversionWizard::QUERY_PARAM_SRC_ID || $key === 'deal_id')
			{
				$entityTypeId = \CCrmOwnerType::Deal;
			}
		}

		if (\CCrmOwnerType::IsDefined($entityTypeId) && $entityId > 0)
		{
			return [
				'ENTITY_TYPE_ID' => (int)($entityTypeId ?? 0),
				'ENTITY_ID' => $entityId ?? 0,
			];
		}

		return null;
	}

	private function getConversionWizard(): ?Conversion\EntityConversionWizard
	{
		$conversionParams = $this->getConversionParams();
		if ($conversionParams !== null)
		{
			return Conversion\ConversionManager::loadWizardByParams($conversionParams);
		}

		return null;
	}

	private function prepareConversionData($conversionWizard, $entityTypeName, &$data): bool
	{
		$conversionContext = $conversionWizard->prepareEditorContextParams($entityTypeName);
		$isConversionLead = $conversionContext['CONVERSION_SOURCE']['entityTypeId'] === \CCrmOwnerType::Lead;
		if ($isConversionLead)
		{
			$data = array_merge($data, $conversionContext);

			return false;
		}

		return true;
	}

	private function prepareCopyData(Factory $factory, Item $entity, array &$data): void
	{
		$sourceFields = $this->getSourceEntityFields($factory, $entity);
		if (empty($sourceFields))
		{
			return;
		}

		if ($factory->isLinkWithProductsEnabled())
		{
			$this->prepareCopyProductRows($data, $sourceFields);
		}

		$this->prepareCopyFileFields($factory->getFieldsCollection(), $data, $sourceFields);
	}

	private function getSourceEntityFields(Factory $factory, Item $entity): array
	{
		$sourceEntityId = (int)$this->findInSourceParametersList('sourceEntityId');
		if (!$sourceEntityId)
		{
			return [];
		}

		$userPermissions = Container::getInstance()->getUserPermissions();
		$hasReadPermission = $userPermissions->checkReadPermissions(
			$entity->getEntityTypeId(),
			$sourceEntityId,
			$entity->isCategoriesSupported() ? $entity->getCategoryId() : null
		);
		if (!$hasReadPermission)
		{
			return [];
		}

		$sourceEntity = $factory->getItem($sourceEntityId);
		if (!$sourceEntity)
		{
			return [];
		}

		return $sourceEntity->getCompatibleData();
	}

	private function prepareCopyProductRows(array &$data, array $sourceFields): void
	{
		if (!isset($data[Item::FIELD_NAME_PRODUCTS]) && isset($sourceFields[Item::FIELD_NAME_PRODUCTS]))
		{
			$data[Item::FIELD_NAME_PRODUCTS] = $sourceFields[Item::FIELD_NAME_PRODUCTS];
		}

		if (!empty($data[Item::FIELD_NAME_PRODUCTS]) && is_array($data[Item::FIELD_NAME_PRODUCTS]))
		{
			foreach ($data[Item::FIELD_NAME_PRODUCTS] as &$productRow)
			{
				unset($productRow['ID']);
			}
		}
	}

	private function prepareCopyFileFields(Collection $fieldCollection, array &$data, array $sourceFields): void
	{
		foreach ($fieldCollection as $fieldName => $field)
		{
			if ($field->getType() !== 'file')
			{
				continue;
			}

			if (empty($data[$fieldName]))
			{
				continue;
			}

			// check if the file id is in the source entity to which we have permissions, exclude random file ids
			if ($field->isMultiple())
			{
				foreach ($data[$fieldName] as &$value)
				{
					if (
						is_numeric($value)
						&& !empty($sourceFields[$fieldName])
						&& is_array($sourceFields[$fieldName])
						&& in_array($value, $sourceFields[$fieldName], true)
					)
					{
						$value = [
							'value' => \CFile::MakeFileArray((int)$value),
							'copy' => true,
						];
					}
				}
			}
			elseif (
				is_numeric($data[$fieldName])
				&& !empty($sourceFields[$fieldName])
				&& $data[$fieldName] === $sourceFields[$fieldName]
			)
			{
				$data[$fieldName] = [
					'value' => \CFile::MakeFileArray((int)$data[$fieldName]),
					'copy' => true,
				];
			}
		}
	}

	private function saveEntityInSelectorRecent(Factory $factory, int $entityId): void
	{
		$entityTypeName = $factory->getEntityName();

		EntityUsageTable::merge([
			'USER_ID' => $this->getCurrentUser()->getId(),
			'CONTEXT' => EntitySelector::CONTEXT,
			'ENTITY_ID' => $entityTypeName,
			'ITEM_ID' => $entityId,
		]);
	}

	public function updateInternalAction(Factory $factory, Item $entity, array $data = []): ?int
	{
		$command = new SaveEntityCommand($factory, $entity, $data);
		$result = $command->execute();
		if (!$result->isSuccess())
		{
			$errors = $this->markErrorsAsPublic($result->getErrors());
			$this->addErrors($errors);

			return null;
		}

		return $result->getData()['ID'] ?? null;
	}

	protected function getEntityTitle(): string
	{
		return '';
	}

	protected function getEntityHeader(Item $entity = null): ?array
	{
		if ($this->header === null)
		{
			$text = '';
			$detailText = '';
			$imageUrl = null;

			if (!$entity)
			{
				$entity = $this->loadEntity();
			}

			if ($entity && !$entity->isNew())
			{
				if ($this->isCopyMode())
				{
					$typeName = mb_strtoupper(\CCrmOwnerType::ResolveName($entity->getEntityTypeId()));
					$text = Loc::getMessage("M_CRM_ENTITY_DETAILS_COPY_TEXT_{$typeName}");
					if (!$text)
					{
						$text = Loc::getMessage('M_CRM_ENTITY_DETAILS_COPY_TEXT');
					}
				}
				else
				{
					$text = (string)$entity->getHeading();
					$detailText = $this->getHeaderDetailText($entity);
				}

				$logo = null;
				$size = null;

				if ($entity->getEntityTypeId() === \CCrmOwnerType::Contact)
				{
					$logo = $entity->get(Contact::FIELD_NAME_PHOTO);
					$size = ['width' => 200, 'height' => 200];
				}
				elseif ($entity->getEntityTypeId() === \CCrmOwnerType::Company)
				{
					$logo = $entity->get(Company::FIELD_NAME_LOGO);
					$size = ['width' => 300, 'height' => 300];
				}

				if (!empty($logo))
				{
					$imageUrl = \CFile::ResizeImageGet(
						$logo,
						$size,
						BX_RESIZE_IMAGE_EXACT,
						false,
						false,
						true
					);
					$imageUrl = $imageUrl['src'] ?? null;
				}
			}

			$this->header = [
				'text' => $text,
				'detailText' => $detailText,
				'imageUrl' => $imageUrl,
			];
		}

		return $this->header;
	}

	private function getHeaderDetailText(Item $entity): string
	{
		if ($entity->getEntityTypeId() === \CCrmOwnerType::Deal)
		{
			if ($entity->get(Item\Deal::FIELD_NAME_IS_REPEATED_APPROACH))
			{
				return Loc::getMessage('M_CRM_ENTITY_DETAILS_REPEATED_APPROACH_DEAL');
			}

			if ($entity->getIsReturnCustomer())
			{
				return Loc::getMessage('M_CRM_ENTITY_DETAILS_REPEATED_DEAL');
			}
		}
		elseif ($entity->getEntityTypeId() === \CCrmOwnerType::Lead)
		{
			if ($entity->getIsReturnCustomer())
			{
				return Loc::getMessage('M_CRM_ENTITY_DETAILS_REPEATED_LEAD');
			}
		}

		return $this->getFactory()->getEntityDescription();
	}

	public function getEntityTotalAmountAction(int $entityId, int $entityTypeId): array
	{
		/**
		 * @var PaymentDocumentsRepository $repository
		 */
		$repository = ServiceLocator::getInstance()->get('crm.entity.paymentDocumentsRepository');
		$data = $repository->getDocumentsForEntity($entityTypeId, $entityId)->getData();

		return [
			'totalAmount' => $data['TOTAL_AMOUNT'] ?? 0,
			'currencyId' => $data['CURRENCY_ID'] ?? '',
		];
	}

	public function getEntityDocumentsAction(int $entityId, int $entityTypeId): array
	{
		/**
		 * @var PaymentDocumentsRepository $repository
		 */
		$repository = ServiceLocator::getInstance()->get('crm.entity.paymentDocumentsRepository');
		$data = $repository->getDocumentsForEntity($entityTypeId, $entityId)->getData();

		return [
			'documents' => $data['DOCUMENTS'] ?? [],
			'totalAmount' => $data['TOTAL_AMOUNT'] ?? 0,
			'currencyId' => $data['CURRENCY_ID'] ?? '',
		];
	}
}
