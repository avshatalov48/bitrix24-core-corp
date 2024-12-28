<?php

namespace Bitrix\Sign\Item\Blank\Export;

use Bitrix\Sign\Contract\Item;
use Bitrix\Sign\Type\Document\InitiatedByType;

class PortableBlank implements Item
{
	public function __construct(
		public string $title,
		public readonly string $scenario,
		public readonly bool $isForTemplate,
		public readonly InitiatedByType $initiatedByType,
		public readonly PortableBlockCollection $blocks,
		public readonly PortableFileCollection $files,
		public readonly PortableFieldCollection $fields,
	) {}
}