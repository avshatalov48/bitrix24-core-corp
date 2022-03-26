<?php
namespace Bitrix\Intranet\UI\LeftMenu\MenuItem;

use Bitrix\Intranet\UI\LeftMenu;

abstract class Group extends Basic
{
	public function init(array $itemData): void
	{
		$this->COUNTER_ID = $itemData['ID'];
	}

	protected function adjustData($data, LeftMenu\User $user): array
	{
		$data['IS_GROUP'] = 'Y';
		return $data;
	}
}
