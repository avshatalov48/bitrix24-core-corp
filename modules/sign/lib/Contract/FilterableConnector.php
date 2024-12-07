<?php

namespace Bitrix\Sign\Contract;

interface FilterableConnector
{
	public function setExcludeFilterRule(?\Closure $rule): static;
	public function setIncludeFilterRule(?\Closure $rule): static;
}