<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Main;

class LeadConversionConfig extends EntityConversionConfig
{
	/** @var int */
	private $typeID = LeadConversionType::UNDEFINED;

	public function __construct(array $options = null)
	{
		if(!is_array($options))
		{
			$options = array();
		}

		$this->typeID = isset($options['TYPE_ID']) ? (int)$options['TYPE_ID'] : LeadConversionType::UNDEFINED;
		if($this->typeID === LeadConversionType::UNDEFINED)
		{
			$this->typeID = LeadConversionType::GENERAL;
		}

		$entityTypeIDs = array(\CCrmOwnerType::Contact, \CCrmOwnerType::Company, \CCrmOwnerType::Deal);
		foreach($entityTypeIDs as $entityTypeID)
		{
			$this->addItem(new EntityConversionConfigItem($entityTypeID));
		}
	}
	public static function getDefault(array $options = null)
	{
		if(!is_array($options))
		{
			$options = array();
		}

		$typeID = isset($options['TYPE_ID']) ? (int)$options['TYPE_ID'] : LeadConversionType::UNDEFINED;
		if($typeID === LeadConversionType::UNDEFINED)
		{
			$typeID = LeadConversionType::GENERAL;
		}

		if($typeID === LeadConversionType::RETURNING_CUSTOMER || $typeID === LeadConversionType::SUPPLEMENT)
		{
			$entityTypeIDs = array(\CCrmOwnerType::Deal);
		}
		else
		{
			$entityTypeIDs = array(\CCrmOwnerType::Deal, \CCrmOwnerType::Contact, \CCrmOwnerType::Company);
		}

		$config = new LeadConversionConfig(array('TYPE_ID' => $typeID));
		foreach($entityTypeIDs as $entityTypeID)
		{
			$item = $config->getItem($entityTypeID);
			$item->setActive(true);
			$item->enableSynchronization(true);
		}
		return $config;
	}
	public static function load(array $options = null)
	{
		if(!is_array($options))
		{
			$options = array();
		}

		$typeID = isset($options['TYPE_ID']) ? (int)$options['TYPE_ID'] : LeadConversionType::UNDEFINED;
		if($typeID === LeadConversionType::UNDEFINED)
		{
			$typeID = LeadConversionType::GENERAL;
		}

		$s = Main\Config\Option::get('crm', self::resolveOptionName($typeID), '', '');
		$params = $s !== '' ? unserialize($s) : null;
		if(!is_array($params))
		{
			return null;
		}

		$item = new static($options);
		$item->internalize($params);
		return $item;
	}
	public static function resolveCurrentSchemeID(array $options = null)
	{
		$config = self::load($options);
		if($config === null)
		{
			$config = self::getDefault($options);
		}

		$schemeID = $config->getSchemeID();
		if($schemeID === LeadConversionScheme::UNDEFINED)
		{
			$schemeID = LeadConversionScheme::getDefault($options);
		}
		return $schemeID;
	}
	/**
	 * @return int
	 */
	public function getTypeID()
	{
		return $this->typeID;
	}
	/**
	 * @param int $typeID
	 * return void
	 */
	public function setTypeID($typeID)
	{
		$this->typeID = $typeID;
	}
	public function save()
	{
		Main\Config\Option::set(
			'crm',
			self::resolveOptionName($this->typeID),
			serialize($this->externalize()),
			''
		);
	}
	public function getSchemeID()
	{
		$contactConfig = $this->getItem(\CCrmOwnerType::Contact);
		$companyConfig = $this->getItem(\CCrmOwnerType::Company);
		$dealConfig = $this->getItem(\CCrmOwnerType::Deal);
		if($dealConfig->isActive() && $contactConfig->isActive() && $companyConfig->isActive())
		{
			return LeadConversionScheme::DEAL_CONTACT_COMPANY;
		}
		elseif($dealConfig->isActive() && $contactConfig->isActive())
		{
			return LeadConversionScheme::DEAL_CONTACT;
		}
		elseif($dealConfig->isActive() && $companyConfig->isActive())
		{
			return LeadConversionScheme::DEAL_COMPANY;
		}
		elseif($dealConfig->isActive())
		{
			return LeadConversionScheme::DEAL;
		}
		elseif($contactConfig->isActive() && $companyConfig->isActive())
		{
			return LeadConversionScheme::CONTACT_COMPANY;
		}
		elseif($contactConfig->isActive())
		{
			return LeadConversionScheme::CONTACT;
		}
		elseif($companyConfig->isActive())
		{
			return LeadConversionScheme::COMPANY;
		}
		return LeadConversionScheme::UNDEFINED;
	}
	public function getCurrentSchemeID()
	{
		return LeadConversionScheme::getCurrentOrDefault($this);
	}
	public function getSchemeJavaScriptDescriptions($checkPermissions = false)
	{
		return LeadConversionScheme::getJavaScriptDescriptions(
			$checkPermissions,
			array('TYPE_ID' => $this->getTypeID())
		);
	}

	/**
	 * Check if this configuration is supported.
	 * Result is depended on current conversion type.
	 * @return bool
	 */
	public function isSupported()
	{
		return LeadConversionScheme::isSupported(
			$this->getSchemeID(),
			array('TYPE_ID' => $this->typeID)
		);
	}

	protected static function resolveOptionName($typeID)
	{
		return $typeID === LeadConversionType::RETURNING_CUSTOMER
			? 'crm_lead_rc_conversion' : 'crm_lead_conversion';
	}
}