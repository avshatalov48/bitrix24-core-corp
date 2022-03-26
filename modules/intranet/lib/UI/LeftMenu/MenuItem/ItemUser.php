<?php
namespace Bitrix\Intranet\UI\LeftMenu\MenuItem;

use \Bitrix\Intranet\UI\LeftMenu;

abstract class ItemUser extends Item
{
	protected $SUB_LINK;
	protected $OPEN_IN_NEW_PAGE;

	public function __construct(array $menuItemData)
	{
		parent::__construct($menuItemData);
		$this->OPEN_IN_NEW_PAGE = isset($menuItemData['NEW_PAGE']) && $menuItemData['NEW_PAGE'] === 'Y';

		if (isset($item['SUB_LINK']) && !empty($item['SUB_LINK']))
		{
			$this->SUB_LINK = $item['SUB_LINK'];
		}
	}

	public function canUserDelete(LeftMenu\User $user): bool
	{
		return true;
	}

	public function adjustData($data, LeftMenu\User $user): array
	{
		$data['TEXT'] = htmlspecialcharsbx($data['TEXT']);
		/* @var $storage ItemUser */
		if ($storage = reset($this->storage))
		{
			$storages = [];
			do
			{
				$storages[] = $storage->getCode();
			} while ($storage = next($this->storage));

			$data['PARAMS']['storage'] = implode(',', $storages);
		}
		$data['OPEN_IN_NEW_PAGE'] = $this->OPEN_IN_NEW_PAGE ? 'Y' : 'N';
		if ($this->SUB_LINK)
		{
			$data['PARAMS']['sub_link'] = $this->SUB_LINK;
		}
		return $data;
	}
}
