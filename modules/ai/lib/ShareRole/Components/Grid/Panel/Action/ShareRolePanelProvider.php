<?php

namespace Bitrix\AI\ShareRole\Components\Grid\Panel\Action;

use Bitrix\Main\Grid\Panel\Action\DataProvider;

class ShareRolePanelProvider extends DataProvider
{

	/**
	 * @inheritDoc
	 */
	public function prepareActions(): array
	{
		return [
			new SelectActionShareRoleAction(),
			new MakeActionShareRoleAction(),
			new ActivateShareRolesAction(),
			new DeactivateShareRolesAction(),
			new ShowForMeShareRoleAction(),
			new HideFromMeShareRolesAction(),
		];
	}
}
