<?php

namespace Bitrix\HumanResources\Integration\UI;

use Bitrix\HumanResources\Builder\Structure\Filter\Column\EntityIdFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\Column\IdFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\Column\Node\NodeTypeFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\NodeFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\SelectionCondition\Node\NodeAccessFilter;
use Bitrix\HumanResources\Builder\Structure\NodeDataBuilder;
use Bitrix\HumanResources\Builder\Structure\Filter\NodeMemberFilter;
use Bitrix\HumanResources\Builder\Structure\NodeMemberDataBuilder;
use Bitrix\HumanResources\Builder\Structure\Sort\NodeSort;
use Bitrix\HumanResources\Enum\DepthLevel;
use Bitrix\HumanResources\Enum\Direction;
use Bitrix\HumanResources\Enum\SortDirection;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Internals\Entity\Provider\UI\DepartmentProviderOptions;
use Bitrix\HumanResources\Internals\Entity\Provider\UI\DepartmentProviderTabs;
use Bitrix\HumanResources\Internals\Enum\Provider\UI\DepartmentProviderAvatarMode;
use Bitrix\HumanResources\Internals\Enum\Provider\UI\DepartmentProviderTagStyleMode;
use Bitrix\HumanResources\Type\AccessCodeType;
use Bitrix\HumanResources\Type\IntegerCollection;
use Bitrix\HumanResources\Type\StructureAction;
use Bitrix\HumanResources\Item\Collection\NodeCollection;
use Bitrix\HumanResources\Item\Collection\NodeMemberCollection;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Internals\Enum\Provider\UI\DepartmentProviderSelectMode;
use Bitrix\HumanResources\Internals\Enum\Provider\UI\DepartmentProviderTabId;
use Bitrix\HumanResources\Util\StructureHelper;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Socialnetwork\Integration\UI\EntitySelector\UserProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\SearchQuery;
use Bitrix\UI\EntitySelector\Tab;

/**
 * @see \Bitrix\Intranet\Integration\UI\EntitySelector\DepartmentProvider
 */
class DepartmentProvider extends BaseStructureProvider
{
	public const ENTITY_ID = 'structure-node';
	/**
	 * @deprecated use DepartmentProviderSelectMode::USERS_ONLY
	 */
	public const MODE_USERS_ONLY = 'usersOnly';
	private const IMAGE_DEPARTMENT_OPTION = '/bitrix/js/humanresources/entity-selector/src/images/department-option.svg';
	private const IMAGE_TEAM_OPTION = '/bitrix/js/humanresources/entity-selector/src/images/team-option.svg';
	private DepartmentProviderTabs $providerTabs;

	/**
	 * @throws SystemException
	 */
	public function __construct(array $options = [])
	{
		parent::__construct($options);

		$this->providerTabs = new DepartmentProviderTabs(
			$this->providerOptions->includedNodeEntityTypes,
			$this->providerOptions->useMultipleTabs
		);
	}
//	Typed property Bitrix\HumanResources\Internals\Entity\Provider\UI\DepartmentProviderOptions::$restricted must not be accessed before initialization

	/**
	 * @throws LoaderException
	 */
	protected function initProviderOptions(array $options = []): DepartmentProviderOptions
	{
		return new DepartmentProviderOptions($options);
	}

	/**
	 * @throws WrongStructureItemException
	 */
	public function getChildren(Item $parentItem, Dialog $dialog): void
	{
		$parentNode = NodeDataBuilder::createWithFilter(
			new NodeFilter(
				idFilter: IdFilter::fromId((int)$parentItem->getId()),
				entityTypeFilter: NodeTypeFilter::fromNodeTypes($this->providerOptions->includedNodeEntityTypes),
				accessFilter: $this->providerOptions->accessFilter,
			),
		)
			->setLimit(1)
			->get()
		;

		if ($parentNode === null)
		{
			return;
		}

		$includedTypes = $this->providerOptions->includedNodeEntityTypes;
		if (!in_array(NodeEntityType::DEPARTMENT, $includedTypes, true))
		{
			$includedTypes[] = NodeEntityType::DEPARTMENT;
		}

		if ($this->providerOptions->useMultipleTabs)
		{
			$includedTypes = [$parentNode->type];
		}

		$nodes = $this->getStructure([
			'parentId' => $parentNode->id,
			'nodeTypes' => $includedTypes,
			'depthLevel' => DepthLevel::FIRST,
		]);

		if (!$nodes->empty())
		{
			$this->fillNodes($dialog, $nodes);
		}

		if ($this->providerOptions->selectMode === DepartmentProviderSelectMode::DepartmentsOnly)
		{
			return;
		}

		$items = $this->makeUsersItemsForNode($parentNode, $dialog);

		$dialog->addItems($items);
	}

	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		$selectMode = $this->providerOptions->selectMode;
		if ($selectMode === DepartmentProviderSelectMode::UsersOnly)
		{
			return;
		}

