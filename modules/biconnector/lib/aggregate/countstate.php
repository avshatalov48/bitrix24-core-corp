<?php

namespace Bitrix\BIConnector\Aggregate;

class CountState extends BaseState
{
	protected $state = null;
	protected $distinct = '';
	public function __construct($distinct)
	{
		$this->distinct = (bool)$distinct;
		$this->state = $this->distinct ? [] : 0;
	}
	public function updateState($id, $value)
	{
		if ($value !== null)
		{
			if ($this->distinct)
			{
				$this->state[$value] = 1;
			}
			else
			{
				$this->state++;
			}
		}
	}
	public function output()
	{
		return $this->distinct ? count($this->state) : $this->state;
	}
}
