<?
namespace Bitrix\Crm\Order;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Sale\TradingPlatform;
use Bitrix\Sale\Helpers\Order\Builder\OrderBuilder;
use Bitrix\Sale\Helpers\Order\Builder\OrderBuilderNew;
use Bitrix\Sale\Helpers\Order\Builder\SettingsContainer;

if (!Loader::includeModule('sale'))
{
	return;
}

/**
 * Class OrderBuilderCrm
 * @package Bitrix\Crm\Order
 * @internal
 */
final class OrderBuilderCrm extends OrderBuilder
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

	public function build($data)
	{
		$this->initFields($data)
			->delegate()
			->createOrder()
			->setDiscounts() //?
			->setFields()
			->setContactCompanyCollection()
			->setProperties()
			->setUser()
			->setTradeBindingCollection()
			->buildBasket()
			->buildPayments()
			->buildShipments()
			->setDiscounts() //?
			->finalActions();
	}

	/**
	 * @return $this
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	public function buildBasket()
	{
		if(is_array($this->formData['PRODUCT']) && !empty($this->formData['PRODUCT']))
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
						}
					}
					catch(ArgumentException $e)
					{
						$this->getErrorsContainer()->addError(
							new Error(
								Loc::getMessage("CRM_ORDERBUILDER_PRODUCT_ERROR"),
								['#BASKET_CODE#' => $k]
							)
						);
					}
				}

				$fields['PRICE'] = str_replace([' ', ','], ['', '.'], $fields['PRICE']);
				$fields['QUANTITY'] = str_replace([' ', ','], ['', '.'], $fields['QUANTITY']);
				$fields['OFFER_ID'] = $fields['PRODUCT_ID'];
				$this->formData['PRODUCT'][$k] = $fields;
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

	public function buildPayments()
	{
		if(is_array($this->formData["PAYMENT"]))
		{
			foreach($this->formData["PAYMENT"] as $idx => $data)
			{
				if(is_array($data['fields']))
				{
					$this->formData["PAYMENT"][$idx] = $data['fields'];
				}
			}
		}

		return parent::buildPayments();
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
		if ((int)($this->formData['REQUISITE_ID']) > 0)
		{
			$requisites['REQUISITE_ID'] = (int)($this->formData['REQUISITE_ID']);
		}
		if ((int)($this->formData['BANK_DETAIL_ID'])> 0)
		{
			$requisites['BANK_DETAIL_ID'] = (int)($this->formData['BANK_DETAIL_ID']);
		}

		if (!empty($requisites))
		{
			$this->order->setRequisiteLink($requisites);
		}

		return $this;
	}
	protected function setTradeBindingCollection()
	{
		$platform = $this->formData['TRADING_PLATFORM'];
		$tradeCollection = $this->order->getTradeBindingCollection();
		$tradeCollection->clearCollection();

		if(strlen($platform) > 0)
		{
			$instance = TradingPlatform\Landing\Landing::getInstanceByCode($platform);
			$tradeCollection->createItem($instance);
		}

		return $this;
	}

	public function setProperties()
	{
		parent::setProperties();
		if ($this->delegate instanceof OrderBuilderNew)
		{
			$clientCollection = $this->order->getContactCompanyCollection();
			$primaryClient = $clientCollection->getPrimaryContact();
			if (empty($primaryClient))
			{
				$primaryClient = $clientCollection->getPrimaryCompany();
				$contacts = $clientCollection->getContacts();
				foreach ($contacts as $contact)
				{
					$this->setPropertiesByClient($contact);
					break;
				}
			}
			if (!empty($primaryClient))
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
				&& !strlen($property->getValue())
			)
			{
				$property->setValue($clientProperties[$property->getPropertyId()]);
			}
		}
	}
}