		$nodes = $this->getStructure(
			[
				'searchQuery' => $searchQuery->getQuery(),
				'nodeTypes' => $this->providerOptions->includedNodeEntityTypes,
			],
		);

		$limitExceeded = $this->getLimit() <= $nodes->count();
		if ($limitExceeded)
		{
			$searchQuery->setCacheable(false);
		}

		foreach ($nodes as $node)
		{
			$isRootDepartment = (int)$node->parentId === 0;
			$hideRootDepartment = $isRootDepartment && !$this->providerOptions->allowSelectRootDepartment;
			if ($hideRootDepartment)
			{
				continue;
			}

			$item = new Item(
				[
					'id' => $node->id,
					'entityId' => self::ENTITY_ID,
					'title' => $node->name,
					'customData' => [
						'accessCode' => $this->getSimpleNodeAccessCodeString($node),
					],
				],
			);
			$this->updateItemViewOptions($item, $node);
			$dialog->addItem($item);

			if ($selectMode === DepartmentProviderSelectMode::UsersAndDepartments && $this->providerOptions->allowFlatDepartments)
			{
				$dialog->addItem(
					new Item(
						[
							'id' => $this->addFlatModeValuePostfix($node->id ?? ''),
							'entityId' => self::ENTITY_ID,
							'title' => Loc::getMessage(
								'HUMANRESOURCES_ENTITY_SELECTOR_ONLY_EMPLOYEES_MSGVER_1',
								['#NODE_NAME#' => $node->name]
							),
							'customData' => [
								'accessCode' => $this->getSimpleNodeAccessCodeString($node) ?? '',
							],
						],
					),
				);
			}
		}
	}

	/**
	 * @throws WrongStructureItemException
	 */
	public function fillDialog(Dialog $dialog): void
	{
		$this->providerTabs->addTabsIntoDialog($dialog);

		if (!$this->providerOptions->fillDepartmentsTab && !$this->providerOptions->fillRecentTab)
		{
			return;
		}

		$nodes = $this->fetchNodes($this->providerOptions->includedNodeEntityTypes);

		$hasMoreNodes = $this->existAnyChildrenForNodes($nodes);
		if ($this->providerOptions->selectMode === DepartmentProviderSelectMode::UsersOnly || !$hasMoreNodes)
		{
			$entity = $dialog->getEntity('structure-node');
			$entity?->setDynamicSearch(false);
		}

		$forceDynamic = $this->providerOptions->selectMode === DepartmentProviderSelectMode::DepartmentsOnly && !$hasMoreNodes ? false : null;

		if ($this->providerOptions->fillRecentTab)
		{
			$this->fillRecentDepartments($dialog, $nodes);
		}

		if ($this->providerOptions->fillDepartmentsTab)
		{
			$this->fillNodes($dialog, $nodes, $forceDynamic);
		}
	}

	private function getUserOptions(Dialog $dialog): array
	{
		if (!empty($userOptions = $this->providerOptions->userOptions))
		{
			return $userOptions;
		}
		if ($dialog->getEntity('user') && is_array($dialog->getEntity('user')->getOptions()))
		{
			return $dialog->getEntity('user')->getOptions();
		}

		return [];
	}

	private function getCurrentUserDepartments(): ?NodeMemberCollection
	{
		$currentUserId = CurrentUser::get()->getId();

		if (!$currentUserId || $this->providerOptions->allowOnlyUserDepartments !== true)
		{
			return new NodeMemberCollection();
		}

		return NodeMemberDataBuilder::createWithFilter(
			new NodeMemberFilter(
				entityIdFilter: EntityIdFilter::fromEntityId($currentUserId),
				entityType: MemberEntityType::USER,
			),
		)
			->getAll()
		;
	}

	private function fillRecentDepartments(Dialog $dialog, NodeCollection $nodes): void
	{
		foreach ($nodes as $node)
		{
			$isRootDepartment = (int)$node->parentId === 0;
			$hideRootDepartment = $isRootDepartment && !$this->providerOptions->allowSelectRootDepartment;

			if ($hideRootDepartment && $isRootDepartment)
			{
				continue;
			}

			$item = new Item(
				[
					'id' => $node->id,
					'entityId' => self::ENTITY_ID,
					'title' => $node->name,
					'tabs' => DepartmentProviderTabId::Recent,
					'customData' => [
						'accessCode' => $this->getSimpleNodeAccessCodeString($node),
					],
				],
			);
			$this->updateItemViewOptions($item, $node);

			$dialog->addRecentItem($item);
		}
	}

	/**
	 * @throws WrongStructureItemException
	 */
	private function fillNodes(Dialog $dialog, NodeCollection $nodes, ?bool $forceDynamic = null): void
	{
		/** @var array<int, Item> $parents */
		$parents = [];
		if ($this->providerOptions->allowOnlyUserDepartments)
		{
			$currentDepartments = $this->getCurrentUserDepartments();
			$allowedNodeCollection = new NodeCollection();

			foreach ($currentDepartments as $currentDepartment)
			{
				$allowedNodeCollection->add(
					new Node(
						name: '',
						type: NodeEntityType::DEPARTMENT,
						structureId: 0,
						id: $currentDepartment->nodeId,
					),
				);
			}

			$integerIds = array_map('intval', array_column($allowedNodeCollection->getItemMap(), 'id'));
			if (empty($integerIds))
			{
				$nodes = new NodeCollection();
			}
			else
			{
				$idFilter = new IdFilter(new IntegerCollection(...$integerIds));
				$entityTypeFilter = NodeTypeFilter::fromNodeTypes($this->providerOptions->includedNodeEntityTypes);

				$nodeFilter = new NodeFilter(
					idFilter: $idFilter,
					entityTypeFilter: $entityTypeFilter,
					direction: Direction::CHILD,
					depthLevel: DepthLevel::FULL,
					accessFilter: $this->providerOptions->accessFilter,
				);

				$nodes = NodeDataBuilder::createWithFilter($nodeFilter)
					->getAll()
					->orderMapByInclude()
				;
			}
		}

		$selectMode = $this->providerOptions->selectMode;
		foreach ($nodes as $node)
		{
			$isRootDepartment = (int)$node->parentId === 0;
			$hideRootDepartment = $isRootDepartment && !$this->providerOptions->allowSelectRootDepartment;

			$availableInRecentTab = $selectMode !== DepartmentProviderSelectMode::UsersOnly;

			$childDepartmentCount = null;
			if ($this->providerOptions->shouldCountSubdepartments)
			{
				$childDepartmentCount = NodeDataBuilder::createWithFilter(
					new NodeFilter(
						idFilter: IdFilter::fromId($node->id),
						entityTypeFilter: NodeTypeFilter::fromNodeTypes($this->providerOptions->includedNodeEntityTypes),
						direction: Direction::CHILD,
						depthLevel: DepthLevel::FIRST,
						active: true,
						accessFilter: $this->providerOptions->accessFilter,
					)
				)
					->setSort(new NodeSort(depth: SortDirection::Asc, sort: SortDirection::Asc))
					->getAll()
					->count()
				;
			}

			$usersCount = null;
			if ($this->providerOptions->shouldCountUsers)
			{
				$usersCount = $this->countUsersInNode(
					$node,
				);
			}

			$tabs = $this->providerTabs->getTabsForNode($node);
			if (empty($tabs))
			{
				continue;
			}

			$tabIds = array_map(static fn(Tab $tab): string => $tab->getId() ?? '', $tabs);
			$item = new Item(
				[
					'id' => $node->id,
					'entityId' => self::ENTITY_ID,
					'title' => $node->name,
					'tabs' => $tabIds,
					'searchable' => $availableInRecentTab,
					'availableInRecentTab' => $availableInRecentTab,
					'customData' => [
						'subdepartmentsCount' => $childDepartmentCount,
						'usersCount' => $usersCount,
						'accessCode' => $this->getSimpleNodeAccessCodeString($node),
					],
					'nodeOptions' => [
						'dynamic' => !$this->providerOptions->isFlatMode && (!is_bool($forceDynamic) || $forceDynamic),
						'open' => $isRootDepartment,
					],
				],
			);
			$this->updateItemViewOptions($item, $node);

			if ($selectMode === DepartmentProviderSelectMode::DepartmentsOnly && !$hideRootDepartment)
			{
				$this->addSelectableNodeChildItemIfNeed($item, $node);
			}
			elseif ($selectMode === DepartmentProviderSelectMode::UsersAndDepartments)
			{
				$this->fillNodesForUserAndDepartments($hideRootDepartment, $item, $node);
			}

			if ($this->providerOptions->isFlatMode)
			{
				$dialog->addItem($item);

				continue;
			}

			$parentItem = $parents[$node->parentId] ?? null;
			$parentNode = $nodes->getItemById($node->parentId);
			if ($parentItem && ($parentNode->type === $node->type || !$this->providerOptions->useMultipleTabs))
			{
				$parentItem->addChild($item);
			}
			else
			{
				$dialog->addItem($item);
			}

			$parents[$node->id] = $item;
		}
	}

	private function addSelectableNodeChildItemIfNeed(Item $item, Node $node): void
	{
		$nodeTitle = $node->isTeam()
			? Loc::getMessage('HUMANRESOURCES_ENTITY_SELECTOR_SELECT_TEAM')
			: Loc::getMessage('HUMANRESOURCES_ENTITY_SELECTOR_SELECT_DEPARTMENT');

		if (
			!$this->providerOptions->useMultipleTabs
			|| $node->isTeam()
			|| $node->isDepartment()
		)
		{
			$item->addChild(
				new Item(
					[
						'id' => $node->id,
						'title' => $node->name,
						'entityId' => self::ENTITY_ID,
						'nodeOptions' => [
							'title' => $nodeTitle,
							'renderMode' => 'override',
						],
						'customData' => ['accessCode' => $this->getSimpleNodeAccessCodeString($node)],
					],
				),
			);
		}
	}

	private function fillNodesForUserAndDepartments(bool $hideRootDepartment, Item $item, Node $node): void
	{
		if (!$hideRootDepartment && !$this->providerOptions->isFlatMode)
		{
			$avatar = null;
			$title = null;

			if ($node->isDepartment())
			{
				$avatar = self::IMAGE_DEPARTMENT_OPTION;
				$title = Loc::getMessage(
					$this->providerOptions->isForSearch
						? 'HUMANRESOURCES_ENTITY_SELECTOR_ALL_EMPLOYEES_SELECT'
						: 'HUMANRESOURCES_ENTITY_SELECTOR_ALL_EMPLOYEES_SUBDIVISIONS',
				);
			}
			elseif ($node->isTeam())
			{
				$avatar = self::IMAGE_TEAM_OPTION;
				$title = Loc::getMessage('HUMANRESOURCES_ENTITY_SELECTOR_ALL_TEAM_EMPLOYEES_SUBTEAMS_MSGVER_1');
			}

			$item->addChild(
				new Item(
					[
						'id' => $node->id,
						'title' => $node->name,
						'entityId' => self::ENTITY_ID,
						'nodeOptions' => [
							'title' => $title,
							'avatar' => $avatar,
							'renderMode' => 'override',
						],
						'customData' => [
							'accessCode' => $this->getRecursiveAccessCodeString($node),
						],
					],
				),
			);
		}

		if ($this->providerOptions->allowFlatDepartments && !$this->providerOptions->isFlatMode)
		{
			if ($node->isDepartment())
			{
				$item->addChild(
					new Item(
						[
							'id' => $this->addFlatModeValuePostfix($node->id ?? ''),
							'entityId' => self::ENTITY_ID,
							'title' => Loc::getMessage(
								'HUMANRESOURCES_ENTITY_SELECTOR_ONLY_EMPLOYEES_MSGVER_1',
								['#NODE_NAME#' => $node->name]
							),
							'nodeOptions' => [
								'title' => Loc::getMessage(
									'HUMANRESOURCES_ENTITY_SELECTOR_ONLY_DEPARTMENT_EMPLOYEES',
								),
								'avatar' => self::IMAGE_DEPARTMENT_OPTION,
								'renderMode' => 'override',
							],
							'customData' => [
								'accessCode' => $this->addFlatModeValuePostfix($this->getSimpleNodeAccessCodeString($node) ?? ''),
							],
						],
					),
				);
			}
			elseif ($node->isTeam())
			{
				$item->addChild(
					new Item(
						[
							'id' => $this->addFlatModeValuePostfix($node->id ?? ''),
							'entityId' => self::ENTITY_ID,
							'title' => Loc::getMessage(
								'HUMANRESOURCES_ENTITY_SELECTOR_ONLY_EMPLOYEES_MSGVER_1',
								['#NODE_NAME#' => $node->name]
							),
							'nodeOptions' => [
								'title' => Loc::getMessage('HUMANRESOURCES_ENTITY_SELECTOR_ONLY_TEAM_EMPLOYEES_MSGVER_1'),
								'avatar' => self::IMAGE_TEAM_OPTION,
								'renderMode' => 'override',
							],
							'customData' => [
								'accessCode' => $this->addFlatModeValuePostfix($this->getSimpleNodeAccessCodeString($node) ?? ''),
							],
						],
					),
				);
			}
		}
	}

	/**
	 * @param array<int|string> $ids
	 * @param bool $useViewRule
	 * @return array
	 * @throws WrongStructureItemException|SystemException|ObjectPropertyException|ArgumentException
	 */
	public function getDepartments(array $ids, bool $useViewRule = false): array
	{
		$integerIds = array_map(intval(...), $ids);
		$idMap = array_combine($ids, $integerIds);
		$structure = StructureHelper::getDefaultStructure();

		$items = [];
		$nodeBuilder = new NodeDataBuilder();
		if ($useViewRule)
		{
			$nodes = $nodeBuilder
				->addFilter(
					new NodeFilter(
						idFilter: new IdFilter(new IntegerCollection(...$integerIds)),
						entityTypeFilter: NodeTypeFilter::fromNodeTypes($this->providerOptions->includedNodeEntityTypes),
						structureId: $structure?->id,
						active: $this->providerOptions->nodeActiveFilter,
						accessFilter: new NodeAccessFilter(StructureAction::ViewAction),
					)
				)
				->getAll()
				->orderMapByInclude()
			;

			$rootDepartment = $this->getRootDepartment();
			if (
				in_array($rootDepartment?->id, $integerIds, true)
				&& !$nodes->getItemById($rootDepartment?->id)
			)
			{
				$nodes->add($rootDepartment);
			}
		}
		else
		{
			$nodes = $nodeBuilder
				->addFilter(
					new NodeFilter(
						idFilter: new IdFilter(new IntegerCollection(...$integerIds)),
						entityTypeFilter: NodeTypeFilter::fromNodeTypes($this->providerOptions->includedNodeEntityTypes),
						structureId: $structure?->id,
						active: $this->providerOptions->nodeActiveFilter,
						accessFilter: $this->providerOptions->accessFilter,
					)
				)
				->getAll()
				->orderMapByInclude()
			;
		}

		if ($nodes->count() > 0)
		{
			foreach ($idMap as $id => $integerId)
			{
				$node = $nodes->getItemById($integerId);
				if (!$node)
				{
					continue;
				}

				$isFlatDepartment = is_string($id) && $id[-1] === 'F';
				if ($isFlatDepartment)
				{
					$availableInRecentTab =
						$this->providerOptions->selectMode === DepartmentProviderSelectMode::UsersAndDepartments
						&& $this->providerOptions->allowFlatDepartments
					;
				}
				else
				{
					$availableInRecentTab = $this->providerOptions->selectMode !== DepartmentProviderSelectMode::UsersOnly;
					if ($node->depth === 1 && !$this->providerOptions->allowSelectRootDepartment)
					{
						$availableInRecentTab = false;
					}
				}

				$title = $isFlatDepartment
					? Loc::getMessage(
						'HUMANRESOURCES_ENTITY_SELECTOR_ONLY_EMPLOYEES_MSGVER_1',
						[
							'#NODE_NAME#' => $node->name,
						],
					)
					: $node->name;

				$item = new Item(
					[
						'id' => $id,
						'entityId' => self::ENTITY_ID,
						'title' => $title,
						'availableInRecentTab' => $availableInRecentTab,
						'searchable' => $availableInRecentTab,
						'customData' => [
							'accessCode' => $this->getSimpleNodeAccessCodeString($node),
						],
					],
				);
				$this->updateItemViewOptions($item, $node);

				$items[] = $item;
			}
		}

		return $items;
	}

	/**
	 * Add postfix (only employees) to department value in flat mode
	 * @param string|int $value
	 * @return string
	 */
	private function addFlatModeValuePostfix(string|int $value): string
	{
		return $value . ':F';
	}

	private function updateItemViewOptions(Item $item, Node $node): void
	{
		if ($node->isTeam())
		{
			$this->applyTeamTagOptions($item);

			return;
		}

		$isRoot = (int)$node->parentId === 0;
		$this->applyDepartmentTagOptions($item, $isRoot);
	}

	private function applyTeamTagOptions(
		Item $item,
	): void
	{
		$tagOptions = $item->getTagOptions();
		$isDefaultTagStyle = $this->providerOptions->tagStyle === DepartmentProviderTagStyleMode::Default;

		$item->setSupertitle(Loc::getMessage('HUMANRESOURCES_ENTITY_SELECTOR_TEAM_SUPER_TITLE'));

		if ($this->providerOptions->showIcons)
		{
			$icon = self::IMAGE_TEAM;
			$this->placeAvatar($item, $icon, $this->providerOptions->avatarMode);
			if ($isDefaultTagStyle)
			{
				$tagOptions->set('avatar', $icon);
			}
		}

		if ($isDefaultTagStyle)
		{
			$tagOptions->set('fontWeight', '700');
			$tagOptions->set('bgColor', 'rgba(0, 117, 255, 0.12)');
			$tagOptions->set('textColor', 'rgba(0, 117, 255, 0.69)');
		}
	}

	private function applyDepartmentTagOptions(
		Item $item,
		bool $isRoot,
	): void
	{
		$isDefaultTagStyle = $this->providerOptions->tagStyle === DepartmentProviderTagStyleMode::Default;
		$tagOptions = $item->getTagOptions();

		$item->setSupertitle(Loc::getMessage('HUMANRESOURCES_ENTITY_SELECTOR_DEPARTMENT_SUPER_TITLE'));
		if ($this->providerOptions->showIcons)
		{
			$icon = $isRoot
				? self::IMAGE_COMPANY
				: self::IMAGE_DEPARTMENT;

			$this->placeAvatar($item, $icon, $this->providerOptions->avatarMode);
			if ($isDefaultTagStyle)
			{
				$tagOptions->set('avatar', $icon);
			}
		}

		if ($isDefaultTagStyle)
		{
			$tagOptions->set('fontWeight', '700');
			if ($isRoot)
			{
				$tagOptions->set('bgColor', '#F1FBD0');
				$tagOptions->set('textColor', '#7FA800');
			}
			else
			{
				$tagOptions->set('bgColor', '#ade7e4');
				$tagOptions->set('textColor', '#207976');
			}
		}
	}

	private function placeAvatar(Item $item, string $path, DepartmentProviderAvatarMode $mode): void
	{
		if ($mode === DepartmentProviderAvatarMode::None)
		{
			return;
		}

		if ($mode === DepartmentProviderAvatarMode::Item || $mode === DepartmentProviderAvatarMode::Both)
		{
			$item->setAvatar($path);
		}

		if ($mode === DepartmentProviderAvatarMode::Node || $mode === DepartmentProviderAvatarMode::Both)
		{
			$item->getNodeOptions()->set('avatar', $path);
		}
	}

	/**
	 * @param list<NodeEntityType> $includedTypes
	 */
	private function fetchNodes(array $includedTypes): NodeCollection
	{
		if (!$this->providerOptions->isFlatMode && !$this->providerOptions->useMultipleTabs)
		{
			return $this->getStructure(
				['depthLevel' => $this->providerOptions->depthLevel, 'nodeTypes' => $includedTypes],
			);
		}

		$nodes = new NodeCollection();
		$count = count($includedTypes);
		if ($count === 0)
		{
			return $nodes;
		}

		$limitPerType = $this->getLimit() / $count;
		foreach ($includedTypes as $includedType)
		{
			if ($this->providerOptions->isFlatMode)
			{
				$nodes = $this
					->getStructure(
						[
							'limit' => $limitPerType,
							'nodeTypes' => [$includedType],
						],
					)
					->merge($nodes)
				;

				continue;
			}

			if ($includedType === NodeEntityType::DEPARTMENT)
			{
				if (
					$this->providerOptions->structureAction === StructureAction::CreateAction
					&& count($includedTypes) > 1
				)
				{
					continue;
				}

				$nodes = $this
					->getStructure(
						['depthLevel' => $this->providerOptions->depthLevel, 'nodeTypes' => [$includedType]],
					)
					->merge($nodes)
				;
			}
			if ($includedType === NodeEntityType::TEAM)
			{
				if ($this->providerOptions->structureAction === StructureAction::CreateAction)
				{
					$limit = 300;
				}

				$nodes = $this
					->getStructure([
						'nodeTypes' => [NodeEntityType::TEAM],
						'limit' => $limit ?? null,
					])
					->merge($nodes)
				;
			}
		}

		return $nodes;
	}

	/**
	 * @todo refactor it to reuse logic in other places
	 */
	private function getRecursiveAccessCodeString(Node $node): string
	{
		$accessCodeType = match ($node->type)
		{
			NodeEntityType::TEAM => AccessCodeType::HrTeamRecursiveType,
			default => AccessCodeType::HrDepartmentRecursiveType,
		};

		return $accessCodeType->buildAccessCode($node->id ?? 0);
	}

	private function getSimpleNodeAccessCodeString(Node $node): string
	{
		$accessCodeType = match ($node->type)
		{
			NodeEntityType::TEAM => AccessCodeType::HrTeamType,
			default => AccessCodeType::HrDepartmentType,
		};

		return $accessCodeType->buildAccessCode($node->id ?? 0);
	}

	/**
	 * @return list<Item>
	 */
	private function makeUsersItemsForNode(Node $parentNode, Dialog $dialog): array
	{
		$userOptions = $this->getUserOptions($dialog);

		$userItems = $this->fetchUsersItemsForNode($parentNode, $userOptions);

		$headMembers = Container::getNodeMemberService()->getDefaultHeadRoleEmployees($parentNode->id);
		$headIds = array_map(
			static fn($member) => $member->entityId,
			$headMembers->getItemMap(),
		);

		foreach ($userItems as $userItem)
		{
			if (in_array($userItem->getId(), $headIds, true))
			{
				$userItem->getNodeOptions()->set('caption', Loc::getMessage('HUMANRESOURCES_ENTITY_SELECTOR_MANAGER'));

				break;
			}
		}

		return $userItems;
	}

	/**
	 * @return list<Item>
	 */
	private function fetchUsersItemsForNode(Node $node, array $userOptions): array
	{
		$nodeUserIds = NodeMemberDataBuilder::createWithFilter(
			new NodeMemberFilter(
				entityType: MemberEntityType::USER,
				nodeFilter: NodeFilter::createWithNodeId($node->id),
			),
		)
			->getAll()
			->getEntityIds()
		;

		if (empty($nodeUserIds))
		{
			return [];
		}

		return UserProvider::makeItems(
			UserProvider::getUsers(
				['userId' => $nodeUserIds]
				+ $userOptions,
			),
			$userOptions,
		);
	}

	private function countUsersInNode(Node $node): int
	{
		$nodeMembers = NodeMemberDataBuilder::createWithFilter(
			new NodeMemberFilter(
				entityType: MemberEntityType::USER,
				nodeFilter: NodeFilter::createWithNodeId($node->id),
			),
		)
			->getAll()
		;

		return $nodeMembers->count();
	}
}
