<?php
namespace Bitrix\Intranet\LeftMenu\MenuItem;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Intranet\LeftMenu;

class ItemSystem extends Item
{
	protected $params = [];

	public function getCode(): string
	{
		return 'default';
	}

	public function setParams(array $params)
	{
		$this->params = $params;
	}

	final public function canUserDelete(LeftMenu\User $user): bool
	{
		return false;
	}

	protected function adjustData($data, LeftMenu\User $user): array
	{
		if ($this->params)
		{
			$data['PARAMS'] = array_merge($this->params, $data['PARAMS']);
		}
		return $data;
	}
}
