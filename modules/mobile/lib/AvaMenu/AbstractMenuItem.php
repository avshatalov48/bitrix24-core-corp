<?php

namespace Bitrix\Mobile\AvaMenu;

use Bitrix\Mobile\Context;

abstract class AbstractMenuItem implements MenuItem
{
	protected Context $context;

	public function separatorBefore(): bool
	{
		return false;
	}

	public function separatorAfter(): bool
	{
		return false;
	}

	public function __construct(Context $context)
	{
		$this->context = $context;
	}
}