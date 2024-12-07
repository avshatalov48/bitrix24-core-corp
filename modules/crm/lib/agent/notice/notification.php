<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2018 Bitrix
 */

namespace Bitrix\Crm\Agent\Notice;

use Bitrix\Main\Loader;
use CIMNotify;

/**
 * Class Notification
 * @package Bitrix\Sender\Integration\Im
 */
class Notification
{
	protected array $to = [];

	/** @var string|callable $message */
	protected $message;

	public static function canUse(): bool
	{
		if (!Loader::includeModule('im'))
		{
			return false;
		}

		return true;
	}

	public static function create(): static
	{
		return new static();
	}

	public function setTo(array $list): static
	{
		$this->to = $list;

		return $this;
	}

	/**
	 * Add list of recipients.
	 *
	 * @param int[] $list List.
	 * @return $this
	 */
	public function toList(array $list): static
	{
		foreach ($list as $userId)
		{
			$this->addTo($userId);
		}

		return $this;
	}

	public function addTo(int $userId): static
	{
		if (!in_array($userId, $this->to, true))
		{
			$this->to[] = $userId;
		}

		return $this;
	}

	public function withMessage(string|callable $message): static
	{
		$this->message = $message;

		return $this;
	}

	public function send(): void
	{
		if (!static::canUse())
		{
			return;
		}

		if (
			!$this->message
			|| count($this->to) === 0
		)
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

			CIMNotify::Add($fields);
		}
	}
}