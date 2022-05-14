<?php

namespace Bitrix\Crm\Conversion;

/**
 * @deprecated
 */
class QuoteConversionConfig extends EntityConversionConfig
{
	public function __construct(array $options = null)
	{
		parent::__construct($options);

		$this->srcEntityTypeID = \CCrmOwnerType::Quote;

		$this->addItem(new EntityConversionConfigItem(\CCrmOwnerType::Deal));
		$this->addItem(new EntityConversionConfigItem(\CCrmOwnerType::Invoice));

		$this->appendCustomRelations();
	}

	protected static function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Quote;
	}

	protected static function getDefaultDestinationEntityTypeId(): int
	{
		return \CCrmOwnerType::Deal;
	}

	public static function getCurrentSchemeID()
	{
		$config = ConversionManager::getConfig(static::getEntityTypeId());

		return $config->getSchemeId();
	}
}
