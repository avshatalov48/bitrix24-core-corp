<?php
namespace Bitrix\Crm\Integrity;

use Bitrix\Crm;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\LeadTable;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\ORM;
use Bitrix\Crm\Binding;
use Bitrix\Crm\Order;

/**
 * Class ActualRanking
 * @package Bitrix\Crm\Integrity
 */
class ActualRanking
{
	/** @var integer Limit parameter for Query */
	protected $queryLimit = 50;

	/** @var bool Is list ranked */
	protected $isRanked = false;

	/** @var integer|null Entity type id */
	protected $entityTypeId;

	/** @var integer|null Dynamic type id */
	protected $dynamicTypeId;

	/** @var array List for rank */
	protected $list = [];

	/** @var callable[] Modifiers */
	protected $modifiers = [];

	/** @var array|null List after modifier. */
	protected $modifiedList = null;

	/** @var integer|null Top entity id in ranked list */
	protected $entityId;

	/** @var int[] Deal id of top entity in ranked list */
	protected $deals = [];

	/** @var int[] Dynamics id of top entity in ranked list. */
	protected $dynamics;

	/** @var int[] Order id list of top entity in ranked list */
	protected $orders = [];

	/** @var int[] Lead(return customer type) id of top entity in ranked list. */
	protected $leads;

	protected function clearRuntime()
	{
		$this->list = [];
		$this->modifiedList = null;
		$this->entityTypeId = null;

		$this->entityId = null;
		$this->deals = [];
		$this->orders = [];
		$this->dynamics = [];
		$this->leads = [];
	}

	/**
	 * Set dynamic type ID.
	 *
	 * @param int|null Dynamic type ID.
	 * @return $this
	 */
	public function setDynamicTypeId($dynamicTypeId)
	{
		$this->dynamicTypeId = $dynamicTypeId;

		return $this;
	}

	/**
	 * Set custom modifiers.
	 *
	 * @param callable[] $modifiers Modifiers.
	 * @return $this
	 */
	public function setModifiers(array $modifiers)
	{
		$this->modifiers = [];
		foreach ($modifiers as $modifier)
		{
			$this->addModifier($modifier);
		}

		return $this;
	}

	/**
	 * Add custom modifier.
	 *
	 * @param callable $modifier Modifier.
	 * @return $this
	 */
	public function addModifier($modifier)
	{
		if (is_callable($modifier))
		{
			$this->modifiers[] = $modifier;
		}

		return $this;
	}

	/**
	 * Get modified list.
	 *
	 * @return array|null
	 */
	public function getModifiedList()
	{
		return $this->modifiedList;
	}

	/**
	 * Set modified list.
	 *
	 * @param array|null $modifiedList Modified list.
	 */
	public function setModifiedList(array $modifiedList)
	{
		$this->modifiedList = $modifiedList;
	}

	/**
	 * Get top entity id in ranked list.
	 *
	 * @return integer|null
	 */
	public function getEntityId()
	{
		return $this->entityId;
	}

	/**
	 * Get top entity type id.
	 *
	 * @return integer|null
	 */
	public function getEntityTypeId()
	{
		return $this->entityTypeId;
	}

	/**
	 * Get deal of top entity in ranked list.
	 *
	 * @return integer|null
	 */
	public function getDealId()
	{
		return empty($this->deals) ? null : $this->deals[0];
	}

	/**
	 * Get dynamic entity of top entity in ranked list.
	 *
	 * @return integer|null
	 */
	public function getDynamicId()
	{
		return $this->dynamics[0] ?? null;
	}

	/**
	 * Get orders of top entity in ranked list.
	 *
	 * @return int[]
	 */
	public function getOrders()
	{
		return $this->orders;
	}

	/**
	 * Get deals of top entity in ranked list.
	 *
	 * @return int[]
	 */
	public function getDeals()
	{
		return $this->deals;
	}

	/**
	 * Get dynamics of top entity in ranked list.
	 *
	 * @return int[]
	 */
	public function getDynamics()
	{
		return $this->dynamics;
	}

