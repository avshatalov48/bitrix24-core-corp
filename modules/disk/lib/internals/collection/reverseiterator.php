<?php

namespace Bitrix\Disk\Internals\Collection;

class ReverseIterator implements \Iterator
{
	/**
	 * @var int
	 */
	protected $i;

	/**
	 * @var FixedArray
	 */
	protected $fixedArray;

	public function __construct(FixedArray $fixedArray)
	{
		$this->fixedArray = $fixedArray;
	}

	public function current()
	{
		return $this->fixedArray[$this->i];
	}

	public function key()
	{
		return $this->i;
	}

	public function next()
	{
		$this->i--;
	}

	public function rewind()
	{
		$size = $this->fixedArray->getSize();
		if($size === 0)
		{
			$this->i = -1;
		}
		else
		{
			$this->i = $size - 1;
		}
	}

	public function valid()
	{
		return $this->i >= 0;
	}
}