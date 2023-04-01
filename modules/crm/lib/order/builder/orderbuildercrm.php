<?
namespace Bitrix\Crm\Order\Builder;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\Json;
use Bitrix\Sale\Helpers\Order\Builder\BuildingException;
use Bitrix\Sale\Helpers\Order\Builder\OrderBuilder;
use Bitrix\Sale\Helpers\Order\Builder\OrderBuilderNew;
use Bitrix\Sale\Helpers\Order\Builder\SettingsContainer;
use Bitrix\Crm\Order\Order;
use Bitrix\Crm\Order\BasketItem;
use Bitrix\Crm\Order\ContactCompanyEntity;
use Bitrix\Crm\Order\Matcher;
use Bitrix\Crm\Order\PersonType;

if (!Loader::includeModule('sale'))
{
	return;
}

/**
 * Class OrderBuilderCrm
 * @package Bitrix\Crm\Order\Builder
 * @internal
 */
class OrderBuilderCrm extends OrderBuilder
{
	/** @var Order  */
	protected $order = null;
	/**
	 * OrderBuilderCrm constructor.
	 * @param SettingsContainer $settings
	 */
	public function __construct(SettingsContainer $settings)
	{
		parent::__construct($settings);
		$this->setBasketBuilder(new BasketBuilderCrm($this));
	}

	/**
	 * @inheritDoc
	 */
	protected function prepareFields(array $fields)
	{
		$fields = parent::prepareFields($fields);

		if (empty($fields['SITE_ID']))
		{
			$fields['SITE_ID'] = SITE_ID;
		}

		if (empty($fields['PERSON_TYPE_ID']))
		{
			if (!empty($fields['CLIENT']['COMPANY_ID']))
			{
				$fields['PERSON_TYPE_ID'] = PersonType::getCompanyPersonTypeId();
			}
			else
			{
				$fields['PERSON_TYPE_ID'] = PersonType::getContactPersonTypeId();
			}
		}

		if (!isset($fields['RESPONSIBLE_ID']))
		{
			$fields['RESPONSIBLE_ID'] = \CCrmSecurityHelper::GetCurrentUserID();
		}

		return $fields;
	}

	public function build($data)
	{
		$this->initFields($data)
			->delegate()
			->createOrder()
			->setDiscounts() //?
			->setFields()
			->buildTradeBindings()
			->setContactCompanyCollection()
			->setProperties()
			->setUser()
			->setEntityBinding()
			->buildBasket()
			->buildShipments()
			->buildPayments()
			->setRelatedProperties()
			->setDiscounts() //?
			->finalActions();
	}

	/**
	 * @return OrderBuilder
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	public function buildBasket()
	{
		if(isset($this->formData['PRODUCT']) && is_array($this->formData['PRODUCT']) && !empty($this->formData['PRODUCT']))
		{
			foreach($this->formData['PRODUCT'] as $k => $p)
			{
				if(isset($p['FIELDS_VALUES']))
				{
					$fieldsValues = $p['FIELDS_VALUES'];

					try
					{
						$fieldsValues = Json::decode($fieldsValues);

						if(is_array($fieldsValues))
						{
							$fields = array_intersect_key($p, array_flip(BasketItem::getAllFields()));
							$fields = array_merge($fieldsValues, $fields);
							$fields['OFFER_ID'] = $fields['PRODUCT_ID'];
							$this->formData['PRODUCT'][$k] = $fields;
						}
					}
					catch(ArgumentException $e)
					{
						$this->getErrorsContainer()->addError(
							new Error(
								Loc::getMessage(
									'CRM_ORDERBUILDER_PRODUCT_ERROR',
									['#BASKET_CODE#' => $k]
								)
							)
						);
					}
				}
			}

			sortByColumn($this->formData["PRODUCT"], array("SORT" => SORT_ASC), '', null, true);
		}

		return parent::buildBasket();
	}

	/**
	 * @return $this
	 * @throws ArgumentException
	 */
	public function setFields()
	{
		$fields = ['COMMENTS', 'STATUS_ID'];

		foreach($fields as $field)
		{
			if(isset($this->formData[$field]))
			{
				$r = $this->order->setField($field, $this->formData[$field]);
				if (!$r->isSuccess())
				{
					$this->getErrorsContainer()->addErrors($r->getErrors());
				}
			}
		}

		if (!empty($this->formData['REQUISITE_BINDING']) && is_array($this->formData['REQUISITE_BINDING']))
		{
			$this->order->setRequisiteLink($this->formData['REQUISITE_BINDING']);
		}

		return parent::setFields();
	}

