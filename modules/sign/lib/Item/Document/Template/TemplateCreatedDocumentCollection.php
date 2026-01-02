<?php

namespace Bitrix\Sign\Item\Document\Template;

use Bitrix\Sign\Item\Collection;

/**
 * @extends Collection<TemplateCreatedDocument>
 */
class TemplateCreatedDocumentCollection extends Collection
{
	protected function getItemClassName(): string
	{
		return TemplateCreatedDocument::class;
	}
}