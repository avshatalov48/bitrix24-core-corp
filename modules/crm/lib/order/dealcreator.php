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

	public function create()
	{
		$selector = $this->getActualEntitySelector();

		$facility = new Crm\EntityManageFacility($selector);
		$facility->setDirection(Crm\EntityManageFacility::DIRECTION_OUTGOING);

		$fields = $this->getDealFieldsOnCreate();
		$dealId = (int)$facility->registerDeal($fields);
		if ($dealId > 0)
		{
			$this->addProductsToDeal($dealId);
		}

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

	protected function addProductsToDeal($dealId)
	{
		$result = [];

		$sort = 0;

		/** @var BasketItem $basketItem */
		foreach ($this->order->getBasket() as $basketItem)
		{
			$item = [
				'PRODUCT_ID' => $basketItem->getField('PRODUCT_ID'),
				'PRODUCT_NAME' => $basketItem->getField('NAME'),
				'PRICE' => $basketItem->getBasePrice(),
				'PRICE_ACCOUNT' => $basketItem->getBasePrice(),
				'PRICE_EXCLUSIVE' => $basketItem->getBasePrice(),
				'PRICE_NETTO' => $basketItem->getBasePrice(),
				'PRICE_BRUTTO' => $basketItem->getBasePrice(),
				'QUANTITY' => $basketItem->getQuantity(),
				'MEASURE_CODE' => $basketItem->getField('MEASURE_CODE'),
				'MEASURE_NAME' => $basketItem->getField('MEASURE_NAME'),
				'TAX_RATE' => $basketItem->getVatRate(),
				'DISCOUNT_SUM' => 0,
				'TAX_INCLUDED' => $basketItem->isVatInPrice() ? 'Y' : 'N',
				'SORT' => $sort,
			];

			if ($basketItem->getDiscountPrice() > 0)
			{
				$item['DISCOUNT_TYPE_ID'] = \Bitrix\Crm\Discount::MONETARY;
				$item['DISCOUNT_SUM'] = $basketItem->getDiscountPrice();
			}

			$item['PRICE'] -= $item['DISCOUNT_SUM'];
			$item['PRICE_ACCOUNT'] -= $item['DISCOUNT_SUM'];
			$item['PRICE_EXCLUSIVE'] -= $item['DISCOUNT_SUM'];

			$result[] = $item;

			$sort += 10;
		}


		if ($result)
		{
			\CCrmDeal::SaveProductRows($dealId, $result);
		}
	}

}