	protected function prepareDateFields(array $fields, array $dateFields)
	{
		foreach($dateFields as $dateFieldName)
		{
			if(!empty($fields[$dateFieldName]) && is_string($fields[$dateFieldName]))
			{
				try
				{
					$fields[$dateFieldName] = new \Bitrix\Main\Type\Date($fields[$dateFieldName]);
				}
				catch (ObjectException $exception)
				{
					$this->errorsContainer->addError(
						new Error(
							Loc::getMessage("CRM_ORDERBUILDER_".$dateFieldName."_ERROR")
						)
					);
					throw new BuildingException();
				}
			}
		}

		if (isset($fields['DATE_RESPONSIBLE_ID']))
		{
			unset($fields['DATE_RESPONSIBLE_ID']);
		}

		return $fields;
	}

	public function buildPayments()
	{
		$dateTypeFields = [
			'DATE_PAID', 'DATE_PAY_BEFORE', 'DATE_BILL',
			'PAY_RETURN_DATE', 'PAY_VOUCHER_DATE'
		];

		if (isset($this->formData["PAYMENT"]) && is_array($this->formData["PAYMENT"]))
		{
			foreach($this->formData["PAYMENT"] as $idx => $data)
			{
				if(is_array($data['fields']))
				{
					$this->formData["PAYMENT"][$idx] = $data['fields'];
				}

				$this->formData["PAYMENT"][$idx] = $this->prepareDateFields(
					$this->formData["PAYMENT"][$idx],
					$dateTypeFields
				);
			}
		}

		return parent::buildPayments();
	}

	/**
	 * Filling form data fields `PRODUCT` and `SHIPMENT` by form data of basket builder.
	 *
	 * @return void
	 */
	private function fillShipmentsByBasketBuilder(): void
	{
		$basketFormData = $this->getBasketBuilder()->getFormData();

		if (isset($this->formData['PRODUCT'], $basketFormData['PRODUCT']))
		{
			$this->formData['PRODUCT'] = $basketFormData['PRODUCT'];
		}

		if (isset($this->formData['SHIPMENT'], $basketFormData['SHIPMENT']))
		{
			foreach ($basketFormData['SHIPMENT'] as $index => $shipment)
			{
				if (isset($this->formData['SHIPMENT'][$index], $shipment['PRODUCT']))
				{
					$this->formData['SHIPMENT'][$index]['PRODUCT'] = $shipment['PRODUCT'];
				}
			}
		}
	}

	public function buildShipments()
	{
		if ($this->getSettingsContainer()->getItemValue('fillShipmentsByBasketBuilder'))
		{
			$this->fillShipmentsByBasketBuilder();
		}

		$dateTypeFields = [
			'DELIVERY_DOC_DATE', 'DATE_DEDUCTED', 'DATE_MARKED',
			'DATE_CANCELED', 'DATE_RESPONSIBLE_ID'
		];

		if(isset($this->formData["SHIPMENT"]) && is_array($this->formData["SHIPMENT"]))
		{
			foreach($this->formData["SHIPMENT"] as $idx => $data)
			{
				$this->formData["SHIPMENT"][$idx] = $this->prepareDateFields(
					$this->formData["SHIPMENT"][$idx],
					$dateTypeFields
				);
			}
		}

		return parent::buildShipments();
	}

