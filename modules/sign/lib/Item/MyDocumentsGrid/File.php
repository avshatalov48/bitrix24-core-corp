<?php

namespace Bitrix\Sign\Item\MyDocumentsGrid;

use Bitrix\Sign\Contract;

class File implements Contract\Item
{
	public function __construct(
		public ?int $entityFileCode,
		public ?string $ext,
		public ?string $url,
	)
	{}
}