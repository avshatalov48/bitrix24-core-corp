<?php

namespace Bitrix\Tasks\Integration\CRM\Fields;

class Collection implements \IteratorAggregate
{
	/** @var Crm[]  */
	private array $fields;

	public function __construct(Crm ...$fields)
	{
		$this->fields = $fields;
	}

	public function getIterator(): \ArrayIterator
	{
		return new \ArrayIterator($this->fields);
	}
}