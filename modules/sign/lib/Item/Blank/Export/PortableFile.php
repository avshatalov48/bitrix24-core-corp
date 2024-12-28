<?php

namespace Bitrix\Sign\Item\Blank\Export;

use Bitrix\Sign\Contract\Item;

class PortableFile implements Item
{
	public function __construct(
		public readonly string $base64Content,
		public readonly string $mimeType,
		public readonly string $name,
	) {}
}