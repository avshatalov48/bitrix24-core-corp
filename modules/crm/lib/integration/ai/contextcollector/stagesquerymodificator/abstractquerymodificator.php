<?php

namespace Bitrix\Crm\Integration\AI\ContextCollector\StagesQueryModificator;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\ORM\Query\Query;

abstract class AbstractQueryModificator
{
	protected string $stageFieldName;

	public function __construct(
		protected readonly Factory $factory,
	)
	{
		$this->stageFieldName = $this->factory->getEntityFieldNameByMap(Item::FIELD_NAME_STAGE_ID);
	}

	abstract public function modify(Query $query): Query;

	abstract public function transferFormattedValues(array $fetchAllResult, ExecutorResultValues $values): void;

	abstract public function fillDefaultValue(ExecutorResultValues $values): void;
}
