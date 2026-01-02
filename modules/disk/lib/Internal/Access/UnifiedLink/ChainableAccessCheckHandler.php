<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internal\Access\UnifiedLink;

use Bitrix\Disk\File;

abstract class ChainableAccessCheckHandler implements AccessCheckHandler
{
	protected ?AccessCheckHandler $next = null;

	public function check(File $file, ?UnifiedLinkAccessLevel $previousResult = null): UnifiedLinkAccessLevel
	{
		$result = $this->doCheck($file);
		$result = (isset($previousResult) && $result->value < $previousResult->value)
			? $previousResult
			: $result;

		if ($this->next !== null && !$result->isMax())
		{
			return $this->next->check($file, $result);
		}

		return $result;
	}

	abstract protected function doCheck(File $file): UnifiedLinkAccessLevel;

	public function setNext(AccessCheckHandler $handler): self
	{
		$this->next = $handler;

		return $this;
	}
}
