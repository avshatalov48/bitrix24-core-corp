<?php

namespace Bitrix\Crm\Conversion;

use Bitrix\Main;
use Bitrix\Main\Config\Option;

class LeadConversionConfig extends EntityConversionConfig
{
	/** @var int */
	private $typeID = LeadConversionType::UNDEFINED;

	public function __construct(array $options = null)
	{
		parent::__construct($options);

		$this->srcEntityTypeID = \CCrmOwnerType::Lead;

		$this->typeID = isset($options['TYPE_ID']) ? (int)$options['TYPE_ID'] : LeadConversionType::UNDEFINED;
		if($this->typeID === LeadConversionType::UNDEFINED)
		{
			$this->typeID = LeadConversionType::GENERAL;
		}

		$entityTypeIDs = [\CCrmOwnerType::Contact, \CCrmOwnerType::Company, \CCrmOwnerType::Deal];
		foreach($entityTypeIDs as $entityTypeID)
		{
			$this->addItem(new EntityConversionConfigItem($entityTypeID));
		}

		$this->appendCustomRelations();
	}

	protected static function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Lead;
	}

	protected static function getDefaultDestinationEntityTypeId(): int
	{
		throw new Main\NotSupportedException(__METHOD__.' should not be called in Lead context. It has different mechanism');
	}

	public static function getDefault(array $options = null)
	{
		if(!is_array($options))
		{
			$options = [];
		}

		$typeID = isset($options['TYPE_ID']) ? (int)$options['TYPE_ID'] : LeadConversionType::UNDEFINED;
		if($typeID === LeadConversionType::UNDEFINED)
		{
			$typeID = LeadConversionType::GENERAL;
		}

		if($typeID === LeadConversionType::RETURNING_CUSTOMER || $typeID === LeadConversionType::SUPPLEMENT)
		{
			$entityTypeIDs = [\CCrmOwnerType::Deal];
		}
		else
		{
			$entityTypeIDs = [\CCrmOwnerType::Deal, \CCrmOwnerType::Contact, \CCrmOwnerType::Company];
		}

		$config = new LeadConversionConfig(['TYPE_ID' => $typeID]);
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
		$typeID = isset($options['TYPE_ID']) ? (int)$options['TYPE_ID'] : LeadConversionType::UNDEFINED;
		if($typeID === LeadConversionType::UNDEFINED)
		{
			$typeID = LeadConversionType::GENERAL;
		}

		$optionValue = Option::get('crm', static::resolveOptionName($typeID), '', '');
		return static::constructFromOption($optionValue, static::getEntityTypeId(), $options);
	}

	public static function resolveCurrentSchemeID(array $options = null)
	{
		$config = static::load($options);
		if($config === null)
		{
			$config = static::getDefault($options);
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
			static::resolveOptionName($this->typeID),
			serialize($this->externalize()),
			''
		);
	}

	public function getSchemeID()
	{
		$contactConfig = $this->getItem(\CCrmOwnerType::Contact);
		$companyConfig = $this->getItem(\CCrmOwnerType::Company);
		$dealConfig = $this->getItem(\CCrmOwnerType::Deal);

		if ($dealConfig->isActive() && $contactConfig->isActive() && $companyConfig->isActive())
		{
			return LeadConversionScheme::DEAL_CONTACT_COMPANY;
		}
		if ($dealConfig->isActive() && $contactConfig->isActive())
		{
			return LeadConversionScheme::DEAL_CONTACT;
		}
		if ($dealConfig->isActive() && $companyConfig->isActive())
		{
			return LeadConversionScheme::DEAL_COMPANY;
		}
		if ($dealConfig->isActive())
		{
			return LeadConversionScheme::DEAL;
		}
		if ($contactConfig->isActive() && $companyConfig->isActive())
		{
			return LeadConversionScheme::CONTACT_COMPANY;
		}
		if ($contactConfig->isActive())
		{
			return LeadConversionScheme::CONTACT;
		}
		if($companyConfig->isActive())
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
			['TYPE_ID' => $this->getTypeID()]
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
			['TYPE_ID' => $this->typeID]
		);
	}

	protected static function resolveOptionName($typeID)
	{
		if ($typeID === LeadConversionType::RETURNING_CUSTOMER)
		{
			return 'crm_lead_rc_conversion';
		}

		return static::getOptionName(static::getEntityTypeId());
	}
}
