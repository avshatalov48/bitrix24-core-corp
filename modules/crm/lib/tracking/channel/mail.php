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
 * Class Mail
 *
 * @package Bitrix\Crm\Tracking\Channel
 */
class Mail extends Base
{
	protected $code = self::Mail;

	/**
	 * Mail constructor.
	 *
	 * @param string $emailTo Email to.
	 */
	public function __construct($emailTo)
	{
		$this->value = Communication\Normalizer::normalizeEmail($emailTo);
	}

	/**
	 * Get source ID.
	 *
	 * @return int|null
	 */
	public function getSourceId()
	{
		return Tracking\Internals\SourceTable::getSourceByEmail($this->value);
	}
}