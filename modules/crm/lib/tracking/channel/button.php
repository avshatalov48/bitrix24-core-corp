<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Channel;

use Bitrix\Crm\SiteButton;

/**
 * Class Button
 *
 * @package Bitrix\Crm\Tracking\Channel
 */
class Button extends Base
{
	protected $code = self::Button;

	/**
	 * Button constructor.
	 *
	 * @param string $buttonId Button ID.
	 */
	public function __construct($buttonId)
	{
		$this->value = $buttonId;
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

		$names = SiteButton\Manager::getListNames();
		return isset($names[$value]) ? $names[$value] : null;
	}
}