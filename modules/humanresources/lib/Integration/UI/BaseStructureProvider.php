<?php

namespace Bitrix\HumanResources\Integration\UI;


use Bitrix\HumanResources\Builder\Structure\Filter\Column\IdFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\Column\Node\NodeTypeFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\NodeFilter;
use Bitrix\HumanResources\Builder\Structure\NodeDataBuilder;
use Bitrix\HumanResources\Builder\Structure\Sort\NodeSort;
use Bitrix\HumanResources\Contract\Repository\NodeRepository;
use Bitrix\HumanResources\Enum\DepthLevel;
use Bitrix\HumanResources\Enum\Direction;
use Bitrix\HumanResources\Enum\NodeActiveFilter;
use Bitrix\HumanResources\Enum\SortDirection;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Internals\Entity\Provider\UI\BaseProviderOptions;
use Bitrix\HumanResources\Item\Collection\NodeCollection;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Util\StructureHelper;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Socialnetwork\Integration\UI\EntitySelector\UserProvider;
use Bitrix\UI\EntitySelector\BaseProvider;

abstract class BaseStructureProvider extends BaseProvider
{
	protected const IMAGE_DEPARTMENT = '/bitrix/js/humanresources/entity-selector/src/images/department.svg';
	protected const IMAGE_COMPANY = '/bitrix/js/humanresources/entity-selector/src/images/company.svg';
	protected const IMAGE_TEAM = '/bitrix/js/humanresources/entity-selector/src/images/team.svg';
	protected BaseProviderOptions $providerOptions;
	private NodeRepository $nodeRepository;
	private int $limit = 100;

	/**
	 * @throws SystemException
	 */
	public function __construct(array $options = [])
	{
		parent::__construct();

		$this->providerOptions = $this->initProviderOptions($options);

		$this->nodeRepository = $this->providerOptions->restricted && $this->providerOptions->structureAction
			? Container::getPermissionRestrictedNodeRepository($this->providerOptions->structureAction)
			: Container::getNodeRepository()
		;
	}

	abstract protected function initProviderOptions(array $options = []): BaseProviderOptions;

	/**
	 * @throws LoaderException
	 */
	public function isAvailable(): bool
	{
		return
			$this->providerOptions->isProviderActive
			&& (int)CurrentUser::get()->getId() > 0
			&& Loader::includeModule('socialnetwork')
			&& UserProvider::isIntranetUser()
		;
	}

	/**
	 * @throws WrongStructureItemException|SystemException|ArgumentException|ObjectPropertyException
	 */
	public function getItems(array $ids): array
	{
		return $this->getDepartments($ids);
	}

	/**
	 * @throws WrongStructureItemException|SystemException|ArgumentException|ObjectPropertyException
	 */
	public function getSelectedItems(array $ids): array
	{
		return $this->getDepartments($ids, true);
	}

	protected function getLimit(): int
	{
		return $this->limit;
	}

	/**
	 * @throws ArgumentException|SystemException|WrongStructureItemException|ObjectPropertyException
	 */
	protected function getRootDepartment(): ?Node
	{
		static $rootDepartment = null;

		if ($rootDepartment === null)
		{
			$structure = StructureHelper::getDefaultStructure();
			$rootDepartment = $this->nodeRepository->getRootNodeByStructureId($structure?->id);
		}

		return $rootDepartment;
	}

	/**
	 * @param array{
	 *     limit?: int|null,
	 *     active?: NodeActiveFilter,
	 *     searchQuery?: string|null,
	 *     parentId?: int|null,
	 *     depthLevel?: int|null,
	 *     nodeTypes?: list<NodeEntityType>|null
	 * } $options
	 */
	protected function getStructure(array $options = []): NodeCollection
	{
		$structure = StructureHelper::getDefaultStructure();
		if ($structure?->id === null)
		{
			return new NodeCollection();
		}

		$searchQuery = isset($options['searchQuery']) && is_string($options['searchQuery'])
			? $options['searchQuery']
			: null
		;

		$depthLevel =
			isset($options['depthLevel'])
			&& (is_int($options['depthLevel']) || $options['depthLevel'] instanceof DepthLevel)
				? $options['depthLevel']
				: DepthLevel::FULL
		;

		if (isset($options['parentId']))
		{
			$idFilter = IdFilter::fromId($options['parentId']);
			if (!isset($options['depthLevel']))
			{
				$depthLevel = DepthLevel::WITHOUT_PARENT;
			}
		}

		$limit = $options['limit'] ?? $this->getLimit();
		$nodeTypes = $options['nodeTypes'] ?? [];

		return NodeDataBuilder::createWithFilter(
			new NodeFilter(
				idFilter: $idFilter ?? null,
				entityTypeFilter: NodeTypeFilter::fromNodeTypes($nodeTypes),
				structureId: $structure?->id,
				direction: Direction::CHILD,
				depthLevel: $depthLevel,
				accessFilter: $this->providerOptions->accessFilter,
				name: $searchQuery,
			)
		)
			->setSort(new NodeSort(depth: SortDirection::Asc, type: SortDirection::Asc))
			->setLimit($limit)
			->getAll()
			->orderMapByInclude()
		;
	}

	protected function existAnyChildrenForNodes(NodeCollection $fetchedNodes): bool
	{
		$bottomNodesIds = $fetchedNodes->getBottomNodes()->getIds();
		if (empty($bottomNodesIds))
		{
			return false;
		}

		$childNodeByIds
			= NodeDataBuilder::createWithFilter(
			new NodeFilter(
				idFilter: idFilter::fromIds($bottomNodesIds),
				direction: Direction::CHILD,
				depthLevel: DepthLevel::FIRST,
			)
		)
			->get()
		;

		return !is_null($childNodeByIds);
	}
}