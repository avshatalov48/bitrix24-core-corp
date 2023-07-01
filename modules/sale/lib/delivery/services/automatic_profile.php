<?php

namespace Bitrix\Sale\Delivery\Services;

use Bitrix\Currency\CurrencyManager;
use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Sale\Shipment;

Loc::loadMessages(__FILE__);

/**
 * Class AutomaticProfile
 * Adapter for old delivery services profile to work with new API.
 * @package Bitrix\Sale\Delivery\Services
 */
class AutomaticProfile extends Base
{
	protected $profileId = "";
	protected $oldConfig;
	protected $parentSid;
	/** @var Automatic|null $parentAutomatic */
	protected $parentAutomatic = null;
	protected $parentHandlerInitParams = array();
	protected $profileConfig = null;
	protected $profileOldConfig = null;
	protected $profileOldConfigActualized = false;

	protected static $isProfile = true;

	public function __construct(array $initParams)
	{
		if(!isset($initParams["PARENT_ID"]))
			throw new ArgumentNullException('initParams["PARENT_ID"]');

		$this->parentAutomatic = Manager::getObjectById($initParams["PARENT_ID"]);

		if(!$this->parentAutomatic || !($this->parentAutomatic instanceof Automatic))
			throw new SystemException("Can't initialize AutomaticProfile's id: ".$initParams["ID"]." parent Automatic parent_id: ".$initParams["PARENT_ID"]);

		$this->parentSid = $this->parentAutomatic->getSid();

		if($this->parentSid == '')
			throw new SystemException("Can't determine AutomaticProfile's SID. profile id: ".$initParams["ID"]." parent Automatic id: ".$initParams["PARENT_ID"]);

		$this->parentHandlerInitParams = $this->parentAutomatic->getHandlerInitParams($this->parentSid);

		if($this->parentHandlerInitParams === false)
			throw new SystemException("Can't get init services params of Automatic delivery service with sid: ".$this->parentSid);

		parent::__construct($initParams);

		if(isset($initParams['PROFILE_ID']) && $initParams['PROFILE_ID'] <> '')
			$this->profileId = $initParams['PROFILE_ID'];
		elseif(isset($this->config["MAIN"]["PROFILE_ID"]))
			$this->profileId = $this->config["MAIN"]["PROFILE_ID"];

		if($this->profileId <> '' && !array_key_exists($this->profileId, $this->parentHandlerInitParams["PROFILES"]))
			throw new SystemException("Profile \"".$this->profileId."\" is not part of Automatic delivery service with sid: ".$this->parentSid);

		if($this->name == '' && isset($this->parentHandlerInitParams['PROFILES'][$this->profileId]['TITLE']))
			$this->name = $this->parentHandlerInitParams['PROFILES'][$this->profileId]['TITLE'];

		if($this->description == '' && isset($this->parentHandlerInitParams['PROFILES'][$this->profileId]['DESCRIPTION']))
			$this->description = $this->parentHandlerInitParams['PROFILES'][$this->profileId]['DESCRIPTION'];

		if(!empty($this->parentHandlerInitParams["PROFILES"][$this->profileId]["TRACKING_CLASS_NAME"]))
			$this->trackingClass = $this->parentHandlerInitParams["PROFILES"][$this->profileId]["TRACKING_CLASS_NAME"];

		$this->inheritParams();
	}

	protected function 	inheritParams()
	{
		if($this->name == '') $this->name = $this->parentAutomatic->getName();
		if(intval($this->logotip) <= 0) $this->logotip = $this->parentAutomatic->getLogotip();
		if($this->description == '') $this->description = $this->parentAutomatic->getDescription();
		if($this->trackingClass == '') $this->trackingClass = $this->parentAutomatic->getTrackingClass();

		$parentTP = $this->parentAutomatic->getTrackingParams();

		if(is_array($parentTP) && !empty($parentTP))
		{
			if(empty($this->trackingParams) || !is_array($this->trackingParams))
			{
				$this->trackingParams = $parentTP;
			}
			else
			{
				foreach($this->trackingParams as $k => $v)
					if(empty($v) && !empty($parentTP[$k]))
						$this->trackingParams[$k] = $parentTP[$k];
			}
		}
	}

