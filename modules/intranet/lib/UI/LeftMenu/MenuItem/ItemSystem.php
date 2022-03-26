<?php
namespace Bitrix\Intranet\UI\LeftMenu\MenuItem;

use \Bitrix\Intranet\UI\LeftMenu;

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
