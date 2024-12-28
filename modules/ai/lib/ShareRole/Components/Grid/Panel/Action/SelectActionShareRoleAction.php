<?php

namespace Bitrix\AI\ShareRole\Components\Grid\Panel\Action;

use Bitrix\Main\Grid\Panel\Types;
use Bitrix\Main\Localization\Loc;

class SelectActionShareRoleAction extends BaseGridAction
{

	/**
	 * @inheritDoc
	 */
	public static function getId(): string
	{
		return 'select_action';
	}

	/**
	 * @inheritDoc
	 */
	public function getControl(): ?array
	{
		return [
			'TYPE' => Types::DROPDOWN,
			'ID' => 'action-menu',
			'NAME' => 'action-menu',
			'ITEMS' => [
				[
					'NAME' => Loc::getMessage('ROLE_LIBRARY_GRID_GROUP_ACTION_SELECT_ACTION'),
					'VALUE' => 'select-action',
				],
				[
					'NAME' => Loc::getMessage('ROLE_LIBRARY_GRID_GROUP_ACTION_ACTIVATE'),
					'VALUE' => 'multiple-activate',
				],
				[
					'NAME' => Loc::getMessage('ROLE_LIBRARY_GRID_GROUP_ACTION_DEACTIVATE'),
					'VALUE' => 'multiple-deactivate',
				],
				[
					'NAME' => Loc::getMessage('ROLE_LIBRARY_GRID_GROUP_ACTION_SHOW_FOR_ME'),
					'VALUE' => 'multiple-show-for-me',
				],
				[
					'NAME' => Loc::getMessage('ROLE_LIBRARY_GRID_GROUP_ACTION_NOT_SHOW_FOR_ME'),
					'VALUE' => 'multiple-hide-from-me',
				]
			]
		];
	}
}