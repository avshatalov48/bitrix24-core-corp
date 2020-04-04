<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Channel;

use Bitrix\Crm\Tracking;
use Bitrix\Crm\Communication;

/**
 * Class Call
 *
 * @package Bitrix\Crm\Tracking\Channel
 */
class Call extends Base
{
	protected $code = self::Call;

	/**
	 * Call constructor.
	 *
	 * @param string $phoneNumber Phone number.
	 */
	public function __construct($phoneNumber)
	{
		$this->value = Communication\Normalizer::normalizePhone($phoneNumber);
	}

	/**
	 * Get source ID.
	 *
	 * @return int|null
	 */
	public function getSourceId()
	{
		return Tracking\Internals\SourceTable::getSourceByPhoneNumber($this->getValue());
	}

	/**
	 * Return true if supports detecting trace.
	 *
	 * @return bool
	 */
	public function isSupportDetecting()
	{
		$phones = array_column(Tracking\Provider::getReadySources(), 'PHONE');
		return in_array($this->getValue(), $phones);
	}
}