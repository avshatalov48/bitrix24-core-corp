<?php
namespace Bitrix\Crm\Activity;

use Bitrix\Crm\Integrity\ActualEntitySelector;
use Bitrix\Main\ArgumentException;

/**
 * Class BindingSelector
 * @package Bitrix\Crm\Activity
 */
class BindingSelector
{
	/** @var array Binding list */
	protected $bindings = array();

	/** @var ActualEntitySelector $entitySelector */
	protected $entitySelector;


	/**
	 * Create instance of class.
	 *
	 * @param array $fields Entity fields
	 * @param array $searchParameters Search parameters for searching duplicates
	 * @return static
	 * @throws ArgumentException
	 */
	public static function create(array $fields, array $searchParameters)
	{
		$selector = ActualEntitySelector::create($fields, $searchParameters);
		return new static($selector);
	}

	/**
	 * Sort bindings.
	 * First - deal, second - lead, next - other.
	 *
	 * @param array $bindings Binding list
	 * @return array
	 */
	public static function sortBindings(array $bindings)
	{
		$list = [];
		$uniqueList = [];

		$multipleTypes = array(\CCrmOwnerType::Deal, \CCrmOwnerType::Lead, \CCrmOwnerType::Order);
		foreach ($bindings as $binding)
		{
			$ownerTypeId = $binding['OWNER_TYPE_ID'];
			if (!isset($uniqueList[$ownerTypeId]) || !in_array($ownerTypeId, $multipleTypes, true))
			{
				$uniqueList[$ownerTypeId] = [];
			}

			$uniqueList[$ownerTypeId][] = $binding;
		}

		// unique
		foreach ($uniqueList as $ownerTypeId => $bindingList)
		{
			$uniqueList[$ownerTypeId] = array_map(
				function ($ownerId) use ($ownerTypeId)
				{
					return [
						'OWNER_TYPE_ID' => $ownerTypeId,
						'OWNER_ID' => $ownerId,
					];
				},
				array_unique(array_column($bindingList, 'OWNER_ID'))
			);
		}

		// keep order by type
		$orderByTypeId = [\CCrmOwnerType::Deal, \CCrmOwnerType::Lead];
		foreach ($orderByTypeId as $typeId)
		{
			if (!isset($uniqueList[$typeId]))
			{
				continue;
			}

			$list = array_merge($list, $uniqueList[$typeId]);
			unset($uniqueList[$typeId]);
		}

		ksort($uniqueList);
		foreach ($uniqueList as $bindingList)
		{
			$list = array_merge($list, $bindingList);
		}

		return $list;
	}


	/**
	 * Constructor.
	 *
	 * @param ActualEntitySelector $selector Actual entity selector.
	 */
	public function __construct(ActualEntitySelector $selector = null)
	{
		if ($selector)
		{
			$this->setEntitySelector($selector);
		}
	}

	/**
	 * Return true if bindings are found.
	 *
	 * @return bool
	 */
	public function hasBindings()
	{
		return count($this->bindings) > 0;
	}

	/**
	 * Get binding entity id by entity type id.
	 *
	 * @param integer $entityTypeId Entity type id
	 * @return integer|null
	 */
	public function getBindingEntityId($entityTypeId)
	{
		foreach ($this->bindings as $binding)
		{
			if ($binding['OWNER_TYPE_ID'] == $entityTypeId)
			{
				return $binding['OWNER_ID'];
			}
		}

		return null;
	}

	/**
	 * Get ordered bindings.
	 *
	 * @return array
	 */
	public function getBindings()
	{
		return $this->bindings;
	}

	/**
	 * Get actual entity selector.
	 *
	 * @return ActualEntitySelector
	 */
	public function getEntitySelector()
	{
		return $this->entitySelector;
	}

	/**
	 * Set actual entity selector.
	 *
	 * @param ActualEntitySelector $entitySelector
	 */
	protected function setEntitySelector(ActualEntitySelector $entitySelector)
	{
		$this->entitySelector = $entitySelector;
		$this->bindings = self::findBindings($this->entitySelector);
	}

