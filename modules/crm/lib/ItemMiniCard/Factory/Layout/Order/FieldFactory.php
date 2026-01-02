<?php

namespace Bitrix\Crm\ItemMiniCard\Factory\Layout\Order;

use Bitrix\Crm\ItemMiniCard\Builder\Layout\ClientBuilder;
use Bitrix\Crm\ItemMiniCard\Layout\Field\ClientField;
use Bitrix\Crm\ItemMiniCard\Layout\Field\CommonField;
use Bitrix\Crm\ItemMiniCard\Layout\Field\MoneyField;
use Bitrix\Crm\ItemMiniCard\Layout\Field\ProductField;
use Bitrix\Crm\ItemMiniCard\Layout\Field\Value;
use Bitrix\Crm\Order\Order;
use Bitrix\Crm\Order\OrderStatus;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Router;
use Bitrix\Main\Localization\Loc;
use CCrmOwnerType;

final class FieldFactory
{
	private const PRODUCT_LIMIT = 3;

	private readonly Router $router;
	private readonly ClientBuilder $clientBuilder;

	public function __construct(
		private readonly Order $order,
	)
	{
		$this->router = Container::getInstance()->getRouter();
		$this->clientBuilder = new ClientBuilder(CCrmOwnerType::Order, $this->order->getId());
	}

	public function getContact(): ?ClientField
	{
		$orderContactCompanyCollection = $this->order->getContactCompanyCollection();
		if ($orderContactCompanyCollection === null)
		{
			return null;
		}

		$contactId = $orderContactCompanyCollection->getPrimaryContact()?->getField('ENTITY_ID');
		if ($contactId === null || $contactId <= 0)
		{
			return null;
		}

		return $this->getClient(CCrmOwnerType::Contact, $contactId);
	}

	public function getCompany(): ?ClientField
	{
		$orderContactCompanyCollection = $this->order->getContactCompanyCollection();
		if ($orderContactCompanyCollection === null)
		{
			return null;
		}

		$companyId = $orderContactCompanyCollection->getPrimaryCompany()?->getField('ENTITY_ID');
		if ($companyId === null || $companyId <= 0)
		{
			return null;
		}

		return $this->getClient(CCrmOwnerType::Company, $companyId);
	}

	private function getClient(int $entityTypeId, int $entityId): ClientField|null
	{
		$clientObj = Container::getInstance()->getFactory($entityTypeId)?->getItem($entityId);
		if (!$clientObj)
		{
			return null;
		}

		$entityName = CCrmOwnerType::ResolveName($entityTypeId);
		$title = Loc::getMessage("CRM_ITEM_MINI_CARD_ORDER_FIELD_{$entityName}");

		$client = $this->clientBuilder->buildClient($clientObj);
		if ($client === null)
		{
			return null;
		}

		return (new ClientField($title))
			->addValue($client);
	}

	public function getStatus(): ?CommonField
	{
		$statuses = OrderStatus::getListInCrmFormat();
		$statusId = $this->order->getField('STATUS_ID');

		$value = $statuses[$statusId]['NAME'] ?? null;
		if ($value === null)
		{
			return null;
		}

		return (new CommonField(Loc::getMessage('CRM_ITEM_MINI_CARD_ORDER_FIELD_STATUS')))
			->addValue($value);
	}

	public function getProducts(): ?ProductField
	{
		$basket = $this->order->getBasket();
		if ($basket === null || $basket->isEmpty())
		{
			return null;
		}

		$field = new ProductField(Loc::getMessage('CRM_ITEM_MINI_CARD_ORDER_FIELD_PRODUCTS'));

		$counter = 0;
		foreach ($basket as $basketItem)
		{
			if ($counter++ >= self::PRODUCT_LIMIT)
			{
				$productsLeftCount = count($basketItem) - self::PRODUCT_LIMIT;
				$productsLeftUrl = $this->router
					->getItemDetailUrl(CCrmOwnerType::Order, $this->order->getId())
					?->addParams([
						'active_tab' => 'tab_products',
					]);

				$field
					->setProductsLeftCount($productsLeftCount)
					->setProductsLeftUrl($productsLeftUrl?->getUri());

				break;
			}

			$field->addValue(
				new Value\Product(
					$basketItem->getField('NAME'),
					$this->router->getProductDetailUrl($basketItem->getField('ID')),
				),
			);
		}

		return $field;
	}

	public function getPrice(): MoneyField
	{
		return (new MoneyField(Loc::getMessage('CRM_ITEM_MINI_CARD_ORDER_FIELD_PRICE')))
			->addValue($this->order->getPrice(), $this->order->getCurrency());
	}
}
