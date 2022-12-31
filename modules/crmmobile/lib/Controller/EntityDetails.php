<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Controller;

use Bitrix\Crm\Activity\TodoCreateNotification;
use Bitrix\Crm\Engine\ActionFilter\CheckReadPermission;
use Bitrix\Crm\Engine\ActionFilter\CheckWritePermission;
use Bitrix\Crm\Exclusion\Manager;
use Bitrix\Crm\Item;
use Bitrix\Crm\Item\Company;
use Bitrix\Crm\Item\Contact;
use Bitrix\Crm\Kanban\EntityActivityCounter;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Settings\Crm;
use Bitrix\Crm\Settings\HistorySettings;
use Bitrix\Crm\Timeline\TimelineEntry;
use Bitrix\Crm\UI\EntitySelector;
use Bitrix\CrmMobile\Command\SaveEntityCommand;
use Bitrix\CrmMobile\Entity\FactoryProvider;
use Bitrix\CrmMobile\ProductGrid\ProductGridQuery;
use Bitrix\CrmMobile\Query\EntityEditor;
use Bitrix\CrmMobile\UI\EntityEditor\Provider;
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
use Bitrix\Mobile\UI\EntityEditor\FormWrapper;

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
		if (!Crm::isUniversalActivityScenarioEnabled())
		{
			return [];
		}

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
	 * @param int|null $categoryId
	 * @return array|null
	 */
	public function loadMainAction(Factory $factory, Item $entity, CurrentUser $currentUser,
		int $categoryId = null): array
	{
		$this->registerEntityViewedEvent($factory, $entity);

		$entityEditorQuery = (new EntityEditor($factory, $entity, [
			'CATEGORY_ID' => $categoryId,
			'ENABLE_SEARCH_HISTORY' => 'N',
		]));

		$result = $entityEditorQuery->execute();

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
					'qrUrl' => $this->getDesktopLink($entity),
					'timelinePushTag' => $this->subscribeToTimelinePushEvents($entity, $currentUser),
					'todoNotificationParams' => $this->getTodoNotificationParams($factory, $entity, $permissions),
				],
			]
		);
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

		if (empty($permissions['update']) || $factory->getEntityTypeId() !== \CCrmOwnerType::Deal)
		{
			return null;
		}

		$todoNotificationAvailable = Crm::isUniversalActivityScenarioEnabled() && $factory->isStagesEnabled();
		if (!$todoNotificationAvailable)
		{
			return null;
		}

		$counter = new EntityActivityCounter($entity->getEntityTypeId(), [$entity->getId()]);

		return [
			'isSkipped' => (new TodoCreateNotification($factory->getEntityTypeId()))->isSkipped(),
			'plannedActivityCounter' => $counter->getCounters()[$entity->getId()]['N'] ?? 0,
			'isFinalStage' => (
				$factory->isStagesSupported()
				&& $factory->getStageSemantics($entity->getStageId()) !== PhaseSemantics::PROCESS
			),
		];
	}

	private function getPermissions(Item $entity): array
	{
		$userPermissions = Container::getInstance()->getUserPermissions();
		$crmPermissions = $userPermissions->getCrmPermissions();

		$entityTypeId = $entity->getEntityTypeId();
		$entityId = $entity->getId();
		$categoryId = $entity->isCategoriesSupported() ? $entity->getCategoryId() : null;

		return [
			'read' => $userPermissions->checkReadPermissions($entityTypeId, $entityId, $categoryId),
			'update' => $userPermissions->checkUpdatePermissions($entityTypeId, $entityId, $categoryId),
			'delete' => $userPermissions->checkDeletePermissions($entityTypeId, $entityId, $categoryId),
			'exclude' => !$crmPermissions->HavePerm('EXCLUSION', BX_CRM_PERM_NONE, 'WRITE'),
		];
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
		return (new ProductGridQuery($entity, $currencyId))->execute();
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
		$provider = new Provider($factory, $entity);

		return (new FormWrapper($provider, 'bitrix:crm.entity.editor'))->getRequiredFields($fieldCodes);
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

		if (!empty($client['company']) && $entity->getEntityTypeId() === \CCrmOwnerType::Contact)
		{
			$data['COMPANY_IDS'] = $client['company'];
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

		if ($isCreationFromSelector && $entityId)
		{
			$this->saveEntityInSelectorRecent($factory, $entityId);
		}

		return $entityId;
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
				$text = (string)$entity->getHeading();
				$detailText = $this->getHeaderDetailText($entity);

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
}
