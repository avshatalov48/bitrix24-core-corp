<?php

namespace Bitrix\Crm\Conversion;

class DealConversionConfig extends EntityConversionConfig
{
	public function __construct(array $options = null)
	{
		parent::__construct($options);

		$this->srcEntityTypeID = \CCrmOwnerType::Deal;

		$this->addItem(new EntityConversionConfigItem(\CCrmOwnerType::Invoice));
		$this->addItem(new EntityConversionConfigItem(\CCrmOwnerType::Quote));

		$this->appendCustomRelations();
	}

	protected static function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Deal;
	}

	protected static function getDefaultDestinationEntityTypeId(): int
	{
		return \CCrmOwnerType::Invoice;
	}

	public function getSchemeID()
	{
		$invoiceConfig = $this->getItem(\CCrmOwnerType::Invoice);
		$quoteConfig = $this->getItem(\CCrmOwnerType::Quote);

		if ($invoiceConfig->isActive())
		{
			return DealConversionScheme::INVOICE;
		}

		if($quoteConfig->isActive())
		{
			return DealConversionScheme::QUOTE;
		}

		return DealConversionScheme::UNDEFINED;
	}

	public static function getCurrentSchemeID()
	{
		return static::getCurrentSchemeIDImplementation();
	}
}
