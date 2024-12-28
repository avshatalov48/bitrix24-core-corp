<?php

namespace Bitrix\Sign\Item\Blank\Export;

use Bitrix\Sign\Item\Collection;

/**
 * @extends Collection<PortableBlock>
 */
class PortableBlockCollection extends Collection implements \JsonSerializable
{

	protected function getItemClassName(): string
	{
		return PortableBlock::class;
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}