	/**
	 * Get leads of top entity in ranked list.
	 *
	 * @return int[]
	 */
	public function getLeads()
	{
		return $this->leads;
	}

	/**
	 * Get lead(return customer type) id of top entity in ranked list.
	 *
	 * @return int|null
	 */
	public function getReturnCustomerLeadId()
	{
		return empty($this->returnCustomerLeadId) ? null : $this->returnCustomerLeadId[0];
	}

	/**
	 * Get ranked list of entity ids.
	 *
	 * @return array
	 */
	public function getRankedList()
	{
		return $this->list;
	}

	/**
	 * Set ranked list of entity ids.
	 *
	 * @param array $list List.
	 * @return $this
	 */
	public function setRankedList(array $list)
	{
		$this->list = array_values($list);
		return $this;
	}

	/**
	 * Return true if list is ranked.
	 *
	 * @return bool
	 */
	public function isRanked()
	{
		return $this->isRanked;
	}


	/**
	 * Rank entity list.
	 *
	 * @param integer $entityTypeId Entity type id.
	 * @param array $list List of entity ids.
	 * @param bool $isRankable Is rankable.
	 * @return $this
	 */
	public function rank($entityTypeId, array $list, $isRankable = true)
	{
		$this->clearRuntime();
		$this->entityTypeId = $entityTypeId;
		$this->setRankedList($list);

		if (count($this->list) === 0)
		{
			return $this;
		}

		if ($isRankable)
		{
			// filter or sort by custom modifiers
			$this->runModifiers();

			// filter by active status
			$this->filterByActiveStatus();

			if (count($this->list) === 0)
			{
				return $this;
			}
		}
		else
		{
			$this->entityId = $this->list[0];
			return $this;
		}

		// ranking by dynamics
		$this->rankByDynamics();

		// ranking by deals
		$findDealsOnly = !$this->entityId ? false : true;
		$this->rankByDeals($findDealsOnly);

		// ranking by orders
		$findOrdersOnly = !$this->entityId ? false : true;
		$this->rankByOrders($findOrdersOnly);

		// ranking by repeated leads
		$findLeadsOnly = !$this->entityId ? false : true;
		$this->rankByLeads($findLeadsOnly);

		// other ranking
		// ...

		// default ranking
		if (!$this->entityId)
		{
			$this->rankByDefaults();
		}

		return $this;
	}

	protected function rankByDefaults()
	{
		if (!$this->list[0])
		{
			return;
		}

		$this->isRanked = true;
		$this->entityId = $this->list[0];
	}

	protected function filterByActiveStatus()
	{
		switch ($this->entityTypeId)
		{
			case \CCrmOwnerType::Lead:
				$query = LeadTable::query();
				break;

			default:
				return;
		}

		$rankedList = array();
		$query->addFilter('=STATUS_SEMANTIC_ID', array(
			PhaseSemantics::PROCESS
		));
		$query->addFilter('=ID', $this->list);
		$query->setSelect(array('ID'));
		$query->setOrder(array(
			'DATE_MODIFY' => 'DESC',
			'DATE_CREATE' => 'DESC',
			'ID' => 'DESC',
		));
		$listDb = $query->exec();
		while ($item = $listDb->fetch())
		{
			$rankedList[] = $item['ID'];
		}

		$this->list = $rankedList;
	}

	protected function rankByLeads($findLeadsOnly = false)
	{
		if ($this->isRanked)
		{
			return;
		}

		$query = LeadTable::query();
		$query->addFilter('=STATUS_SEMANTIC_ID', array(
			PhaseSemantics::PROCESS
		));
		$query->addFilter('=IS_RETURN_CUSTOMER', 'Y');
		$leads = $this->rankByQuery($query, $findLeadsOnly ? 1 : null);
		if (empty($leads))
		{
			return;
		}

		$this->isRanked = true;

		// set return customer lead id
		$this->leads = $leads;
	}

