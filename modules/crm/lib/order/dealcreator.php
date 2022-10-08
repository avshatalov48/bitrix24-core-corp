<?php

namespace Bitrix\Crm\Order;

use Bitrix\Main;
use Bitrix\Crm;

class DealCreator
{
	/** @var Order|null $order */
	private $order = null;

	public function __construct(Order $order)
	{
		$this->order = $order;
	}

	/**
	 * Create deal.
	 *
	 * Creates a deal without products, for creating products rows use method `addProductsToDeal`.
	 *
	 * @return int
	 */
	public function create()
	{
		$selector = $this->getActualEntitySelector();

		$facility = new Crm\EntityManageFacility($selector);
		$facility->setDirection(Crm\EntityManageFacility::DIRECTION_OUTGOING);

		$fields = $this->getDealFieldsOnCreate();
		$dealId = (int)$facility->registerDeal($fields);

		return $dealId;
	}

	/**
	 * @return Crm\Integrity\ActualEntitySelector
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\SystemException
	 */
	protected function getActualEntitySelector()
	{
		$selector = new Crm\Integrity\ActualEntitySelector();

		$contactCompanyCollection = $this->order->getContactCompanyCollection();

		foreach($contactCompanyCollection as $item)
		{
			$selector->setEntity($item->getEntityType(), $item->getField('ENTITY_ID'));
		}

		$selector->setEntity(\CCrmOwnerType::Order, $this->order->getId());

		return $selector;
	}

	/**
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\SystemException
	 */
	protected function getDealFieldsOnCreate() : array
	{
		$contactIds = [];

		$companyId = null;
		$contactId = null;

		$company = $this->order->getContactCompanyCollection()->getPrimaryCompany();
		if ($company)
		{
			$companyId = $company->getField('ENTITY_ID');
		}

		foreach ($this->order->getContactCompanyCollection()->getContacts() as $contact)
		{
			if ($contact->isPrimary())
			{
				$contactId = $contact->getField('ENTITY_ID');
			}

			$contactIds[] = $contact->getField('ENTITY_ID');
		}

		return [
			'TITLE' => Main\Localization\Loc::getMessage(
				'CRM_ORDER_DEAL_CREATOR_TITLE_DEAL',
				[
					'#ORDER_ID#' => $this->order->getId()
				]
			),
			'OPPORTUNITY' => $this->order->getPrice(),
			'CURRENCY_ID' => $this->order->getCurrency(),
			'ASSIGNED_BY_ID' => $this->order->getField('RESPONSIBLE_ID'),
			'CREATED_BY_ID' => $this->order->getField('RESPONSIBLE_ID'),
			'CONTACT_IDS' => $contactIds,
			'CONTACT_ID' => $contactId,
			'COMPANY_ID' => $companyId,
			'ORDER_ID' => $this->order->getId(),
		];
	}

	/**
	 * Adds the order basket items to the deal as the product rows.
	 *
	 * If you need to sync a deal and an order, maybe you need `OrderDealSynchronizer`?
	 * @see \Bitrix\Crm\Order\OrderDealSynchronizer
	 *
	 * @param mixed $dealId
	 *
	 * @return void
	 */
	public function addProductsToDeal($dealId)
	{
		Manager::copyOrderProductsToDeal($this->order, $dealId);
	}
}
