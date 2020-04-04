<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Channel;

use Bitrix\Main\Loader;
use Bitrix\Crm\Tracking;

/**
 * Class Site24
 *
 * @package Bitrix\Crm\Tracking\Channel
 */
class Site24 extends Base implements Features\Site
{
	protected $code = self::Site24;

	/**
	 * Site24 constructor.
	 *
	 * @param string $landingSiteId Landing site ID.
	 */
	public function __construct($landingSiteId)
	{
		$this->value = $landingSiteId;
	}

	/**
	 * Return true if can use.
	 *
	 * @return bool
	 */
	public function canUse()
	{
		if (!Loader::includeModule('landing'))
		{
			return false;
		}

		return parent::canUse();
	}

	/**
	 * Get description.
	 *
	 * @return string
	 */
	public function getDescription()
	{
		$value = $this->getValue();
		if (!$value || !$this->canUse())
		{
			return null;
		}

		$names = Tracking\Provider::getB24Sites();
		$names = array_combine(
			array_column($names, 'ID'),
			array_column($names, 'DOMAIN_NAME')
		);
		return isset($names[$value]) ? $names[$value] : null;
	}
}