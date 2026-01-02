<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Access\Context;

trait AccessContextTrait
{
	protected ?Context $context = null;

	public function getAccessContext(): Context
	{
		return $this->context ?? new Context(0);
	}

	public function setAccessContext(Context $context): static
	{
		$this->context = $context;

		return $this;
	}
}