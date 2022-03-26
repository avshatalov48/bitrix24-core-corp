<?php
namespace Bitrix\Intranet\UI\LeftMenu\MenuItem;

use \Bitrix\Main;
use \Bitrix\Intranet\UI\LeftMenu;

// DTO like
abstract class Item extends Basic
{
	const CODE = 'abstract';

	protected $storage = [];

	public function init(array $itemData): void
	{
		if (isset($itemData['COUNTER_ID']) && $itemData['COUNTER_ID'])
		{
			$this->COUNTER_ID = $itemData['COUNTER_ID'];
			// this is legacy. TODO: remove it
			$this->makeMagicFunctionsWithCounter($itemData['COUNTER_ID']);
		}
	}

	abstract protected function adjustData($data, LeftMenu\User $user): array;

	public function addStorage(Item $item)
	{
		$this->storage[] = $item;
	}

	public function getStorage(): array
	{
		return $this->storage;
	}

	private function makeMagicFunctionsWithCounter($counterId)
	{
		if (is_string($counterId)
			&& preg_match('~^crm_~i', $counterId)
			&& Main\Loader::includeModule('crm')
		)
		{
			\Bitrix\Crm\Counter\EntityCounterManager::prepareValue($counterId);
		}
	}
}
