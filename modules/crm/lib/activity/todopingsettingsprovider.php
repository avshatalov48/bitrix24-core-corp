<?php

namespace Bitrix\Crm\Activity;

use Bitrix\Crm\Activity\Ping\PingSettingsProvider;
use Bitrix\Main\Localization\Loc;
use CCrmOwnerType;
use CUserOptions;

final class TodoPingSettingsProvider extends PingSettingsProvider
{
	private const OPTION_NAME_PREFIX = 'todo_ping_settings';

	public static function getDefaultOffsetList(): array
	{
		return array_merge(
			parent::getDefaultOffsetList(),
			[
				[
					'id' => 'in_1_day',
					'title' => Loc::getMessage('CRM_TODO_ACTIVITY_PING_OFFSET_1_DAY'),
					'offset' => 1440,
				],

			],
		);
	}

	private function isAllCategoriesSelected(): bool
	{
		return $this->categoryId === -1
			|| (CCrmOwnerType::isPossibleDynamicTypeId($this->entityTypeId) && $this->categoryId === 0);
	}

	public function fetchAll(): array
	{
		if ($this->isAllCategoriesSelected())
		{
			return [];
		}

		return [
			'optionName' => $this->getOptionName(),
			'offsetList' => self::getDefaultOffsetList(),
			'currentOffsets' => $this->getCurrentOffsets(),
		];
	}

	public function fetchSelectedValues(): array
	{
		if ($this->isAllCategoriesSelected())
		{
			return [];
		}

		return $this->getCurrentOffsets();
	}

	/**
	 * Get data for crm.field.ping-selector component
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
		static $values = [];
		if (!isset($values[$this->getOptionName()]))
		{
			$values[$this->getOptionName()] = CUserOptions::GetOption('crm', $this->getOptionName(), '');
		}

		$value = $values[$this->getOptionName()];

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
