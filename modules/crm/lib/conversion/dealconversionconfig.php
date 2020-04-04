<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Main;
class DealConversionConfig extends EntityConversionConfig
{
	public function __construct(array $options = null)
	{
		$this->addItem(new EntityConversionConfigItem(\CCrmOwnerType::Invoice));
		$this->addItem(new EntityConversionConfigItem(\CCrmOwnerType::Quote));
	}

	public static function getDefault()
	{
		$config = new DealConversionConfig();
		$item = $config->getItem(\CCrmOwnerType::Invoice);
		$item->setActive(true);
		$item->enableSynchronization(true);
		return $config;
	}

	public static function load()
	{
		$s = Main\Config\Option::get('crm', 'crm_deal_conversion', '', '');
		$params = $s !== '' ? unserialize($s) : null;
		if(!is_array($params))
		{
			return null;
		}

		$item = new DealConversionConfig();
		$item->internalize($params);
		return $item;
	}

	function save()
	{
		Main\Config\Option::set('crm', 'crm_deal_conversion', serialize($this->externalize()), '');
	}

	public function getSchemeID()
	{
		$invoiceConfig = $this->getItem(\CCrmOwnerType::Invoice);
		$quoteConfig = $this->getItem(\CCrmOwnerType::Quote);
		if($invoiceConfig->isActive())
		{
			return DealConversionScheme::INVOICE;
		}
		elseif($quoteConfig->isActive())
		{
			return DealConversionScheme::QUOTE;
		}
		return DealConversionScheme::UNDEFINED;
	}

	public static function getCurrentSchemeID()
	{
		$config = self::load();
		if($config === null)
		{
			$config = self::getDefault();
		}

		$schemeID = $config->getSchemeID();
		if($schemeID === DealConversionScheme::UNDEFINED)
		{
			$schemeID = DealConversionScheme::getDefault();
		}

		return $schemeID;
	}
}