<?php declare(strict_types=1);

namespace Bitrix\ImOpenLines\Services;

use Bitrix\Main;
use Bitrix\ImOpenLines;

/**
 * Message service.
 *
 * @package Bitrix\ImOpenLines\Services
 */
class Message
{
	/** @var bool */
	private $isEnabled;

	public function __construct()
	{
		$this->isEnabled = Main\Loader::includeModule('imopenlines');
	}

	/**
	 * Sends im message.
	 *
	 * @param array $fields Message parameters.
	 *
	 * @return int|false
	 */
	public function addMessage(array $fields)
	{
		if ($this->isEnabled)
		{
			return ImOpenLines\Im::addMessage($fields);
		}

		return false;
	}
}
