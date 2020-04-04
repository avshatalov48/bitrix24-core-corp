<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2018 Bitrix
 */

namespace Bitrix\Crm\Agent\Notice;

use Bitrix\Main\Loader;

/**
 * Class Notification
 * @package Bitrix\Sender\Integration\Im
 */
class Notification
{
	/** @var array $to */
	protected $to = array();

	/** @var string $message */
	protected $message;

	/**
	 * Can use.
	 *
	 * @return bool|null
	 */
	public static function canUse()
	{
		if (!Loader::includeModule('im'))
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Create.
	 *
	 * @return static
	 */
	public static function create()
	{
		return new static();
	}

	/**
	 * Set to.
	 *
	 * @param integer[] $list List.
	 * @return $this
	 */
	public function setTo(array $list)
	{
		$this->to = $list;
		return $this;
	}

	/**
	 * Add list of recipients.
	 *
	 * @param integer[] $list List.
	 * @return $this
	 */
	public function toList(array $list)
	{
		foreach ($list as $userId)
		{
			$this->addTo($userId);
		}

		return $this;
	}

	/**
	 * Add to.
	 *
	 * @param integer $userId User ID.
	 * @return $this
	 */
	public function addTo($userId)
	{
		if (!in_array($userId, $this->to))
		{
			$this->to[] = $userId;
		}

		return $this;
	}

	/**
	 * With message.
	 *
	 * @param string $message Text.
	 * @return $this
	 */
	public function withMessage($message)
	{
		$this->message = $message;
		return $this;
	}

	/**
	 * Send.
	 *
	 * @return void
	 */
	public function send()
	{
		if (!static::canUse())
		{
			return;
		}

		if (count($this->to) === 0 || !$this->message)
		{
			return;
		}

		foreach ($this->to as $userId)
		{
			$fields = array(
				"TO_USER_ID" => $userId,
				"FROM_USER_ID" => 0,
				"NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
				"NOTIFY_MODULE" => "crm",
				//"NOTIFY_EVENT" => $imNotifyEvent,
				//"NOTIFY_TAG" => $notifyTag,
				"NOTIFY_MESSAGE" => $this->message
			);
			\CIMNotify::Add($fields);
		}
	}
}