	protected function rankByDeals($findDealsOnly = false)
	{
		$query = DealTable::query();
		$query->addFilter('=STAGE_SEMANTIC_ID', array(
			PhaseSemantics::PROCESS
		));
		$query->addFilter('=IS_RECURRING', 'N');
		$deals = $this->rankByQuery($query, $findDealsOnly ? 1 : null);
		if (empty($deals))
		{
			return;
		}

		$this->isRanked = true;

		// set deal Id
		$this->deals = $deals;
	}

	protected function rankByDynamics($findDynamicsOnly = false)
	{
		if (!$this->dynamicTypeId)
		{
			return;
		}

		if (!\CCrmOwnerType::isUseDynamicTypeBasedApproach($this->dynamicTypeId))
		{
			return;
		}

		$factory = Crm\Service\Container::getInstance()->getFactory($this->dynamicTypeId);
		if (!$factory)
		{
			return;
		}


		$query = $factory->getDataClass()::query();
		switch ($this->entityTypeId)
		{
			case \CCrmOwnerType::Contact:
				$fieldName = 'CONTACT_ID';
				break;
			case \CCrmOwnerType::Company:
				$fieldName = 'COMPANY_ID';
				break;
			default:
				return;
		}
		$query
			->setSelect(['ID', $fieldName])
			->addFilter("=$fieldName", $this->entityId ?: $this->list)
			->addFilter('!=STAGE.SEMANTICS', Crm\PhaseSemantics::getFinalSemantis());

		$query->registerRuntimeField(new ORM\Fields\ExpressionField('MAX_ID', 'MAX(%s)', 'ID'));
		$query->registerRuntimeField(new ORM\Fields\ExpressionField('MAX_DATE_MODIFY', 'MAX(%s)', 'UPDATED_TIME'));
		$query->registerRuntimeField(new ORM\Fields\ExpressionField('MAX_DATE_CREATE', 'MAX(%s)', 'CREATED_TIME'));
		$query->setOrder(array(
			'MAX_DATE_MODIFY' => 'DESC',
			'MAX_DATE_CREATE' => 'DESC',
			'MAX_ID' => 'DESC',
		));

		$topEntityId = null;
		$rankedList = [];
		foreach ($query->fetchAll() as $item)
		{
			if (!$topEntityId)
			{
				$topEntityId = $item[$fieldName];
			}

			if ($topEntityId == $item[$fieldName] && !in_array($item['ID'], $this->dynamics))
			{
				// find all, even from ::setEntity
				$this->dynamics[] = $item['ID'];
			}

			if (!in_array($item[$fieldName], $rankedList))
			{
				$rankedList[] = $item[$fieldName];
			}
		}

		if (empty($rankedList))
		{
			return;
		}

		$this->isRanked = true;

		// set entity id
		if (!$this->entityId)
		{
			$this->entityId = $rankedList[0];
		}

		if ($findDynamicsOnly)
		{
			return;
		}

		$this->updateListByRankedList($rankedList);
	}

	protected function rankByOrders($findOrdersOnly = false)
	{
		if (!in_array($this->entityTypeId, [\CCrmOwnerType::Contact, \CCrmOwnerType::Company]))
		{
			return;
		}

		$topEntityId = null;
		$rankedList = [];
		$list = Binding\OrderContactCompanyTable::getList([
			'select' => ['ORDER_ID', 'ENTITY_ID'],
			'filter' => [
				'=IS_PRIMARY' => 'Y',
				'=ENTITY_TYPE_ID' => $this->entityTypeId,
				'=ENTITY_ID' => $this->entityId ?: $this->list,
				'=ORDER.STATUS_ID' => Order\OrderStatus::getSemanticProcessStatuses(),
			],
			'order' => [
				'ORDER.DATE_UPDATE' => 'DESC',
				'ORDER.DATE_INSERT' => 'DESC',
				'ORDER.ID' => 'DESC',
			]
		]);
		foreach ($list as $item)
		{
			if (!$topEntityId)
			{
				$topEntityId = $item['ENTITY_ID'];
			}

			if ($topEntityId == $item['ENTITY_ID'] && !in_array($item['ORDER_ID'], $this->orders))
			{
				// find all, even from ::setEntity
				$this->orders[] = $item['ORDER_ID'];
			}

			if (!in_array($item['ENTITY_ID'], $rankedList))
			{
				$rankedList[] = $item['ENTITY_ID'];
			}
		}

		if (empty($rankedList))
		{
			return;
		}

		$this->isRanked = true;

		// set entity id
		if (!$this->entityId)
		{
			$this->entityId = $rankedList[0];
		}

		if ($findOrdersOnly)
		{
			return;
		}

		$this->updateListByRankedList($rankedList);
	}

