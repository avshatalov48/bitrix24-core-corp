<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Channel;

use Bitrix\Crm\Tracking;

/**
 * Class Site
 *
 * @package Bitrix\Crm\Tracking\Channel
 */
class Site extends Base implements iSite
{
	protected $code = self::Site;

	/**
	 * Site constructor.
	 *
	 * @param string $siteId Site ID.
	 */
	public function __construct($siteId)
	{
		$this->value = $siteId;
	}

	/**
	 * Get description.
	 *
	 * @return string
	 */
	public function getDescription()
	{
		$value = $this->getValue();
		if (!$value)
		{
			return null;
		}

		return Tracking\Internals\SiteTable::getHostBySiteId($value);
	}
}