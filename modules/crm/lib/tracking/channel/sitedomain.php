<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Channel;

/**
 * Class SomeSite
 *
 * @package Bitrix\Crm\Tracking\Channel
 */
class SiteDomain extends Base implements Features\Site
{
	protected $code = self::SiteDomain;

	/**
	 * SiteDomain constructor.
	 *
	 * @param string $domain Domain name.
	 */
	public function __construct($domain)
	{
		$this->value = $domain;
	}
}