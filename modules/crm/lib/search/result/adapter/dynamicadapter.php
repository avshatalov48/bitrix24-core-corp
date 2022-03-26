<?php

namespace Bitrix\Crm\Search\Result\Adapter;

use Bitrix\Crm\Item;
use Bitrix\Crm\Search\Result\Adapter;
use Bitrix\Crm\Service\Factory;

class DynamicAdapter extends Adapter
{
	/** @var Factory */
	private $factory;

	public function __construct(Factory $factory)
	{
		$this->factory = $factory;
	}

	protected function getEntityTypeId(): int
	{
		return $this->factory->getEntityTypeId();
	}

	protected function loadItemsByIds(array $ids): array
	{
		$items = $this->factory->getItemsFilteredByPermissions([
			'select' => [
				Item::FIELD_NAME_TITLE,
			],
			'filter' => [
				'@' . Item::FIELD_NAME_ID => $ids,
			],
		]);

		$result = [];
		foreach ($items as $item)
		{
			$result[] = $item->getData();
		}

		return $result;
	}

	protected function prepareTitle(array $item): string
	{
		return $item[Item::FIELD_NAME_TITLE] ?? '';
	}

	protected function prepareSubTitle(array $item): string
	{
		return '';
	}

	protected function areMultifieldsSupported(): bool
	{
		return false;
	}
}
