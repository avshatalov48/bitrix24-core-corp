<?php

namespace Bitrix\AI\ShareRole\Components\Grid\Row\Action;

use Bitrix\Main\Grid\Row\Action\DataProvider;

class ShareRoleProvider extends DataProvider
{
	/**
	 * @inheritDoc
	 */
	public function prepareActions(): array
	{
		return [
			new EditShareRoleAction(),
			new ToggleActiveAction(),
			new ToggleDeletedAction(),
			new ToggleFavouriteAction(),
		];
	}
}
