<?php

namespace Bitrix\Crm\RepeatSale\DataCollector\Mapper;

use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Item;
use Bitrix\Crm\Item\Deal;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\StatusTable;

final class SystemFieldsMapper extends AbstractFieldsMapper
{
	public const TYPE_ID = 'SF';

	// @todo:: add support other entity types
	private array $map = [
		Item::FIELD_NAME_ID => ['deal_id', null],
		Item::FIELD_NAME_TITLE => ['deal_title', 'normalizeText'],
		Item::FIELD_NAME_CATEGORY_ID => ['pipeline', 'normalizeCategory'],
		Item::FIELD_NAME_STAGE_ID => ['deal_stage', 'normalizeStage'],
		Item::FIELD_NAME_STAGE_SEMANTIC_ID => ['deal_stage_group', 'normalizeStageSemantic'],
		Item::FIELD_NAME_PRODUCTS => ['products', 'normalizeProducts'],
		Item::FIELD_NAME_CURRENCY_ID => ['currency', null],
		Item::FIELD_NAME_OPPORTUNITY => ['amount', null],
		'DATE_CREATE' => ['deal_created_at', null],
		Item::FIELD_NAME_CLOSE_DATE => ['deal_closed_at', null],
		Item::FIELD_NAME_CLOSED => ['deal_is_closed', 'normalizeBoolean'],
		Item::FIELD_NAME_TYPE_ID => ['deal_type', 'normalizeType'],
		Item::FIELD_NAME_SOURCE_ID => ['deal_source', 'normalizeSource'],
		Item::FIELD_NAME_SOURCE_DESCRIPTION => ['deal_source_description', 'normalizeText'],
		Item::FIELD_NAME_COMMENTS => ['comments', 'normalizeText'],
		Deal::FIELD_NAME_ADDITIONAL_INFO => ['additional_info', 'normalizeText'],
	];

	public function map(array $item): array
	{
		$fields = $this->filterFields($item);
		if (empty($fields))
		{
			return [];
		}

		$result = [];
		foreach ($fields as $key => $value)
		{
			$mapped = $this->map[$key] ?? null;
			if (isset($mapped) && is_array($mapped))
			{
				[$normalizedName, $normalizeMethod] = $mapped;

				$result[$normalizedName] = isset($normalizeMethod) && is_callable([$this, $normalizeMethod])
					? $this->$normalizeMethod($value)
					: $value
				;
			}
		}

		return $result;
	}

	// region normalize data

	/** @noinspection PhpUnusedPrivateMethodInspection */
	private function normalizeProducts(?array $input): array
	{
		if (empty($input))
		{
			return [];
		}

		return array_filter(array_column($input, 'PRODUCT_NAME'));
	}

	/** @noinspection PhpUnusedPrivateMethodInspection */
	private function normalizeCategory(?int $categoryId): string
	{
		if ($categoryId === null)
		{
			return '';
		}

		return DealCategory::getName($categoryId);
	}

	/** @noinspection PhpUnusedPrivateMethodInspection */
	private function normalizeStage(?string $stageId): string
	{
		if (empty($stageId))
		{
			return '';
		}

		return DealCategory::getStageName($stageId);
	}

	/** @noinspection PhpUnusedPrivateMethodInspection */
	private function normalizeStageSemantic(?string $stageSemanticId): string
	{
		if (empty($stageSemanticId))
		{
			return '';
		}

		$statusSemantics = PhaseSemantics::getAllDescriptions();

		return $statusSemantics[$stageSemanticId] ?? '';
	}

	/** @noinspection PhpUnusedPrivateMethodInspection */
	private function normalizeSource(string $sourceId): string
	{
		$list = StatusTable::getStatusesList(StatusTable::ENTITY_ID_SOURCE);

		return $list[$sourceId] ?? '';
	}

	/** @noinspection PhpUnusedPrivateMethodInspection */
	private function normalizeType(string $typeId): string
	{
		$list = StatusTable::getStatusesList(StatusTable::ENTITY_ID_DEAL_TYPE);

		return $list[$typeId] ?? '';
	}
	//endregion
}
