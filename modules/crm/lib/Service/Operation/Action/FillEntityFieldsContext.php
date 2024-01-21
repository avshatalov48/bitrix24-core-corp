<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\FieldContext\ValueFiller;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Operation\Action;
use Bitrix\Main\ORM\Objectify\Values;
use Bitrix\Main\Result;

class FillEntityFieldsContext extends Action
{
	public function process(Item $item): Result
	{
		$result = new Result();

		$itemBeforeSave = $this->getItemBeforeSave();
		if (!$itemBeforeSave)
		{
			return $result;
		}

		$filler = new ValueFiller($item->getEntityTypeId(), $item->getId(), $this->getScope(), $this->getUserId());
		$filler->fill($itemBeforeSave->getData(Values::ACTUAL), $item->getData());

		return $result;
	}

	protected function getScope(): string
	{
		return $this->getContext()->getScope();
	}

	protected function getUserId(): int
	{
		return $this->getContext()->getUserId();
	}
}
