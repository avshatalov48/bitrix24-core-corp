<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Entity\FieldContentType;
use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Result;

final class ContentTypeId extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		$isContentChanged = $item->isChanged(FieldContentType::compileRegularFieldName($this->getName()));
		// if content field (e.g. COMMENTS) is not changed, leave everything as it is
		if (!$isContentChanged)
		{
			return new Result();
		}

		$context ??= Container::getInstance()->getContext();
		if ($context->getItemOption('PRESERVE_CONTENT_TYPE') === true)
		{
			return new Result();
		}

		$item->set($this->getName(), \CCrmContentType::Html);

		return new Result();
	}
}
