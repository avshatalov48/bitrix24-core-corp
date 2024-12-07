<?php

namespace Bitrix\Tasks\Flow\Search\Conversion;

use Bitrix\Tasks\Internals\Task\Search\Conversion\UserTrait;

abstract class AbstractConverter
{
	use UserTrait;

	protected array $flow;

	public function __construct(array $flow)
	{
		$this->flow = $flow;
	}

	abstract public static function getFieldName(): string;

	public function convert()
	{
		return is_string($this->getFieldValue()) ? $this->getFieldValue() : '';
	}

	public function getFieldValue()
	{
		return $this->flow[static::getFieldName()] ?? '';
	}
}