<?php

namespace Bitrix\BIConnector\Aggregate;

class ConcatState extends BaseState
{
	protected $state = [];
	protected $delimiter = '';
	public function __construct($delimiter)
	{
		$this->delimiter = $delimiter;
	}
	public function updateState($id, $value)
	{
		if ($value !== null)
		{
			$this->state[$id] = $value;
		}
	}
	public function output()
	{
		return $this->state ? implode($this->delimiter, $this->state) : null;
	}
}
