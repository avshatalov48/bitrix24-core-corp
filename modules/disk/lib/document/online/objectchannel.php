<?php

namespace Bitrix\Disk\Document\Online;

use Bitrix\Pull;

final class ObjectChannel
{
	/** @var int */
	private $objectId;

	public function __construct(int $objectId)
	{
		$this->objectId = $objectId;
	}

	public function getObjectId(): int
	{
		return $this->objectId;
	}

	public function getName(): string
	{
		return "object_{$this->getObjectId()}";
	}

	public function getPullModel(): Pull\Model\Channel
	{
		return Pull\Model\Channel::createWithTag($this->getName());
	}
}