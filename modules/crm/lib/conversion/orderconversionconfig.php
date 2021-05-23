<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Main;
class OrderConversionConfig extends EntityConversionConfig
{
	public function __construct(array $params = null)
	{
		$this->addItem(new EntityConversionConfigItem(\CCrmOwnerType::Deal));
		$this->addItem(new EntityConversionConfigItem(\CCrmOwnerType::Invoice));
	}

	public static function getDefault()
	{
		$config = new OrderConversionConfig();
		$item = $config->getItem(\CCrmOwnerType::Deal);
		$item->setActive(true);
		$item->enableSynchronization(true);
		return $config;
	}

	public static function load()
	{
		$s = Main\Config\Option::get('crm', 'crm_order_conversion', '', '');
		$params = $s !== '' ? unserialize($s, ['allowed_classes' => false]) : null;
		if(!is_array($params))
		{
			return null;
		}

		$item = new OrderConversionConfig();
		$item->internalize($params);
		return $item;
	}

	function save()
	{
		Main\Config\Option::set('crm', 'crm_order_conversion', serialize($this->externalize()), '');
	}

	public function getSchemeID()
	{
		$dealConfig = $this->getItem(\CCrmOwnerType::Deal);
		$invoiceConfig = $this->getItem(\CCrmOwnerType::Invoice);
		if($dealConfig->isActive())
		{
			return OrderConversionScheme::DEAL;
		}
		elseif($invoiceConfig->isActive())
		{
			return OrderConversionScheme::INVOICE;
		}
		return OrderConversionScheme::UNDEFINED;
	}

	public static function getCurrentSchemeID()
	{
		$config = self::load();
		if($config === null)
		{
			$config = self::getDefault();
		}

		$schemeID = $config->getSchemeID();
		if($schemeID === OrderConversionScheme::UNDEFINED)
		{
			$schemeID = OrderConversionScheme::getDefault();
		}

		return $schemeID;
	}
}