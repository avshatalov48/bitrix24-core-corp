<?
namespace Sale\Handlers\Delivery;

use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Delivery\Requests\Result;
use Bitrix\Sale\Order;
use Bitrix\Sale\Shipment;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Sale\Delivery\Services\Manager;
use Bitrix\Sale\Delivery\CalculationResult;
use Bitrix\Sale\ShipmentCollection;
use Sale\Handlers\Delivery\Additional\RestClient;
use Sale\Handlers\Delivery\Additional\RusPost\Helper;

Loc::loadMessages(__FILE__);

class AdditionalProfile extends \Bitrix\Sale\Delivery\Services\Base
{
	/** @var AdditionalHandler Parent service. */
	protected $additionalHandler = null;
	/** @var string Service type */
	protected $profileType = "";
	protected $trackingTitle = "";
	protected $trackingDescription = "";

	protected static $whetherAdminExtraServicesShow = true;
	/** @var bool This handler is profile */
	protected static $isProfile = true;

	/**
	 * @param array $initParams
	 * @throws ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public function __construct(array $initParams)
	{
		if(empty($initParams["PARENT_ID"]))
			throw new ArgumentNullException('initParams[PARENT_ID]');

		parent::__construct($initParams);
		$this->additionalHandler = Manager::getObjectById($this->parentId);

		if(!($this->additionalHandler instanceof AdditionalHandler))
			throw new ArgumentNullException('this->additionalHandler is not instance of AdditionalHandler');

		if(isset($initParams['PROFILE_ID']) && $initParams['PROFILE_ID'] <> '')
			$this->profileType = $initParams['PROFILE_ID'];
		elseif(isset($this->config['MAIN']['PROFILE_TYPE']) && $this->config['MAIN']['PROFILE_TYPE'] <> '')
			$this->profileType = $this->config['MAIN']['PROFILE_TYPE'];

		if($this->profileType <> '')
		{
			$profileParams = $this->getProfileParams();

			if(!empty($profileParams) && $this->id <= 0)
			{
				$this->name = $profileParams['NAME'];
				$this->description = $profileParams['DESCRIPTION'];

				if(!empty($profileParams['LOGOTIP']))
					$this->logotip = $profileParams['LOGOTIP'];
			}

			if($this->isRusPost())
			{
				if(empty($this->config['MAIN']['IS_OTPRAVKA_SUPPORTED']))
				{
					if(isset($profileParams['IS_OTPRAVKA_SUPPORTED']) && $profileParams['IS_OTPRAVKA_SUPPORTED'] == 'Y')
					{
						$this->config['MAIN']['IS_OTPRAVKA_SUPPORTED'] = 'Y';
					}
					else
					{
						$this->config['MAIN']['IS_OTPRAVKA_SUPPORTED'] = 'N';
					}
				}
			}
		}

		$this->inheritParams();
	}

	protected function isRusPost()
	{
		$parentConfig = $this->additionalHandler->getConfigValues();
		return $parentConfig['MAIN']['SERVICE_TYPE'] == "RUSPOST";
	}

	/**
	 * @return array
	 */
	protected function getProfileParams()
	{
		$result = array();
		$list = $this->additionalHandler->getProfilesListFull();

		if(!empty($list[$this->profileType]))
			$result = $list[$this->profileType];

		return $result;
	}

	/**
	 * @return string
	 */
	public static function getClassTitle()
	{
		return Loc::getMessage("SALE_DLVRS_ADDP_NAME");
	}

	/**
	 * @return string
	 */
	public static function getClassDescription()
	{
		return Loc::getMessage("SALE_DLVRS_ADDP_DESCRIPTION");
	}

	/**
	 * Defines inheritance behavior.
	 * @throws ArgumentNullException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function 	inheritParams()
	{
		if($this->name == '') $this->name = $this->additionalHandler->getName();
		if(intval($this->logotip) <= 0) $this->logotip = $this->additionalHandler->getLogotip();
		if($this->description == '') $this->description = $this->additionalHandler->getDescription();

		$this->trackingParams = $this->additionalHandler->getTrackingParams();
		$this->trackingClass = $this->additionalHandler->getTrackingClass();
		$this->trackingTitle = $this->additionalHandler->getTrackingClassTitle();
		$this->trackingDescription = $this->additionalHandler->getTrackingClassDescription();

		if(!$this->isRusPost() || $this->config['MAIN']['IS_OTPRAVKA_SUPPORTED'] == 'Y')
		{
			$this->deliveryRequestHandler = $this->additionalHandler->getDeliveryRequestHandler();
		}

		$parentES = \Bitrix\Sale\Delivery\ExtraServices\Manager::getExtraServicesList($this->parentId);

		if(!empty($parentES))
		{
			foreach($parentES as $esFields)
			{
				if(
					($esFields['CODE'] <> '' && !$this->extraServices->getItemByCode($esFields['CODE']))
					|| $esFields['CODE'] == ''
				)
				{
					$this->extraServices->addItem($esFields, $this->currency);
				}
			}
		}
	}

	/**
	 * Calculates price
	 * @param Shipment $shipment
	 * @return CalculationResult
	 * @throws SystemException
	 * todo: Send default values if some params added to config, but not saved yet.
	 */
	protected function calculateConcrete(Shipment $shipment)
	{
		$client = new \Sale\Handlers\Delivery\Additional\RestClient();

		try
		{
			$result =  $client->getDeliveryPrice(
				$this->additionalHandler->getServiceType(),
				$this->profileType,
				self::extractConfigValues($this->additionalHandler->getConfig()),
				self::extractConfigValues($this->getConfig()),
				$shipment
			);
		}
		catch(SystemException $e)
		{
			$result = new CalculationResult();
			$result->addError(new Error($e->getMessage()));
		}

		/** @var ShipmentCollection $shipmentCollection */
		$shipmentCollection = $shipment->getCollection();
		/** @var Order $order */
		$order = $shipmentCollection->getOrder();
		$shipmentCurrency = $order->getCurrency();

		if ($result->isSuccess() && $this->currency != $shipmentCurrency)
		{
			if(!Loader::includeModule('currency'))
				throw new SystemException("Can't include module \"Currency\"");

			$result->setDeliveryPrice(
				\CCurrencyRates::convertCurrency(
					$result->getPrice(),
					$this->currency,
					$shipmentCurrency
			));
		}

		return $result;
	}


