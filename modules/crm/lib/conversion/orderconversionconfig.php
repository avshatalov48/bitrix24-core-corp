<?php

namespace Bitrix\Crm\Conversion;

class OrderConversionConfig extends EntityConversionConfig
{
	public function __construct(array $params = null)
	{
		parent::__construct($params);

		$this->addItem(new EntityConversionConfigItem(\CCrmOwnerType::Deal));
		$this->addItem(new EntityConversionConfigItem(\CCrmOwnerType::Invoice));
	}

	protected static function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Order;
	}

	protected static function getDefaultDestinationEntityTypeId(): int
	{
		return \CCrmOwnerType::Deal;
	}

	public function getSchemeID()
	{
		$dealConfig = $this->getItem(\CCrmOwnerType::Deal);
		$invoiceConfig = $this->getItem(\CCrmOwnerType::Invoice);

		if ($dealConfig->isActive())
		{
			return OrderConversionScheme::DEAL;
		}

		if($invoiceConfig->isActive())
		{
			return OrderConversionScheme::INVOICE;
		}

		return OrderConversionScheme::UNDEFINED;
	}

	public static function getCurrentSchemeID()
	{
		return static::getCurrentSchemeIDImplementation();
	}
}