<?php

namespace Bitrix\DocumentGenerator\Entity;

use Bitrix\DocumentGenerator\Entity;
use Bitrix\DocumentGenerator\Nameable;
use Bitrix\Main\Loader;
use Bitrix\Sale\IBusinessValueProvider;
use Bitrix\Sale\PaySystem\Service;

class BusinessValue extends ChildEntity
{
	protected $provider;
	protected $consumerKey;

	protected $orderId;

	public function __construct($provider, array $options = [])
	{
		if($this->includeModule())
		{
			if($provider instanceof IBusinessValueProvider)
			{
				if($provider instanceof \Bitrix\Sale\Payment)
				{
					$this->orderId = $provider->getOrderId();
					$this->consumerKey = Service::PAY_SYSTEM_PREFIX.$provider->getPaymentSystemId();
				}
				if($this->orderId > 0)
				{
					$dbRes = \Bitrix\Sale\Order::getList(
						array(
							'select' => array('*', 'UF_DEAL_ID', 'UF_QUOTE_ID', 'UF_COMPANY_ID', 'UF_CONTACT_ID', 'UF_MYCOMPANY_ID'),
							'filter' => array('ID' => $this->orderId)
						)
					);
					if ($data = $dbRes->fetch())
					{
						$paymentData = is_array($data) ? \CCrmInvoice::PrepareSalePaymentData($data, array('PUBLIC_LINK_MODE' => 'Y')) : null;
						\CSalePaySystemAction::InitParamArrays($data, $this->orderId, '', $paymentData);
					}
				}
				$this->provider = $provider;
			}
		}
	}

	/**
	 * Returns list of value names for this entity.
	 *
	 * @return array
	 */
	public function getFields()
	{
		$fields = [];

//		There is some damn magic
//		$consumers = \Bitrix\Sale\BusinessValue::getConsumers();
//		print_r(\Bitrix\Sale\Helpers\Admin\BusinessValueControl::getPersonGroupCodes($consumers, []));
//
//      if($this->includeModule())
//		{
//			$providers = \Bitrix\Sale\BusinessValue::getProviders();
//			if(isset($providers[$this->getProviderName()]) && isset($providers[$this->getProviderName()]['FIELDS']))
//			{
//				$fields = array_keys($providers[$this->getProviderName()]['FIELDS']);
//			}
//		}

		return $fields;
	}

	/**
	 * Returns value by its name.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function getValue($name)
	{
		if($this->includeModule())
		{
			return \Bitrix\Sale\BusinessValue::getValueFromProvider($this->provider, $name, $this->consumerKey);
		}

		return null;
	}

	protected function includeModule()
	{
		return Loader::includeModule('sale');
	}

	/**
	 * @return bool
	 */
	public function isLoaded()
	{
		return true;
	}

	/**
	 * @return string
	 */
	public static function getLangName()
	{
		return 'Бизнес-смыслы';
	}
}