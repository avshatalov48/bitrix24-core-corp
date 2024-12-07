<?php

namespace Bitrix\Tasks\CheckList\Decorator;

use Bitrix\Tasks\CheckList\CheckListFacade;

abstract class CheckListDecorator extends CheckListFacade
{
	public function __construct(
		protected CheckListFacade $source,
		protected int $userId
	)
	{
		$this->init();
	}

	protected function init(): void
	{

	}
}