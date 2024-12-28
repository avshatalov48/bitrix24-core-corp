<?php

namespace Bitrix\Sign\Item\Blank\Export;

use Bitrix\Sign\Item\Collection;

/**
 * @extends Collection<PortableFile>
 */
class PortableFileCollection extends Collection implements \JsonSerializable
{
	protected function getItemClassName(): string
	{
		return PortableFile::class;
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}