	/**
	 * @param bool $actualizeProfiles
	 * @return array Old config.
	 * @internal
	 */

	public function getOldConfig($actualizeProfiles = false)
	{
		if($this->profileOldConfig === null)
		{
			$own = Automatic::createConfig($this->parentHandlerInitParams, $this->config["MAIN"]["OLD_SETTINGS"] ?? '');
			$parent = $this->getParentService()->getOldConfig();

			$profileOldConfig = array(
				"CONFIG" =>	array_merge(
					isset($parent["CONFIG"]) && is_array($parent["CONFIG"]) ? $parent["CONFIG"] : array(),
					isset($own["CONFIG"]) && is_array($own["CONFIG"]) ? $own["CONFIG"] : array()
				),
				"CONFIG_GROUPS" =>
					isset($parent["CONFIG_GROUPS"]) && is_array($parent["CONFIG"])? $parent["CONFIG_GROUPS"] : array()
			);

			if(isset($own["CONFIG"]) && is_array($own["CONFIG"]))
				foreach($own["CONFIG"] as $k => $v)
					if(empty($v["GROUP"]) || $v["GROUP"] != $this->profileId)
						$profileOldConfig["CONFIG"][$k] = $parent["CONFIG"][$k];

			$this->profileOldConfig = $profileOldConfig;
		}

		if($actualizeProfiles && !$this->profileOldConfigActualized)
		{
			$this->profileOldConfig = $this->actualizeProfilesConfig($this->profileOldConfig);
			$this->profileOldConfigActualized = true;
		}

		return $this->profileOldConfig;
	}

	/**
	 * @param array $oldConfig
	 * @return array
	 */
	protected function actualizeProfilesConfig(array $oldConfig)
	{
		$profiles = Manager::getByParentId($this->parentId);
		$actualizedCodes = array();

		foreach($profiles as $id => $fields)
		{
			if($id == $this->id)
				continue;

			if($fields['CODE'] <> '')
			{
				if($fields['CODE'] == $this->code)
					continue;

				if(in_array($fields['CODE'], $actualizedCodes))
					continue;
			}

			/** @var \Bitrix\Sale\Delivery\Services\AutomaticProfile $service */
			$service = Manager::getPooledObject($fields);

			if($service)
			{
				$config = $service->getConfig();
				$serviceProfileId = $config['MAIN']['ITEMS']['PROFILE_ID']['VALUE'];

				if($serviceProfileId == $this->profileId)
					continue;

				$profileOldConfig = $service->getOldConfig();

				foreach($profileOldConfig['CONFIG'] as $k => $v)
					if(isset($v['GROUP']) && $v['GROUP'] == $serviceProfileId)
						$oldConfig['CONFIG'][$k] = $v;

				$actualizedCodes[] = $fields['CODE'];
			}
		}

		return $oldConfig;
	}

	public function getConfig()
	{
		if($this->profileConfig === null)
		{
			$configStructure = $this->getConfigStructure();
			$profileConfig = array();

			foreach($configStructure as $key => $configSection)
				$profileConfig[$key] = $this->glueValuesToConfig($configSection, isset($this->config[$key]) ? $this->config[$key] : array());

			if($this->profileId <> '')
			{
				$oldConfig = Automatic::createConfig($this->parentHandlerInitParams, $this->config["MAIN"]["OLD_SETTINGS"] ?? '');
				$newConfig = Automatic::convertOldConfigToNew($oldConfig);

				foreach($newConfig as $groupId => $groupParams)
					if($groupId != $this->profileId)
						unset($newConfig[$groupId]);

				$profileConfig= array_merge($this->config, $profileConfig, $newConfig);
			}

			$this->profileConfig = $profileConfig;
		}

		return $this->profileConfig;
	}

