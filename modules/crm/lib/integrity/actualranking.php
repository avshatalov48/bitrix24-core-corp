<?php
namespace Bitrix\Crm\Integrity;

use Bitrix\Crm\DealTable;
use Bitrix\Crm\LeadTable;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query;
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

	/** @var array List for rank */
	protected $list = [];

	/** @var callable[] Modifiers */
	protected $modifiers = [];

	/** @var array|null List after modifier. */
	protected $modifiedList = null;

	/** @var integer|null Top entity id in ranked list */
	protected $entityId;

	/** @var integer|null Deal id of top entity in ranked list */
	protected $dealId;

	/** @var int[] Order id list of top entity in ranked list */
	protected $orders = [];

	/** @var integer|null Lead(return customer type) id of top entity in ranked list. */
	protected $returnCustomerLeadId;

	protected function clearRuntime()
	{
		$this->list = [];
		$this->modifiedList = null;
		$this->entityTypeId = null;

		$this->entityId = null;
		$this->dealId = null;
		$this->orders = [];
		$this->returnCustomerLeadId = null;
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
		return $this->dealId;
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
	 * Get lead(return customer type) id of top entity in ranked list.
	 *
	 * @return integer|null
	 */
	public function getReturnCustomerLeadId()
	{
		return $this->returnCustomerLeadId;
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

		if (count($this->list) === 0 || !$isRankable)
		{
			return $this;
		}

		// filter or sort by custom modifiers
		$this->runModifiers();

		// filter by active status
		$this->filterByActiveStatus();

		if (count($this->list) === 0)
		{
			return $this;
		}

		// ranking by deals
		$findDealIdOnly = !$this->entityId ? false : true;
		$this->rankByDeals($findDealIdOnly);

		// ranking by orders
		$findOrdersOnly = !$this->entityId ? false : true;
		$this->rankByOrders($findOrdersOnly);

		// ranking by repeated leads
		$findLeadIdOnly = !$this->entityId ? false : true;
		$this->rankByReturnCustomerLeads($findLeadIdOnly);

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

	protected function rankByReturnCustomerLeads($findLeadIdOnly = false)
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
		$leadId = $this->rankByQuery($query, $findLeadIdOnly ? 1 : null);
		if (!$leadId)
		{
			return;
		}

		$this->isRanked = true;

		// set return customer lead id
		$this->returnCustomerLeadId = $leadId;
	}

	protected function rankByDeals($findDealIdOnly = false)
	{
		$query = DealTable::query();
		$query->addFilter('=STAGE_SEMANTIC_ID', array(
			PhaseSemantics::PROCESS
		));
		$dealId = $this->rankByQuery($query, $findDealIdOnly ? 1 : null);
		if (!$dealId)
		{
			return;
		}

		$this->isRanked = true;

		// set deal Id
		$this->dealId = $dealId;
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

	protected function rankByQuery(Query $query, $limit = null)
	{
		switch ($this->entityTypeId)
		{
			case \CCrmOwnerType::Contact:
				$fieldName = 'CONTACT_ID';
				break;
			case \CCrmOwnerType::Company:
				$fieldName = 'COMPANY_ID';
				break;
			default:
				return null;
		}

		$queryId = null;
		$rankedList = array();
		$query->setSelect(array($fieldName, 'MAX_ID'));
		$query->addFilter('=' . $fieldName, $this->list);
		$query->registerRuntimeField(new ExpressionField('MAX_DATE_MODIFY', 'MAX(%s)', 'DATE_MODIFY'));
		$query->registerRuntimeField(new ExpressionField('MAX_DATE_CREATE', 'MAX(%s)', 'DATE_CREATE'));
		$query->registerRuntimeField(new ExpressionField('MAX_ID', 'MAX(%s)', 'ID'));
		$query->setOrder(array(
			'MAX_DATE_MODIFY' => 'DESC',
			'MAX_DATE_CREATE' => 'DESC',
			'MAX_ID' => 'DESC',
		));
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
			return null;
		}

		// set entity id
		if (!$this->entityId)
		{
			$this->entityId = $rankedList[0];
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