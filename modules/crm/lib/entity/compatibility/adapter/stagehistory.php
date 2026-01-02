<?php

namespace Bitrix\Crm\Entity\Compatibility\Adapter;

use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\Entity\Compatibility\Adapter;
use Bitrix\Crm\History\StageHistory\AbstractStageHistory;
use Bitrix\Crm\History\StageHistoryWithSupposed\AbstractStageHistoryWithSupposed;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Result;

/**
 * @internal
 */
final class StageHistory extends Adapter
{
	/** @var Array<int, array> */
	private array $previousEntities = [];

	public function __construct(
		private readonly Factory $factory,
		private readonly AbstractStageHistory $stageHistory,
		private readonly AbstractStageHistoryWithSupposed $supposedHistory,
	)
	{
	}

	public function setPreviousFields(int $id, array $previousFields): self
	{
		$this->previousEntities[$id] = $previousFields;

		return $this;
	}

	/**
	 * @inheritDoc
	 */
	protected function doPerformAdd(array &$fields, array $compatibleOptions): Result
	{
		$commonCurrent = $this->mapEntityFieldsToCommon($fields);

		$diff = ComparerBase::compareEntityFields([], $commonCurrent);

		$result = $this->stageHistory->registerItemAdd($diff);
		$supposedResult = $this->supposedHistory->registerItemAdd($diff);
		if (!$supposedResult->isSuccess())
		{
			$result->addErrors($supposedResult->getErrors());
		}

		return $result;
	}

	/**
	 * @inheritDoc
	 */
	protected function doPerformUpdate(int $id, array &$fields, array $compatibleOptions): Result
	{
		$commonPrevious = $this->mapEntityFieldsToCommon($this->previousEntities[$id] ?? []);
		$commonCurrent = $this->mapEntityFieldsToCommon($fields);

		$diff = ComparerBase::compareEntityFields($commonPrevious, $commonCurrent);

		$result = $this->stageHistory->registerItemUpdate($diff);
		$supposedResult = $this->supposedHistory->registerItemUpdate($diff);
		if (!$supposedResult->isSuccess())
		{
			$result->addErrors($supposedResult->getErrors());
		}

		return $result;
	}

	private function mapEntityFieldsToCommon(array $fields): array
	{
		// super mega hack to handle not only field names, but dates, and other specific stuff
		$common = $this->factory->createItem()->setFromCompatibleData($fields)->getData();

		$providedFieldNames = array_keys($fields);
		$commonProvidedFieldNames = $this->getCommonFieldNames($providedFieldNames);

		$commonFiltered = array_intersect_key($common, array_flip($commonProvidedFieldNames));

		// setFromCompatibleData doesn't allow setting ID, it will always be 0
		unset($commonFiltered[Item::FIELD_NAME_ID]);
		// map it manually if it was provided
		if (
			isset($fields[Item::FIELD_NAME_ID])
			&& is_numeric($fields[Item::FIELD_NAME_ID])
			&& (int)$fields[Item::FIELD_NAME_ID] > 0
		)
		{
			$commonFiltered[Item::FIELD_NAME_ID] = (int)$fields[Item::FIELD_NAME_ID];
		}

		return $commonFiltered;
	}

	private function getCommonFieldNames(array $entityFieldNames): array
	{
		return array_map($this->factory->getCommonFieldNameByMap(...), $entityFieldNames);
	}

	/**
	 * @inheritDoc
	 */
	protected function doPerformDelete(int $id, array $compatibleOptions): Result
	{
		$result = $this->stageHistory->registerItemDelete($id);
		$supposedResult = $this->supposedHistory->registerItemDelete($id);
		if (!$supposedResult->isSuccess())
		{
			$result->addErrors($supposedResult->getErrors());
		}

		return $result;
	}
}