	public static function getClassTitle()
	{
		return Loc::getMessage("SALE_DLVR_HANDL_AUTP_NAME");
	}

	public static function getClassDescription()
	{
		return Loc::getMessage("SALE_DLVR_HANDL_AUTP_DESCRIPTION");
	}

	protected function calculateConcrete(\Bitrix\Sale\Shipment $shipment)
	{
		$result = $this->parentAutomatic->calculateProfile($this->profileId, $this->getOldConfig(true), $shipment);

		$result->setDeliveryPrice(
			$result->getPrice() + $this->getMarginPrice($result->getPrice())
		);

		return $result;
	}

	protected function getMarginPrice($price)
	{
		if($this->config["MAIN"]["MARGIN_TYPE"] == "%")
			$marginPrice = $price * floatval($this->config["MAIN"]["MARGIN_VALUE"]) / 100;
		else
			$marginPrice = floatval($this->config["MAIN"]["MARGIN_VALUE"]);

		return $marginPrice;
	}

	protected function getConfigStructure()
	{
		static $profiles = null;

		if($profiles === null)
		{
			$profiles = array("" => "");

			foreach($this->parentHandlerInitParams["PROFILES"] as $profileId => $profileParams)
				if($profileParams["TITLE"] <> '')
					$profiles[$profileId] = $profileParams["TITLE"]." [".$profileId."]";
		}

		$result = array(
			"MAIN" => array(
				"TITLE" => Loc::getMessage("SALE_DLVR_HANDL_AUTP_CONF_MAIN_TITLE"),
				"DESCRIPTION" => Loc::getMessage("SALE_DLVR_HANDL_AUTP_CONF_MAIN_DESCR"),
				"ITEMS" => array (
					"PROFILE_ID" => array(
						"TYPE" => "STRING",
						"NAME" => "PROFILE_ID",
						"HIDDEN" => true,
						"DEFAULT" => $this->profileId
					),
					"PROFILE_NAME" => array(
						"TYPE" => "STRING",
						"NAME" => Loc::getMessage("SALE_DLVR_HANDL_AUTP_CONF_MAIN_PROFILE_ID"),
						"DEFAULT" => $profiles[$this->profileId] ?? '',
						"READONLY" => true
					)
				)
			)
		);

		$serviceCurrency = $this->currency;

		if(\Bitrix\Main\Loader::includeModule('currency'))
		{
			$currencyList = CurrencyManager::getCurrencyList();

			if (isset($currencyList[$this->currency]))
				$serviceCurrency = $currencyList[$this->currency];

			unset($currencyList);
		}

		$marginTypes = array(
			"%" => "%",
			"CURRENCY" => $serviceCurrency
		);

		$result["MAIN"]["ITEMS"]["MARGIN_VALUE"] = array(
			"TYPE" => "STRING",
			"NAME" => Loc::getMessage("SALE_DLVR_HANDL_AUT_MARGIN_VALUE"),
			"DEFAULT" => 0
		);

		$result["MAIN"]["ITEMS"]["MARGIN_TYPE"] = array(
			"TYPE" => "ENUM",
			"NAME" => Loc::getMessage("SALE_DLVR_HANDL_AUT_MARGIN_TYPE"),
			"DEFAULT" => "%",
			"OPTIONS" => $marginTypes
		);

		$configProfileIds = array_keys($this->parentHandlerInitParams["PROFILES"]);

		if($this->profileId <> '' && in_array($this->profileId, $configProfileIds))
		{
			$oldAutoConfig = $this->parentAutomatic->getOldConfig();

			if($oldAutoConfig && isset($oldAutoConfig["CONFIG_GROUPS"]) && is_array($oldAutoConfig["CONFIG_GROUPS"]))
			{
				foreach($oldAutoConfig["CONFIG_GROUPS"] as $key => $groupId)
					if($this->profileId != $groupId)
						unset($oldAutoConfig["CONFIG_GROUPS"][$key]);

				foreach($oldAutoConfig["CONFIG"] as $key => $params)
				{
					if (!isset($params["CONFIG"]))
					{
						unset($oldAutoConfig["CONFIG"][$key]);
					}
					elseif ($this->profileId != $params["CONFIG"])
					{
						unset($oldAutoConfig["CONFIG"][$key]);
					}
				}
			}

			$oldConfig = Automatic::convertOldConfigToNew($oldAutoConfig);

			if(!empty($oldConfig))
			{
				if(isset($oldConfig["CONFIG_GROUPS"]["MAIN"]))
				{
					$oldConfig["CONFIG_GROUPS"]["MAIN_OLD"] = $oldConfig["CONFIG_GROUPS"]["MAIN"];
					unset($oldConfig["CONFIG_GROUPS"]["MAIN"]);
				}

				$result = array_merge($result, $oldConfig);
			}
		}

		return $result;
	}

