<?php

namespace Bitrix\Crm\Integration\AI\ContextCollector\StagesQueryModificator;

use Bitrix\Crm\Item;
use Bitrix\Main\ORM\Query\Query;
use CCrmCurrency;
use CCrmOwnerType;

final class ItemsSumQueryModificator extends AbstractQueryModificator
{
	private const RESULT_FIELD = 'ITEMS_SUM';
	private const DEFAULT_ITEMS_SUM = 0;

	public function modify(Query $query): Query
	{
		if (!$this->factory->isStagesSupported())
		{
			return $query;
		}

		$totalSumField = $this->getTotalSumFieldName();

		return $query
			->addSelect($this->stageFieldName)
			->addSelect(Query::expr()->sum($totalSumField), self::RESULT_FIELD)
			->addGroup($this->stageFieldName);
	}

	public function transferFormattedValues(array $fetchAllResult, ExecutorResultValues $values): void
	{
		$currency = $this->getCurrency();

		foreach ($fetchAllResult as $item)
		{
			$stageId = $item[$this->stageFieldName] ?? null;
			if ($stageId === null)
			{
				continue;
			}

			$sum = (float)($item[self::RESULT_FIELD] ?? null);
			$values->addItemsSum($stageId, new ItemsSum($sum, $currency));
		}
	}

	private function getTotalSumFieldName(): string
	{
		return match ($this->factory->getEntityTypeId()) {
			CCrmOwnerType::Invoice => 'PRICE',
			default => Item::FIELD_NAME_OPPORTUNITY_ACCOUNT,
		};
	}

	private function getCurrency(): ?string
	{
		return match ($this->factory->getEntityTypeId()) {
			CCrmOwnerType::Invoice => CCrmCurrency::getInvoiceDefault(),
			default => CCrmCurrency::GetAccountCurrencyID(),
		};
	}

	public function fillDefaultValue(ExecutorResultValues $values): void
	{
		$values->setDefaultItemsSum(
			new ItemsSum(self::DEFAULT_ITEMS_SUM, $this->getCurrency()),
		);
	}
}
