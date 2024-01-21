<?php

namespace Bitrix\Crm\Activity;

use Bitrix\Main\Localization\Loc;
use CCrmOwnerType;
use CUserOptions;

final class TodoPingSettingsProvider
{
	public const DEFAULT_OFFSETS = [0, 15];
	private const OPTION_NAME_PREFIX = 'todo_ping_settings';

	private int $entityTypeId;
	private int $categoryId;

	public function __construct(int $entityTypeId, int $categoryId = 0)
	{
		$this->entityTypeId = $entityTypeId;
		$this->categoryId = $categoryId;
	}

	public static function getDefaultOffsetList(): array
	{
		return [
			[
				'id' => 'at_the_time_of_the_onset',
				'title' => Loc::getMessage('CRM_ACTIVITY_TODO_PING_OFFSET_0_MIN'),
				'offset' => 0,
			],
			[
				'id' => 'in_15_minutes',
				'title' => Loc::getMessage('CRM_ACTIVITY_TODO_PING_OFFSET_15_MIN'),
				'offset' => 15,
			],
			[
				'id' => 'in_30_minutes',
				'title' => Loc::getMessage('CRM_ACTIVITY_TODO_PING_OFFSET_30_MIN'),
				'offset' => 30,
			],
			[
				'id' => 'in_1_hour',
				'title' => Loc::getMessage('CRM_ACTIVITY_TODO_PING_OFFSET_1_HOUR'),
				'offset' => 60,
			],
			[
				'id' => 'in_2_hours',
				'title' => Loc::getMessage('CRM_ACTIVITY_TODO_PING_OFFSET_2_HOURS'),
				'offset' => 120,
			],
			[
				'id' => 'in_1_day',
				'title' => Loc::getMessage('CRM_ACTIVITY_TODO_PING_OFFSET_1_DAY'),
				'offset' => 1440,
			],
		];
	}

	public static function filterOffsets(array $offsets): array
	{
		$allowedOffsets = array_column(self::getDefaultOffsetList(), 'offset');

		return array_unique(array_intersect($offsets, $allowedOffsets));
	}

	public static function getValuesByOffsets(array $offsets): array
	{
		if (empty($offsets))
		{
			return [];
		}

		return array_values(
			array_filter(
				self::getDefaultOffsetList(),
				static fn($row) => in_array($row['offset'], $offsets)
			)
		);
	}

	public static function getOffsetsByValues(array $values): array
	{
		if (empty($values))
		{
			return [];
		}

		$filtered = array_filter(
			self::getDefaultOffsetList(),
			static fn($row) => in_array($row['id'], $values)
		);

		return array_column($filtered, 'offset');
	}

	public function fetchAll(): array
	{
		$isAllCategoriesSelected = $this->categoryId === -1
			|| (CCrmOwnerType::isPossibleDynamicTypeId($this->entityTypeId) && $this->categoryId === 0);

		if ($isAllCategoriesSelected)
		{
			return [];
		}

		return [
			'optionName' => $this->getOptionName(),
			'offsetList' => self::getDefaultOffsetList(),
			'currentOffsets' => $this->getCurrentOffsets(),
		];
	}

	/**
	 * Get data for crm.field.item-selector component
	 *
	 * @return array
	 */
	public function fetchForJsComponent(): array
	{
		$settings = $this->fetchAll();
		if (empty($settings))
		{
			return [];
		}

		return [
			'valuesList' => array_map(
				static fn($item) => ['id' => (string)$item['offset'], 'title' => $item['title']],
				$settings['offsetList']
			),
			'selectedValues' => $settings['currentOffsets'],
		];
	}

	public function getCurrentOffsets(): array
	{
		$value = CUserOptions::GetOption('crm', $this->getOptionName(), '');
		if (!isset($value['offsets']))
		{
			return self::DEFAULT_OFFSETS;
		}

		$offsets = explode(',', (string)$value['offsets']);
		if (empty($offsets))
		{
			return self::DEFAULT_OFFSETS;
		}

		return self::filterOffsets($offsets);
	}

	private function getOptionName(): string
	{
		return self::OPTION_NAME_PREFIX
			. '_'
			. mb_strtolower(CCrmOwnerType::ResolveName($this->entityTypeId))
			. '_c'
			. $this->categoryId
		;
	}
}
