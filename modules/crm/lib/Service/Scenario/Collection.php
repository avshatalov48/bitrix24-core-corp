<?php

namespace Bitrix\Crm\Service\Scenario;

use Bitrix\Main\Result;
use Bitrix\Crm\Service\Scenario;

class Collection implements \Iterator, \Countable
{
	/** @var Scenario[] */
	protected $scenarios;
	protected $result;

	public function __construct(array $scenarios)
	{
		$this->scenarios = $scenarios;
		$this->result = new Result();
	}

	public function add(Scenario $scenario): self
	{
		$this->scenarios[] = $scenario;

		return $this;
	}

	public function playAll(): Result
	{
		$this->rewind();
		foreach($this as $scenario)
		{
			$result = $scenario->play();
			if(!$result->isSuccess())
			{
				$this->result->addErrors($result->getErrors());
				break;
			}

			$this->result->setData(array_merge($this->result->getData(), $result->getData()));
		}

		return $this->result;
	}

	public function current(): ?Scenario
	{
		return current($this->scenarios);
	}

	public function next(): void
	{
		next($this->scenarios);
	}

	public function key(): int
	{
		return key($this->scenarios);
	}

	public function valid(): bool
	{
		return (key($this->scenarios) !== null);
	}

	public function rewind(): void
	{
		reset($this->scenarios);
	}

	public function count()
	{
		return count($this->scenarios);
	}
}