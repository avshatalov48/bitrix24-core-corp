<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Builder\Structure;

use Bitrix\HumanResources\Builder\Structure\Filter\NodeFilter;
use Bitrix\HumanResources\Contract\Builder;
use Bitrix\HumanResources\Builder\Structure\Sort\NodeSort;
use Bitrix\HumanResources\Contract\Builder\Structure\SelectionCondition;
use Bitrix\HumanResources\Exception\NodeAccessFilterException;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Internals\Repository\Mapper\NodeMemberMapper;
use Bitrix\HumanResources\Item\Collection\NodeMemberCollection;
use Bitrix\HumanResources\Item\NodeMember;
use Bitrix\HumanResources\Item\Collection\RoleCollection;
use Bitrix\HumanResources\Model\NodeMemberTable;
use Bitrix\HumanResources\Contract\Repository\RoleRepository;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\StructureRole;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Main\Validation\Rule\ElementsType;
use InvalidArgumentException;

/**
 * @extends BaseDataBuilder<NodeMember, NodeMemberCollection>
 */
final class NodeMemberDataBuilder extends BaseDataBuilder
{
	private readonly NodeMemberMapper $mapper;
	private readonly RoleRepository $roleRepository;
	private readonly RoleCollection $roleCollection;

	protected array $select = [
		'ID',
		'ENTITY_TYPE',
		'ENTITY_ID',
		'NODE_ID',
		'ACTIVE',
		'ADDED_BY',
		'CREATED_AT',
		'UPDATED_AT',
		'ROLE',
	];

	#[ElementsType(className: StructureRole::class, errorMessage: 'Invalid structure role list')]
	private array $structureRoleList = [];
	private ?SelectionCondition $selectionCondition = null;

	public function __construct()
	{
		parent::__construct();

		$this->roleRepository = Container::instance()->getRoleRepository();
		$this->roleCollection = $this->roleRepository->list();
		$this->mapper = new NodeMemberMapper();
	}

	public static function createWithFilter(
		Builder\Filter $filter,
	): self
	{
		return (new self())->addFilter($filter);
	}

	public function addStructureRole(StructureRole $role): self
	{
		$this->structureRoleList[] = $role;

		return $this;
	}

	public function setStructureRoles(array $roles): self
	{
		$this->structureRoleList = $roles;

		$validateResult = $this->validationService->validate($this);
		if (!$validateResult->isSuccess())
		{
			throw new InvalidArgumentException(implode(', ', $validateResult->getErrorMessages()));
		}

		return $this;
	}

	public function setSelectionCondition(SelectionCondition $selectionCondition): self
	{
		$this->selectionCondition = $selectionCondition;

		return $this;
	}

    /**
     * @throws SystemException|WrongStructureItemException
	 */
	protected function getData(): NodeMemberCollection
	{
		try
		{
			return $this->applySelectionCondition(
				$this->getList(
					$this->prepareQuery(),
				),
			);
		}
		catch (NodeAccessFilterException)
		{
			return new NodeMemberCollection();
		}
	}

	private function applySelectionCondition(NodeMemberCollection $nodeMemberCollection): NodeMemberCollection
	{
		return $this->selectionCondition
			? $this->selectionCondition->apply($nodeMemberCollection)
			: $nodeMemberCollection;
	}

	/**
	 * @return int[]
	 */
	private function getStructureRoleIdList(): array
	{
		$result = [];
		/** @var StructureRole $structureRole */
		foreach ($this->structureRoleList as $structureRole)
		{
			$xmlId = $structureRole->getXmlId();
			$roleItem = $this->roleCollection->getItemByXmlId($xmlId);
			if ($roleItem === null)
			{
				continue;
			}

			if ($roleItem->id === null)
			{
				continue;
			}

			$result[] = $roleItem->id;
		}

		return $result;
	}

	/**
	 * @return Query
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws NodeAccessFilterException
	 */
	public function prepareQuery(): Query
	{
		$query = NodeMemberTable::query();

		if (!empty($this->select))
		{
			$query->setSelect($this->select);
		}

		if ($this->limit > 0)
		{
			$query->setLimit($this->limit);
		}

		if ($this->offset > 0)
		{
			$query->setOffset($this->offset);
		}

		if ($this->cacheTtl > 0)
		{
			$query->setCacheTtl($this->cacheTtl);
		}

		if ($this->sort !== null)
		{
			if ($this->sort instanceof NodeSort)
			{
				$this->sort->setCurrentAlias('NODE');
			}

			$query->setOrder($this->sort->prepareSort());
		}

		$conditionTree = new ConditionTree();
		$conditionTree->logic($this->logic);

		if (!empty($this->filters))
		{
			foreach ($this->filters as $filter)
			{
				if ($filter instanceof NodeFilter)
				{
					$filter->setCurrentAlias('NODE');
				}

				$conditionTree->addCondition($filter->prepareFilter());
			}
		}

		$roleList = $this->getStructureRoleIdList();

		if (!empty($roleList))
		{
			$query->whereIn('ROLE.ID', $roleList);
		}

		$query->where($conditionTree);

		return $query;
	}

	protected function validate(Builder\Filter $filter): bool
	{
		return true;
	}


	/**
	 * @param Query $query
	 *
	 * @return NodeMemberCollection
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws WrongStructureItemException
	 */
	protected function getList(Query $query): NodeMemberCollection
	{
		$result = $query->fetchAll();

		$nodeMemberCollection = new NodeMemberCollection();

		foreach ($result as $nodeMember)
		{
			$nodeMemberCollection->add($this->mapper->convertFromOrmArray($nodeMember));
		}

		return $nodeMemberCollection;
	}
}