	/**
	 * Return list of bindings by ActualEntitySelector.
	 *
	 * @param ActualEntitySelector $selector Entity Selector
	 * @return array
	 */
	public static function findBindings(ActualEntitySelector $selector)
	{
		$list = array();
		$isDealAdded = false;
		$isOrdersAdded = false;
		$isReturnCustomerLeadAdded = false;

		/**
		 * append company
		 */
		if ($selector->getCompanyId())
		{
			if ($selector->getCompanyDealId())
			{
				foreach ($selector->getCompanyDeals() as $dealId)
				{
					$list[] = array(
						'OWNER_TYPE_ID' => \CCrmOwnerType::Deal,
						'OWNER_ID' => $dealId
					);
				}

				$isDealAdded = true;
			}
			elseif ($selector->getCompanyReturnCustomerLeadId())
			{
				foreach ($selector->getCompanyReturnCustomerLeads() as $leadId)
				{
					$list[] = array(
						'OWNER_TYPE_ID' => \CCrmOwnerType::Lead,
						'OWNER_ID' => $leadId
					);
				}

				$isReturnCustomerLeadAdded = true;
			}

			if (!empty($selector->getCompanyOrders()))
			{
				foreach ($selector->getCompanyOrders() as $orderId)
				{
					$list[] = array(
						'OWNER_TYPE_ID' => \CCrmOwnerType::Order,
						'OWNER_ID' => $orderId
					);
				}

				$isOrdersAdded = true;
			}

			$list[] = array(
				'OWNER_TYPE_ID' => \CCrmOwnerType::Company,
				'OWNER_ID' => $selector->getCompanyId()
			);
		}

		/**
		 * append contact
		 */
		if (!$selector->getCompanyId())
		{
			// if no company
			$isContactNeedAdd = true;
		}
		else if ($selector->getCompanyId() === $selector->getContactCompanyId())
		{
			// if company found and it is related with contact
			$isContactNeedAdd = true;
		}
		else
		{
			$isContactNeedAdd = false;
		}

		if ($selector->getContactId() && $isContactNeedAdd)
		{
			if ($selector->getContactDealId() && !$isDealAdded)
			{
				foreach ($selector->getContactDeals() as $dealId)
				{
					$list[] = array(
						'OWNER_TYPE_ID' => \CCrmOwnerType::Deal,
						'OWNER_ID' => $dealId
					);
				}

				$isDealAdded = true;
			}

			if ($selector->getContactReturnCustomerLeadId() && !$isReturnCustomerLeadAdded && !$isDealAdded)
			{
				foreach ($selector->getContactReturnCustomerLeads() as $leadId)
				{
					$list[] = array(
						'OWNER_TYPE_ID' => \CCrmOwnerType::Lead,
						'OWNER_ID' => $leadId
					);
				}

				$isReturnCustomerLeadAdded = true;
			}

			if (!empty($selector->getContactOrders()) && !$isOrdersAdded)
			{
				foreach ($selector->getContactOrders() as $orderId)
				{
					$list[] = array(
						'OWNER_TYPE_ID' => \CCrmOwnerType::Order,
						'OWNER_ID' => $orderId
					);
				}

				$isOrdersAdded = true;
			}

			$list[] = array(
				'OWNER_TYPE_ID' => \CCrmOwnerType::Contact,
				'OWNER_ID' => $selector->getContactId()
			);
		}


		/**
		 * append lead
		 */
		if (empty($list))
		{
			if ($selector->getLeadId())
			{
				$list[] = array(
					'OWNER_TYPE_ID' => \CCrmOwnerType::Lead,
					'OWNER_ID' => $selector->getLeadId()
				);
			}
		}

		// append deal if it was set by setEntity
		if (!$isDealAdded && $selector->getDealId())
		{
			foreach ($selector->getDeals() as $dealId)
			{
				$list[] = array(
					'OWNER_TYPE_ID' => \CCrmOwnerType::Deal,
					'OWNER_ID' => $dealId
				);
			}

			$isDealAdded = true;
		}

		// append rc-lead if it was set by setEntity
		if (!$isReturnCustomerLeadAdded && !$isDealAdded && $selector->getReturnCustomerLeadId())
		{
			foreach ($selector->getReturnCustomerLeads() as $leadId)
			{
				$list[] = array(
					'OWNER_TYPE_ID' => \CCrmOwnerType::Lead,
					'OWNER_ID' => $leadId
				);
			}
		}

		// append orders if it was set by setEntity
		if (!$isOrdersAdded && !empty($selector->getOrders()))
		{
			foreach ($selector->getOrders() as $orderId)
			{
				$list[] = array(
					'OWNER_TYPE_ID' => \CCrmOwnerType::Order,
					'OWNER_ID' => $orderId
				);
			}
		}

		return self::sortBindings($list);
	}
}