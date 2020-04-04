<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Main;
class QuoteConversionConfig extends EntityConversionConfig
{
	public function __construct(array $options = null)
	{
		$this->addItem(new EntityConversionConfigItem(\CCrmOwnerType::Deal));
		$this->addItem(new EntityConversionConfigItem(\CCrmOwnerType::Invoice));
	}

	public static function getDefault()
	{
		$config = new QuoteConversionConfig();
		$item = $config->getItem(\CCrmOwnerType::Deal);
		$item->setActive(true);
		$item->enableSynchronization(true);
		return $config;
	}

	public static function load()
	{
		$s = Main\Config\Option::get('crm', 'crm_quote_conversion', '', '');
		$params = $s !== '' ? unserialize($s) : null;
		if(!is_array($params))
		{
			return null;
		}

		$item = new QuoteConversionConfig();
		$item->internalize($params);
		return $item;
	}

	function save()
	{
		Main\Config\Option::set('crm', 'crm_quote_conversion', serialize($this->externalize()), '');
	}

	public function getSchemeID()
	{
		$dealConfig = $this->getItem(\CCrmOwnerType::Deal);
		$invoiceConfig = $this->getItem(\CCrmOwnerType::Invoice);
		if($dealConfig->isActive())
		{
			return QuoteConversionScheme::DEAL;
		}
		elseif($invoiceConfig->isActive())
		{
			return QuoteConversionScheme::INVOICE;
		}
		return QuoteConversionScheme::UNDEFINED;
	}

	public static function getCurrentSchemeID()
	{
		$config = self::load();
		if($config === null)
		{
			$config = self::getDefault();
		}

		$schemeID = $config->getSchemeID();
		if($schemeID === QuoteConversionScheme::UNDEFINED)
		{
			$schemeID = QuoteConversionScheme::getDefault();
		}

		return $schemeID;
	}
}