<?php

namespace Bitrix\AI\SharePrompt\Components\Grid\Panel\Action;

use Bitrix\Main\Grid\Panel\Types;
use Bitrix\Main\Localization\Loc;

class SelectActionSharePromptAction extends BaseGridAction
{
	/**
	 * @inheritDoc
	 */
	public static function getId(): string
	{
		return 'select-action';
	}

	/**
	 * @inheritDoc
	 */
	public function getControl(): ?array
	{
		return array(
			"TYPE" => Types::DROPDOWN,
			"ID" => "action-menu",
			"NAME" => "action-menu",
			"ITEMS" => [
				[
					"NAME" => Loc::getMessage('PROMPT_LIBRARY_GRID_GROUP_ACTION_SELECT_ACTION'),
					"VALUE" => "select-action",
				],
				[
					"NAME" => Loc::getMessage('PROMPT_LIBRARY_GRID_GROUP_ACTION_ACTIVATE'),
					"VALUE" => "multiple-activate",
				],
				[
					"NAME" => Loc::getMessage('PROMPT_LIBRARY_GRID_GROUP_ACTION_DEACTIVATE'),
					"VALUE" => "multiple-deactivate",
				],
				[
					"NAME" => Loc::getMessage('PROMPT_LIBRARY_GRID_GROUP_ACTION_SHOW_FOR_ME'),
					"VALUE" => "multiple-show-for-me",
				],
				[
					"NAME" => Loc::getMessage('PROMPT_LIBRARY_GRID_GROUP_ACTION_NOT_SHOW_FOR_ME'),
					"VALUE" => "multiple-hide-from-me",
				]
			]
		);
	}
}
