<?php

namespace Bitrix\Crm\Integration;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\SalesCenter\Integration\CrmManager;

class SalesCenterManager
{
	protected static $instance;
	protected $isEnabled;

	protected function __construct()
	{
		if(Loader::includeModule('salescenter'))
		{
			$this->isEnabled = true;
		}
		else
		{
			$this->isEnabled = false;
		}
	}

	/**
	 * @return SalesCenterManager
	 */
	public static function getInstance()
	{
		if(static::$instance === null)
		{
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * @return bool
	 */
	public function isEnabled()
	{
		return $this->isEnabled;
	}

	/**
	 * @return bool
	 */
	public function isShowApplicationInSmsEditor()
	{
		return (
			$this->isEnabled &&
			Option::get('crm', 'sms_editor_salescenter_enabled', 'Y') === 'Y' &&
			method_exists(CrmManager::getInstance(), 'isShowSmsTile') &&
			CrmManager::getInstance()->isShowSmsTile()
		);
	}
}