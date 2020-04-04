<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Channel;

use Bitrix\Crm\WebForm;

/**
 * Class Callback
 *
 * @package Bitrix\Crm\Tracking\Channel
 */
class Callback extends Base
{
	protected $code = self::Callback;

	/**
	 * Callback constructor.
	 *
	 * @param string $formId Form ID.
	 */
	public function __construct($formId)
	{
		$this->value = $formId;
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

		$names = WebForm\Manager::getListNames();
		return isset($names[$value]) ? $names[$value] : null;
	}
}