	protected function setEntityBinding()
	{
		$ownerId = $this->formData['OWNER_ID'] ?? 0;
		$ownerTypeId = $this->formData['OWNER_TYPE_ID'] ?? 0;

		if ($ownerId && $ownerTypeId)
		{
			$binding = $this->order->getEntityBinding();

			if ($binding === null)
			{
				$binding = $this->order->createEntityBinding();
			}

			if ($binding)
			{
				$binding->setField('OWNER_ID', $ownerId);
				$binding->setField('OWNER_TYPE_ID', $ownerTypeId);
			}
		}

		return $this;
	}

	protected function setContactCompanyCollection()
	{
		$client = $this->formData['CLIENT'];
		$clientCollection = $this->order->getContactCompanyCollection();
		$clientCollection->clearCollection();

		if((int)($client['COMPANY_ID']) > 0)
		{
			/** @var \Bitrix\Crm\Order\Company $company */
			$company = $clientCollection->createCompany();
			$company->setFields([
				'ENTITY_ID' => $client['COMPANY_ID'],
				'IS_PRIMARY' => 'Y'
			]);
		}

		if(!empty($client['CONTACT_IDS']) && is_array($client['CONTACT_IDS']))
		{
			$contactIds = array_unique($client['CONTACT_IDS']);
			$firstKey = key($contactIds);
			foreach($contactIds as $key => $itemId)
			{
				if ($itemId > 0)
				{
					$contact = $clientCollection->createContact();
					$contact->setFields([
						'ENTITY_ID' => $itemId,
						'IS_PRIMARY' => ($key === $firstKey) ? 'Y' : 'N'
					]);
				}
			}
		}

		$requisites = [];
		if (isset($this->formData['REQUISITE_ID']) && (int)($this->formData['REQUISITE_ID']) > 0)
		{
			$requisites['REQUISITE_ID'] = (int)($this->formData['REQUISITE_ID']);
		}
		if (isset($this->formData['BANK_DETAIL_ID']) && (int)($this->formData['BANK_DETAIL_ID'])> 0)
		{
			$requisites['BANK_DETAIL_ID'] = (int)($this->formData['BANK_DETAIL_ID']);
		}

		if (!empty($requisites))
		{
			$this->order->setRequisiteLink($requisites);
		}

		return $this;
	}

	public function buildTradeBindings()
	{
		if (isset($this->formData['TRADING_PLATFORM']))
		{
			$this->formData['TRADE_BINDINGS'] = [
				[
					'TRADING_PLATFORM_ID' => (int)$this->formData['TRADING_PLATFORM']
				]
			];

			unset($this->formData['TRADING_PLATFORM']);
		}

		return parent::buildTradeBindings();
	}

	public function setProperties()
	{
		parent::setProperties();
		if ($this->delegate instanceof OrderBuilderNew)
		{
			$clientCollection = $this->order->getContactCompanyCollection();
			$company = $clientCollection->getPrimaryCompany();
			if (!empty($company))
			{
				$this->setPropertiesByClient($company);
			}

			$primaryClient = $clientCollection->getPrimaryContact();
			if (empty($primaryClient))
			{
				$contacts = $clientCollection->getContacts();
				foreach ($contacts as $contact)
				{
					$this->setPropertiesByClient($contact);
					break;
				}
			}
			else
			{
				$this->setPropertiesByClient($primaryClient);
			}
		}

		return $this;
	}

	protected function setPropertiesByClient(ContactCompanyEntity $entity)
	{
		$clientProperties = Matcher\FieldMatcher::getPropertyValues($entity->getField('ENTITY_TYPE_ID'), (int)$entity->getField('ENTITY_ID'));
		$propertyCollection = $this->order->getPropertyCollection();
		/**
		 * @var  \Bitrix\Crm\Order\PropertyValue $property
		 */
		foreach ($propertyCollection as $property)
		{
			if (
				isset($clientProperties[$property->getPropertyId()])
				&& !is_array($property->getValue())
				&& !mb_strlen($property->getValue())
			)
			{
				$property->setValue($clientProperties[$property->getPropertyId()]);
			}
		}
	}
}
