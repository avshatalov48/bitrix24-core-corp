<?php

namespace Bitrix\Crm\Conversion;

class QuoteConversionConfig extends EntityConversionConfig
{
	public function __construct(array $options = null)
	{
		parent::__construct($options);

		$this->addItem(new EntityConversionConfigItem(\CCrmOwnerType::Deal));
		$this->addItem(new EntityConversionConfigItem(\CCrmOwnerType::Invoice));
	}

	protected static function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Quote;
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
			return QuoteConversionScheme::DEAL;
		}

		if($invoiceConfig->isActive())
		{
			return QuoteConversionScheme::INVOICE;
		}

		return QuoteConversionScheme::UNDEFINED;
	}

	public static function getCurrentSchemeID()
	{
		return static::getCurrentSchemeIDImplementation();
	}
}