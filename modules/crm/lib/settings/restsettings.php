<?php
namespace Bitrix\Crm\Settings;
use Bitrix\Main;
class RestSettings
{
	/** @var RestSettings  */
	private static $current = null;
	/** @var BooleanSetting  */
	private $enableRequiredUserFieldCheck = null;

	function __construct()
	{
		$this->enableRequiredUserFieldCheck = new BooleanSetting('rest_enable_req_uf_check', false);
	}
	/**
	 * @return RestSettings
	 */
	public static function getCurrent()
	{
		if(self::$current === null)
		{
			self::$current = new RestSettings();
		}
		return self::$current;
	}
	/**
	 * @return bool
	 */
	public function isRequiredUserFieldCheckEnabled()
	{
		return $this->enableRequiredUserFieldCheck->get();
	}
	/**
	 * @param bool $enabled Enabled Flag
	 * @return void
	 */
	public function enableRequiredUserFieldCheck($enabled)
	{
		$this->enableRequiredUserFieldCheck->set($enabled);
	}
}