	/**
	 * @param array $config
	 * @return array
	 */
	public static function extractConfigValues(array $config)
	{
		if(!is_array($config) || empty($config))
			return array();

		$result = array();

		foreach($config as $sectionKey => $sectionConfig)
		{
			if(isset($sectionConfig["ITEMS"]) && is_array($sectionConfig["ITEMS"]) && !empty($sectionConfig["ITEMS"]))
			{
				$result[$sectionKey] = array();

				foreach($sectionConfig["ITEMS"] as $name => $params)
				{
					$value = "";

					if(isset($params['VALUE']))
						$value = $params['VALUE'];
					elseif(isset($params['DEFAULT']))
						$value = $params['DEFAULT'];

					$result[$sectionKey][$name] = $value;
				}
			}
		}

		return $result;
	}


	/**
	 * @return bool
	 */
	public function isCalculatePriceImmediately()
	{
		return $this->additionalHandler->isCalculatePriceImmediately();
	}

	/**
	 * @return array Handler's configuration
	 */
	protected function getConfigStructure()
	{
		$client = new \Sale\Handlers\Delivery\Additional\RestClient();

		$res = $client->getProfileConfig(
			$this->additionalHandler->getServiceType(),
			$this->profileType
		);

		if(!$res->isSuccess())
			array();

		return $res->getData();
	}

	/**
	 * @return \Bitrix\Sale\Delivery\Services\Base|\Sale\Handlers\Delivery\SpsrHandler Parent sevice.
	 */
	public function getParentService()
	{
		return $this->additionalHandler;
	}

	/**
	 * @param Shipment $shipment
	 * @return bool
	 */
	public function isCompatible(Shipment $shipment)
	{
		return array_key_exists(
			$this->profileType,
			$this->additionalHandler->getCompatibleProfiles($shipment)
		);
	}

	/**
	 * Install handler
	 */
	public static function install()
	{
		AdditionalHandler::install();
	}

	/**
	 * Uninstall
	 */
	public static function unInstall()
	{
		AdditionalHandler::unInstall();
	}

	/**
	 * Is handler installed
	 */
	public static function isInstalled()
	{
		AdditionalHandler::isInstalled();
	}

	/**
	 * @return bool
	 */
	public static function isProfile()
	{
		return self::$isProfile;
	}

	/**
	 * @return bool
	 */
	public static function whetherAdminExtraServicesShow()
	{
		return self::$whetherAdminExtraServicesShow;
	}

	/**
	 * @return array
	 */
	public function getEmbeddedExtraServicesList()
	{
		static $result = null;

		if($result === null)
		{
			$result = array();
			$client = new RestClient();
			$res = $client->getProfileExtraServices(
				$this->additionalHandler->getServiceType(),
				$this->profileType,
				self::extractConfigValues($this->getConfig())
			);

			if($res->isSuccess())
				$result = $res->getData();
		}

		return $result;
	}

	public function getTrackingClassTitle()
	{
		return !empty($this->config['MAIN']['TRACKING_TITLE']) ? $this->config['MAIN']['TRACKING_TITLE'] : '';
	}

	public function getTrackingClassDescription()
	{
		return !empty($this->config['MAIN']['TRACKING_DESCRIPTION']) ? $this->config['MAIN']['TRACKING_DESCRIPTION'] : '';
	}

	/**
	 * @return bool
	 */
	public function isTrackingInherited()
	{
		return true;
	}

	public function execAdminAction()
	{
		$result = new \Bitrix\Sale\Result();

		if($this->isRusPost() && !empty($this->config['MAIN']['OTPRAVKA_RPO']))
		{
			$parentConfig = $this->getParentService()->getConfigValues();

			if(empty($parentConfig['MAIN']['SHIPPING_POINT']['VALUE']))
			{
				return $result;
			}

			$selectedShippingPoint = $parentConfig['MAIN']['SHIPPING_POINT']['VALUE'];
			$shippingPoints = Helper::getEnabledShippingPointsList($this->id);

			if(isset($shippingPoints[$selectedShippingPoint]))
			{
				if(in_array($this->config['MAIN']['OTPRAVKA_RPO'], $shippingPoints[$selectedShippingPoint]['available-mail-types']))
				{
					$this->config['MAIN']['IS_OTPRAVKA_SUPPORTED'] = 'Y';
					$this->config['MAIN']['IS_OTPRAVKA_SUPPORTED_LABEL'] = Loc::getMessage('SALE_DLVRS_ADDP_Y');
				}
				else
				{
					$this->config['MAIN']['IS_OTPRAVKA_SUPPORTED'] = 'N';
					$this->config['MAIN']['IS_OTPRAVKA_SUPPORTED_LABEL'] = Loc::getMessage('SALE_DLVRS_ADDP_N');
				}
			}
		}

		return $result;
	}
}