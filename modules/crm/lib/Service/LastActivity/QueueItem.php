<?php

namespace Bitrix\Crm\Service\LastActivity;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Type\DateTime;

/**
 * @internal
 */
final class QueueItem
{
	private int $userId;
	private ?DateTime $time;

	public function __construct()
	{
		$this->userId = Container::getInstance()->getContext()->getUserId();
	}

	public function setUserCurrent(): self
	{
		$this->userId = Container::getInstance()->getContext()->getUserId();

		return $this;
	}

	public function setSpecificUser(int $userId): self
	{
		$this->userId = $userId;

		return $this;
	}

	public function getUserId(): int
	{
		// why don't we use Container::getInstance()->getContext()->getUserId() here?
		// because global user context can change over time, like when agents start.
		// therefore, we need to capture the user id at the moment of creating the payload

		return $this->userId;
	}

	public function setTimeNow(): self
	{
		$this->time = null;

		return $this;
	}

	public function setSpecificTime(DateTime $time): self
	{
		$this->time = $time;

		return $this;
	}

	public function getTime(): DateTime
	{
		return $this->time ?? new DateTime();
	}
}
