<?php

namespace Bitrix\Mobile;

abstract class Query
{
	abstract public function execute();

	public function __invoke()
	{
		return $this->execute();
	}
}
