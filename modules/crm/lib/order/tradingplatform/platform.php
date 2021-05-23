<?php

namespace Bitrix\Crm\Order\TradingPlatform;

use Bitrix\Main;
use Bitrix\Sale;

Main\Localization\Loc::loadMessages(__FILE__);

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class Platform
 * @package Bitrix\Crm\TradingPlatform
 */
abstract class Platform extends Sale\TradingPlatform\Platform
{
	/**
	 * @return string
	 */
	abstract protected function getName() : string;

	/**
	 * @return bool
	 * @throws \Exception
	 */
	public function install()
	{
		$result = Sale\TradingPlatformTable::add([
			"CODE" => $this->getCode(),
			"ACTIVE" => "Y",
			"NAME" => $this->getName(),
			"DESCRIPTION" => '',
			"CLASS" => '\\'.static::class,
			"XML_ID" => static::generateXmlId(),
		]);

		if ($result->isSuccess())
		{
			$this->isInstalled = true;
			$this->id = $result->getId();
		}

		return $result->isSuccess();
	}

	/**
	 * @return string
	 */
	protected static function generateXmlId()
	{
		return uniqid('bx_');
	}

	/**
	 * @return void
	 */
	public static function setShipmentTableOnAfterUpdateEvent() {}

	/**
	 * @return void
	 */
	protected static function unSetShipmentTableOnAfterUpdateEvent() {}

	/**
	 * @return void
	 */
	protected function setCatalogSectionsTabEvent() {}

	/**
	 * @return void
	 */
	protected function unSetCatalogSectionsTabEvent() {}
}
