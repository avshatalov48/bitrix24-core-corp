<?php
namespace Bitrix\Crm\Settings;
use Bitrix\Main;
class ConversionSettings
{
	/** @var ConversionSettings  */
	private static $current = null;
	/** @var BooleanSetting  */
	private $enableAutocreation = null;

	function __construct()
	{
		$this->enableAutocreation = new BooleanSetting('conversion_enable_autocreate', true);
	}
	/**
	 * @return ConversionSettings
	 */
	public static function getCurrent()
	{
		if(self::$current === null)
		{
			self::$current = new ConversionSettings();
		}
		return self::$current;
	}
	/**
	 * @return bool
	 */
	public function isAutocreationEnabled()
	{
		return $this->enableAutocreation->get();
	}
	/**
	 * @param bool $enable Enable Autocreation
	 * @return void
	 */
	public function enableAutocreation($enable)
	{
		$this->enableAutocreation->set($enable);
	}
}