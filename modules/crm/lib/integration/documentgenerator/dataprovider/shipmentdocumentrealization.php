<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Order\ShipmentItem;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\DocumentGenerator\Nameable;
use \Bitrix\Crm\Integration\DocumentGenerator;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Internals\ShipmentTable;
use Bitrix\Sale\Repository\ShipmentRepository;
use Bitrix\Crm\Order\Shipment;
use Bitrix\Crm\Order;
use Bitrix\Crm\Security\EntityPermissionType;

/**
 * Class ShipmentDocumentRealization
 *
 * @package Bitrix\Crm\Integration\DocumentGenerator\DataProvider
 */
class ShipmentDocumentRealization extends ProductsDataProvider implements Nameable
{
	/** @var Order\Order|null */
	private $order;

	/** @var Shipment|null */
	private $shipment;

	/**
	 * @inheritDoc
	 */
	protected function fetchData()
	{
		if ($this->data === null)
		{
			$this->shipment = ShipmentRepository::getInstance()->getById((int)$this->source);
			parent::fetchData();
			$this->order = $this->shipment ? $this->shipment->getOrder() : null;
			$this->fetchContactCompanyData();
		}
	}

	/**
	 * @return array
	 */
	protected function loadProductsData()
	{
		if (!$this->shipment)
		{
			return [];
		}

		$result = [];

		/** @var ShipmentItem $shipmentItem */
		foreach($this->shipment->getShipmentItemCollection() as $shipmentItem)
		{
			$basketItem = $shipmentItem->getBasketItem();
			if (!$basketItem)
			{
				continue;
			}

			$item = DocumentGenerator\DataProvider\Order::getProductProviderDataByBasketItem(
				$basketItem->toArray(),
				new ItemIdentifier(
					\CCrmOwnerType::ShipmentDocument,
					$this->shipment->getId(),
				),
				$this->getCurrencyId()
			);

			if (!$this->isProductVariantSupported($item['PRODUCT_VARIANT']))
			{
				continue;
			}

			$item['ID'] = $shipmentItem->getId();
			$item['QUANTITY'] = $shipmentItem->getQuantity();
			$item['CUSTOMIZED'] = 'Y';

			$result[] = $item;
		}

		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public function hasAccess($userId)
	{
		if ($this->isLoaded())
		{
			return EntityAuthorization::checkPermission(
				EntityPermissionType::READ,
				\CCrmOwnerType::ShipmentDocument,
				$this->source,
				\CCrmPerms::GetUserPermissions($userId)
			);
		}

		return false;
	}

	/**
	 * @inheritDoc
	 */
	public static function getLangName()
	{
		return Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_SHPD_RLZ_TITLE');
	}

	/**
	 * @inheritDoc
	 */
	public function getCrmOwnerType(): int
	{
		return \CCrmOwnerType::ShipmentDocument;
	}

	/**
	 * @inheritDoc
	 */
	protected function getUserFieldEntityID(): ?string
	{
		return null;
	}

	/**
	 * @inheritDoc
	 */
	protected function getTableClass(): ?string
	{
		return ShipmentTable::class;
	}

	/**
	 * @return array
	 */
	protected function getGetListParameters()
	{
		return [
			'select' => [
				'ID',
				'ACCOUNT_NUMBER',
				'DATE_INSERT',
			],
		];
	}

	private function fetchContactCompanyData(): void
	{
		if ($this->order)
		{
			$contactCompanyCollection = $this->order->getContactCompanyCollection();
			if ($contactCompanyCollection)
			{
				$company = $contactCompanyCollection->getPrimaryCompany();
				if ($company)
				{
					$this->data['COMPANY_ID'] = (int)$company->getField('ENTITY_ID');
				}
				else
				{
					$companies = $contactCompanyCollection->getCompanies();
					foreach ($companies as $company)
					{
						$this->data['COMPANY_ID'] = (int)$company->getField('ENTITY_ID');
						break;
					}
				}

				$contact = $contactCompanyCollection->getPrimaryContact();
				if ($contact)
				{
					$this->data['CONTACT_ID'] = (int)$contact->getField('ENTITY_ID');
				}
				else
				{
					$contacts = $contactCompanyCollection->getContacts();
					foreach ($contacts as $contact)
					{
						$this->data['CONTACT_ID'] = (int)$contact->getField('ENTITY_ID');
						break;
					}
				}
			}
		}
	}

	public function getCurrencyId()
	{
		if ($this->order)
		{
			return $this->order->getCurrency();
		}

		return parent::getCurrencyId();
	}
}