	protected function rankByQuery(ORM\Query\Query $query, $limit = null)
	{
		switch ($this->entityTypeId)
		{
			case \CCrmOwnerType::Contact:
				$fieldName = 'BINDING_CONTACT_ID';
				$filterFieldName = 'BINDING_CONTACT.CONTACT_ID';
				break;
			case \CCrmOwnerType::Company:
				$fieldName = $filterFieldName = 'COMPANY_ID';
				break;
			default:
				return null;
		}

		$rankingEntitiesQuery = clone $query;

		$queryId = null;
		$rankedList = array();
		$query->setSelect(array($fieldName => $filterFieldName, 'MAX_ID'));
		$query->whereIn($filterFieldName, $this->list);
		$query->registerRuntimeField(new ORM\Fields\ExpressionField('MAX_ID', 'MAX(%s)', 'ID'));
		if ($limit !== 1)
		{
			$query->registerRuntimeField(new ORM\Fields\ExpressionField('MAX_DATE_MODIFY', 'MAX(%s)', 'DATE_MODIFY'));
			$query->registerRuntimeField(new ORM\Fields\ExpressionField('MAX_DATE_CREATE', 'MAX(%s)', 'DATE_CREATE'));
			$query->setOrder(array(
				'MAX_DATE_MODIFY' => 'DESC',
				'MAX_DATE_CREATE' => 'DESC',
				'MAX_ID' => 'DESC',
			));
		}
		else
		{
			$query->setOrder(array(
				'MAX_ID' => 'DESC',
			));
		}

		if ($limit)
		{
			$query->setLimit($limit);
		}

		$listDb = $query->exec();
		while ($item = $listDb->fetch())
		{
			$rankedList[] = $item[$fieldName];
			if (!$queryId)
			{
				$queryId = $item['MAX_ID'];
			}
		}

		$isRanked = $queryId > 0;
		if (!$isRanked)
		{
			return [];
		}

		// set entity id
		if (!$this->entityId)
		{
			$this->entityId = $rankedList[0];
		}

		$queryId = [$queryId];
		if (!$limit && $this->entityId && !empty($queryId))
		{
			// find only if its not from ::setEntity

			$rankingEntitiesResult = $rankingEntitiesQuery
				->setSelect(['ID'])
				->where($filterFieldName, $this->entityId)
				->setOrder([
					'DATE_MODIFY' => 'DESC',
					'DATE_CREATE' => 'DESC',
					'ID' => 'DESC',
				])
				->exec()
				->fetchAll();
			if (count($rankingEntitiesResult) > 0)
			{
				$queryId = array_column($rankingEntitiesResult, 'ID');
			}
		}

		if ($limit)
		{
			return $queryId;
		}

		$this->updateListByRankedList($rankedList);
		return $queryId;
	}

	protected function updateListByRankedList(array $rankedList)
	{
		foreach ($this->list as $entityId)
		{
			if (in_array($entityId, $rankedList))
			{
				continue;
			}

			$rankedList[] = $entityId;
		}

		$this->list = $rankedList;
	}

	protected function runModifiers()
	{
		foreach ($this->modifiers as $modifier)
		{
			if (!is_callable($modifier))
			{
				continue;
			}

			call_user_func_array($modifier, [$this]);
		}
	}
}