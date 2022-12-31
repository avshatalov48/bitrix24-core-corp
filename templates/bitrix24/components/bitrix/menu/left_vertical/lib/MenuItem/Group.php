<?php
namespace Bitrix\Intranet\LeftMenu\MenuItem;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Intranet\LeftMenu;

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
