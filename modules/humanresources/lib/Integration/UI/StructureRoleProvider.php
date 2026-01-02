<?php

namespace Bitrix\HumanResources\Integration\UI;

use Bitrix\HumanResources\Builder\Structure\Filter\Column\IdFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\Column\Node\NodeTypeFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\NodeFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\SelectionCondition\Node\NodeAccessFilter;
use Bitrix\HumanResources\Builder\Structure\NodeDataBuilder;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Internals\Entity\Provider\UI\BaseProviderOptions;
use Bitrix\HumanResources\Type\IntegerCollection;
use Bitrix\HumanResources\Type\StructureAction;
use Bitrix\HumanResources\Item\Collection\NodeCollection;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Util\StructureHelper;
use Bitrix\Main\Access\AccessCode;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\SearchQuery;
use Bitrix\UI\EntitySelector\Tab;

class StructureRoleProvider extends BaseStructureProvider
{
	public const ENTITY_ID = 'structure-role';
	private const TAB_ID_ROLES = 'structure-roles-tab';
	private const IMAGE_ROLE_TEAM_OPTION = '/bitrix/js/humanresources/entity-selector/src/images/role-team-option.svg';
	private const IMAGE_ROLE_DEPARTMENT_OPTION = '/bitrix/js/humanresources/entity-selector/src/images/role-department-option.svg';
	private const IMAGE_ROLE_TEAM_OPTION_SELECTED = '/bitrix/js/humanresources/entity-selector/src/images/role-team-option-selected.svg';
	private const IMAGE_ROLE_DEPARTMENT_OPTION_SELECTED = '/bitrix/js/humanresources/entity-selector/src/images/role-department-option-selected.svg';

	protected function initProviderOptions(array $options = []): BaseProviderOptions
	{
		return new BaseProviderOptions($options);
	}

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

