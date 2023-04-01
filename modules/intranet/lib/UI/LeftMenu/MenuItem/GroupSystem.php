<?php
namespace Bitrix\Intranet\UI\LeftMenu\MenuItem;

use \Bitrix\Intranet\UI\LeftMenu;

class GroupSystem extends Group
{
	private $defaultOptions = [];

	public function getCode(): string
	{
		return 'system_group';
	}

	public function setDefaultOptions(array $options)
	{
		$this->defaultOptions = $options;

	}

	protected function getCollapseMode($itemId): ?string
	{
		static $saveData;
		if (!$saveData)
		{
			$saveData = \CUserOptions::GetOption('intranet', 'left_menu_groups_' . $this->siteId);
		}

		if (is_array($saveData) && array_key_exists($itemId, $saveData))
		{
			return $saveData[$itemId] === 'collapsed' ? 'collapsed' : 'expanded';
		}
		return null;
	}

	public function canUserDelete(LeftMenu\User $user): bool
	{
		return false;
	}

	protected function adjustData($data, LeftMenu\User $user): array
	{
		$collapseMode = $this->getCollapseMode($this->getId());
		if ($collapseMode === null)
		{
			$collapseMode = $this->defaultOptions['collapsed_mode'] ?? null === 'expanded' ? 'expanded' : 'collapsed';
		}

		$data['PARAMS']['collapse_mode'] = $collapseMode;

		return parent::adjustData($data, $user);
	}
}
