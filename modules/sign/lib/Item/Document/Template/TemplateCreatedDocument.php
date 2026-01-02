<?php

namespace Bitrix\Sign\Item\Document\Template;

use Bitrix\Sign\Contract\Item;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Document\Template;

class TemplateCreatedDocument implements Item
{
	public function __construct(
		public readonly Template $template,
		public readonly Document $document,
	) {}
}