		$nodes = $this->getStructure(['parentId' => $parentNode->id, 'nodeTypes' => $includedTypes]);
		$this->fillNodes($dialog, $nodes);
	}

	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		$nodes = $this->getStructure([
			'nodeTypes' => $this->providerOptions->includedNodeEntityTypes,
			'searchQuery' => $searchQuery->getQuery(),
		]);

		if ($this->getLimit() <= $nodes->count())
		{
			$searchQuery->setCacheable(false);
		}

		foreach ($nodes as $node)
		{
			$dialog->addItems($this->getNodeRoles($node));
		}
	}

	public function fillDialog(Dialog $dialog): void
	{
		$dialog->addTab($this->getTab());
		$nodes = $this->getStructure(['depthLevel' => 1, 'nodeTypes' => $this->providerOptions->includedNodeEntityTypes]);
		$hasMoreNodes = $this->existAnyChildrenForNodes($nodes);
		if (!$hasMoreNodes)
		{
			$entity = $dialog->getEntity('structure-role');
			$entity?->setDynamicSearch(false);
		}

		$forceDynamic = !$hasMoreNodes ? false : null;
		$this->fillNodes($dialog, $nodes, $forceDynamic);
	}

	private function fillNodes(Dialog $dialog, NodeCollection $nodes, ?bool $forceDynamic = null): void
	{
		/** @var array<int, Item> $parents */
		$parents = [];
		foreach ($nodes as $node)
		{
			$isRootDepartment = (int)$node->parentId === 0;

			$item = new Item([
				'id' => $node->id,
				'entityId' => self::ENTITY_ID,
				'title' => $node->name,
				'supertitle' => $node->isDepartment()
					? Loc::getMessage('HUMANRESOURCES_ENTITY_SELECTOR_ROLES_DEPARTMENT_SUPERTITLE')
					: Loc::getMessage('HUMANRESOURCES_ENTITY_SELECTOR_ROLES_TEAM_SUPERTITLE'),
				'tabs' => [self::TAB_ID_ROLES],
				'searchable' => false,
				'availableInRecentTab' => false,
				'nodeOptions' => ['dynamic' => (!is_bool($forceDynamic) || $forceDynamic), 'open' => $isRootDepartment],
			]);

			$this->updateItemViewOptions($item, $node);
			$children = $this->getNodeRoles($node);
			foreach ($children as $child)
			{
				$item->addChild($child);
			}

			$parentItem = $parents[$node->parentId] ?? null;
			if ($parentItem)
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

	/**
	 * @param array<int|string> $ids
	 * @param bool $useViewRule
	 * @return array
	 * @throws ArgumentException|WrongStructureItemException|ObjectPropertyException|SystemException

	 */
	public function getDepartments(array $ids): array
	{
		$structure = StructureHelper::getDefaultStructure();
		$nodeBuilder = new NodeDataBuilder();

		$parseAccessCode = fn($accessCode) => preg_match('/^(AD|AE|AT|ATD|ATE|ATT)(\d+)$/i', $accessCode, $matches)
			? ['accessCode' => $accessCode, 'prefix' => $matches[1], 'nodeId' => (int)$matches[2]]
			: null
		;

		$accessCodeMap = array_filter(array_map($parseAccessCode, $ids));
		$integerIds = array_values(array_unique(array_column($accessCodeMap, 'nodeId')));

		$nodes = $nodeBuilder
			->addFilter(
				new NodeFilter(
					idFilter: new IdFilter(new IntegerCollection(...$integerIds)),
					entityTypeFilter: NodeTypeFilter::fromNodeTypes($this->providerOptions->includedNodeEntityTypes),
					structureId: $structure?->id,
					accessFilter: new NodeAccessFilter(StructureAction::ViewAction),
				)
			)
			->getAll()
			->orderMapByInclude()
		;

		$rootDepartment = $this->getRootDepartment();
		if (in_array($rootDepartment?->id, $integerIds, true) && !$nodes->getItemById($rootDepartment?->id))
		{
			$nodes->add($rootDepartment);
		}

		$createRoleItem = fn($data) => ($node = $nodes->getItemById($data['nodeId']))
			? $this->getNodeRoleByPrefix($node, $data['prefix'], $data['accessCode'])
			: null
		;

		return array_filter(array_map($createRoleItem, $accessCodeMap));
	}

	private function updateItemViewOptions(Item $item, Node $node): void
	{
		$icon = (int)$node->parentId === 0
			? self::IMAGE_COMPANY
			: self::IMAGE_DEPARTMENT
		;
		if ($node->isTeam())
		{
			$icon = self::IMAGE_TEAM;
		}

		$item->setAvatar($icon);
		$item->getNodeOptions()->set('avatar', $icon);
	}

	private function getTab(): Tab
	{
		return new Tab(
			[
				'id' => self::TAB_ID_ROLES,
				'title' => Loc::getMessage('HUMANRESOURCES_ENTITY_SELECTOR_ROLES_TAB_TITLE') ?? '',
				'itemMaxDepth' => 7,
				'icon' => [
					'default' => $this->getRoleTabIconInBase64(),
					'selected' => $this->getRoleTabSelectedIconInBase64(),
				],
			],
		);
	}

	private function getRoleTabIconInBase64(): string
	{
		return 'data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2221%22%20height%3D%2217%22%20viewBox%3D%220%200%2021%2017%22%20fill%3D%22none%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%3E%3Cpath%20fill-rule%3D%22evenodd%22%20clip-rule%3D%22evenodd%22%20d%3D%22M2.21523%2015.6207C2.33373%2016.089%202.75509%2016.417%203.23814%2016.417H17.299C17.8032%2016.417%2018.2369%2016.0603%2018.3343%2015.5656L20.0036%207.08371C20.191%206.13183%2019.2023%205.38045%2018.3353%205.81584L15.3199%207.33025C14.7499%207.61648%2014.0558%207.39199%2013.7614%206.82624L10.9854%201.49187C10.5493%200.653863%209.35009%200.654547%208.91495%201.49306L6.14886%206.82334C5.85462%207.39033%205.15912%207.61527%204.58858%207.32795L1.74639%205.89663C0.85334%205.4469%20-0.154668%206.25549%200.090627%207.22483L2.21523%2015.6207Z%22%20fill%3D%22%23ABB1B8%22/%3E%3C/svg%3E';
	}

	private function getRoleTabSelectedIconInBase64(): string
	{
		return str_replace('ABB1B8', 'fff', $this->getRoleTabIconInBase64());
	}

	private function getNodeRoles(Node $node): array
	{
		$nodeType = $node->isDepartment() ? NodeEntityType::DEPARTMENT : NodeEntityType::TEAM;
		$roleDefinitions = $this->getRoleDefinitions($nodeType);

		$roles = [];
		foreach ($roleDefinitions as $roleData)
		{
			$roles[] = $this->buildRoleItem(
				$node,
				$roleData['accessCode'],
				$roleData['messageKeyOnly'],
				$roleData['messageKeyWithName']
			);
		}

		return $roles;
	}

	private function getNodeRoleByPrefix(Node $node, string $prefix, string $accessCode): ?Item
	{
		$nodeType = $node->isDepartment() ? NodeEntityType::DEPARTMENT : NodeEntityType::TEAM;
		$roleDefinitions = $this->getRoleDefinitions($nodeType);

		foreach ($roleDefinitions as $roleData)
		{
			if ($roleData['accessCode'] === $prefix)
			{
				return $this->buildRoleItem(
					$node,
					$prefix,
					$roleData['messageKeyOnly'],
					$roleData['messageKeyWithName'],
					$accessCode,
					false,
					false,
				);
			}
		}

		return null;
	}

	private function getRoleDefinitions(NodeEntityType $entityType): array
	{
		return match ($entityType)
		{
			NodeEntityType::DEPARTMENT => [
				[
					'accessCode' => AccessCode::ACCESS_DIRECTOR,
					'messageKeyOnly' => 'HUMANRESOURCES_ENTITY_SELECTOR_ROLE_ONLY_DEPARTMENT_HEAD',
					'messageKeyWithName' => 'HUMANRESOURCES_ENTITY_SELECTOR_ROLE_ONLY_DEPARTMENT_HEAD_WITH_NAME',
				],
				[
					'accessCode' => AccessCode::ACCESS_DEPUTY,
					'messageKeyOnly' => 'HUMANRESOURCES_ENTITY_SELECTOR_ROLE_ONLY_DEPARTMENT_DEPUTY_HEAD',
					'messageKeyWithName' => 'HUMANRESOURCES_ENTITY_SELECTOR_ROLE_ONLY_DEPARTMENT_DEPUTY_HEAD_WITH_NAME',
				],
				[
					'accessCode' => AccessCode::ACCESS_EMPLOYEE,
					'messageKeyOnly' => 'HUMANRESOURCES_ENTITY_SELECTOR_ROLE_ONLY_DEPARTMENT_EMPLOYEES',
					'messageKeyWithName' => 'HUMANRESOURCES_ENTITY_SELECTOR_ROLE_ONLY_DEPARTMENT_EMPLOYEES_WITH_NAME',
				],
			],
			NodeEntityType::TEAM => [
				[
					'accessCode' => AccessCode::ACCESS_TEAM_DIRECTOR,
					'messageKeyOnly' => 'HUMANRESOURCES_ENTITY_SELECTOR_ROLE_ONLY_TEAM_HEAD',
					'messageKeyWithName' => 'HUMANRESOURCES_ENTITY_SELECTOR_ROLE_ONLY_TEAM_HEAD_WITH_NAME',
				],
				[
					'accessCode' => AccessCode::ACCESS_TEAM_DEPUTY,
					'messageKeyOnly' => 'HUMANRESOURCES_ENTITY_SELECTOR_ROLE_ONLY_TEAM_DEPUTY_HEAD',
					'messageKeyWithName' => 'HUMANRESOURCES_ENTITY_SELECTOR_ROLE_ONLY_TEAM_DEPUTY_HEAD_WITH_NAME',
				],
				[
					'accessCode' => AccessCode::ACCESS_TEAM_EMPLOYEE,
					'messageKeyOnly' => 'HUMANRESOURCES_ENTITY_SELECTOR_ROLE_ONLY_TEAM_EMPLOYEES',
					'messageKeyWithName' => 'HUMANRESOURCES_ENTITY_SELECTOR_ROLE_ONLY_TEAM_EMPLOYEES_WITH_NAME',
				],
			],
		};
	}

	private function buildRoleItem(
		Node $node,
		string $accessCodePrefix,
		string $messageKeyOnly,
		string $messageKeyWithName,
		?string $customId = null,
		?bool $searchable = true,
		?bool $availableInRecentTab = true,
	): Item
	{
		$tagAvatar = $node->isDepartment() ? self::IMAGE_ROLE_DEPARTMENT_OPTION_SELECTED : self::IMAGE_ROLE_TEAM_OPTION_SELECTED;
		$avatar = $node->isDepartment() ? self::IMAGE_ROLE_DEPARTMENT_OPTION : self::IMAGE_ROLE_TEAM_OPTION;
		$titleWithName = Loc::getMessage($messageKeyWithName, ['#NODE_NAME#' => $node->name]);
		$id = $customId ?? ($accessCodePrefix . $node->id);
		$titleOnly = Loc::getMessage($messageKeyOnly);

		return new Item([
			'id' => $id,
			'title' => $titleWithName,
			'entityId' => self::ENTITY_ID,
			'availableInRecentTab' => $searchable,
			'searchable' => $availableInRecentTab,
			'nodeOptions' => [
				'avatar' => $avatar,
				'title' => $titleOnly,
				'caption' => $node->name,
			],
			'tagOptions' => [
				'avatar' => $tagAvatar,
				'title' => $titleWithName,
				'maxWidth' => 600,
			],
		]);
	}
}