	public function prepareFieldsForSaving(array $fields)
	{
		$parentAutoConfig = $this->parentAutomatic->getConfigValues();

		if(isset($fields["CONFIG"]) && is_array($fields["CONFIG"]))
			$fields["CONFIG"] = array_merge($parentAutoConfig, $fields["CONFIG"]);

		$configMain = $fields["CONFIG"]["MAIN"];
		$handler = $this->parentHandlerInitParams;

		if (isset($handler["DBSETSETTINGS"]) && is_callable($handler["DBSETSETTINGS"]))
		{
			$oldSettings = $fields["CONFIG"];
			unset($oldSettings["MAIN"]);

			if(is_array($oldSettings))
				$oldSettings = Automatic::convertNewSettingsToOld($oldSettings);

			if (!$strOldSettings = call_user_func($handler["DBSETSETTINGS"], $oldSettings))
				throw new SystemException("Can't save delivery services's old settings");
		}
		else
		{
			$strOldSettings = "";
		}

		$strOldSettings = serialize($strOldSettings);

		$fields["CONFIG"] = array(
			"MAIN" => $configMain
		);

		$fields["CONFIG"]["MAIN"]["OLD_SETTINGS"] = $strOldSettings;
		$fields = parent::prepareFieldsForSaving($fields);
		$fields["CODE"] = $this->parentAutomatic->getSid().":".$this->profileId;

		if(!empty($fields['TRACKING_PARAMS']) && is_array($fields['TRACKING_PARAMS']))
		{
			$parentTP = $this->parentAutomatic->getTrackingParams();

			foreach($fields['TRACKING_PARAMS'] as $k => $v)
			{
				if (!empty($parentTP[$k]) && $v == $parentTP[$k])
				{
					$fields['TRACKING_PARAMS'][$k] = '';
				}
			}
		}

		return $fields;
	}

	public function getParentService()
	{
		return $this->parentAutomatic;
	}

	public function isCompatible(Shipment $shipment)
	{
		return $this->parentAutomatic->isProfileCompatible($this->profileId, $this->getOldConfig(true), $shipment);
	}

	public static function isProfile()
	{
		return self::$isProfile;
	}

	/**
	 * @inheritDoc
	 */
	protected function getProfileType(): string
	{
		return (string)$this->profileId;
	}

	public function getAdditionalInfoShipmentEdit(Shipment $shipment)
	{
		return $this->parentAutomatic->getAdditionalInfoShipmentEdit($shipment);
	}

	public function processAdditionalInfoShipmentEdit(Shipment $shipment, array $requestData)
	{
		return $this->parentAutomatic->processAdditionalInfoShipmentEdit($shipment, $requestData);
	}

	public function getAdditionalInfoShipmentView(Shipment $shipment)
	{
		return $this->parentAutomatic->getAdditionalInfoShipmentView($shipment);
	}
}
