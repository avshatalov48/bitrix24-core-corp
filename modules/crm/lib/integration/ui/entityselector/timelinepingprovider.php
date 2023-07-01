<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Main\Localization\Loc;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;

class TimelinePingProvider extends BaseProvider
{
	public const ENTITY_ID = 'timeline_ping';

	public function __construct(array $options)
	{
		parent::__construct();

		$this->options = $options;
	}

	final public static function getValuesByOffsets(array $offsets): array
	{
		if (empty($offsets))
		{
			return [];
		}

		$items = static::getDefaultItemList();

		return array_values(
			array_filter($items, static fn($row) => in_array($row['offset'], $offsets, true))
		);
	}

	final public static function getOffsetsByValues(array $values): array
	{
		if (empty($values))
		{
			return [];
		}

		$items = static::getDefaultItemList();

		$filtered = array_filter(
			$items,
			static fn($row) => in_array($row['id'], $values, true)
		);

		return array_column($filtered, 'offset');
	}

	final public function isAvailable(): bool
	{
		return EntityAuthorization::isAuthorized();
	}

	final public function fillDialog(Dialog $dialog): void
	{
		$items = $this->makeItems();

		array_walk(
			$items,
			static function (Item $item) use ($dialog) {
				$dialog->addRecentItem($item);
			}
		);
	}

	final public function getItems(array $ids): array
	{
		return $this->makeItems();
	}

	final public function getSelectedItems(array $ids): array
	{
		return $this->makeItems();
	}

	private function makeItems(): array
	{
		$items = [];
		foreach (static::getDefaultItemList() as $row)
		{
			$items[] = $this->makeItem($row['id'], $row['title'], $row['offset']);
		}

		return $items;
	}

	private function makeItem(string $id, string $title, int $offset): Item
	{
		return new Item([
			'id' => $id,
			'entityId' => static::ENTITY_ID,
			'title' => $title,
			'customData' => [
				'offset' => $offset,
			]
		]);
	}

	private static function getDefaultItemList(): array
	{
		return [
			[
				'id' => 'at the time of the onset',
				'title' => Loc::getMessage('CRM_ENTITY_SELECTOR_PING_TYPE_0_MIN'),
				'offset' => 0,
			],
			[
				'id' => 'in 5 minutes',
				'title' => Loc::getMessage('CRM_ENTITY_SELECTOR_PING_TYPE_5_MIN'),
				'offset' => 5,
			],
			[
				'id' => 'in 15 minutes',
				'title' => Loc::getMessage('CRM_ENTITY_SELECTOR_PING_TYPE_15_MIN'),
				'offset' => 15,
			],
			[
				'id' => 'in 30 minutes',
				'title' => Loc::getMessage('CRM_ENTITY_SELECTOR_PING_TYPE_30_MIN'),
				'offset' => 30,
			],
			[
				'id' => 'in 1 hour',
				'title' => Loc::getMessage('CRM_ENTITY_SELECTOR_PING_TYPE_1_HOUR'),
				'offset' => 60,
			],
			[
				'id' => 'in 3 hours',
				'title' => Loc::getMessage('CRM_ENTITY_SELECTOR_PING_TYPE_3_HOURS'),
				'offset' => 180,
			],
			[
				'id' => 'in 6 hours',
				'title' => Loc::getMessage('CRM_ENTITY_SELECTOR_PING_TYPE_6_HOURS'),
				'offset' => 360,
			],
		];
	}
}
