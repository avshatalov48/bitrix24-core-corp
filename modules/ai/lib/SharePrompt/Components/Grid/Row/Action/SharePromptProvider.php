<?php

namespace Bitrix\AI\SharePrompt\Components\Grid\Row\Action;

use Bitrix\Main\Grid\Row\Action\DataProvider;

class SharePromptProvider extends DataProvider
{
	/**
	 * @inheritDoc
	 */
	public function prepareActions(): array
	{
		return [
			new EditSharePromptAction(),
			new ToggleIsActiveAction(),
			new ToggleIsDeletedAction(),
			new ToggleIsFavouriteAction(),
		];
	}
}
