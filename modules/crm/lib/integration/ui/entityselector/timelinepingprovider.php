<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use Bitrix\Crm\Activity\TodoPingSettingsProvider;
use Bitrix\Crm\Security\EntityAuthorization;
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

		return array_values(
			array_filter(
				TodoPingSettingsProvider::getDefaultOffsetList(),
				static fn($row) => in_array($row['offset'], $offsets, true)
			)
		);
	}

	final public static function getOffsetsByValues(array $values): array
	{
		if (empty($values))
		{
			return [];
		}

		$filtered = array_filter(
			TodoPingSettingsProvider::getDefaultOffsetList(),
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
		foreach (TodoPingSettingsProvider::getDefaultOffsetList() as $row)
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
}
