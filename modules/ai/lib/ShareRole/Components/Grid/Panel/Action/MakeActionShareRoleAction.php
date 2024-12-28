<?php

namespace Bitrix\AI\ShareRole\Components\Grid\Panel\Action;

use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\Snippet;

class MakeActionShareRoleAction extends BaseGridAction
{

	/**
	 * @inheritDoc
	 */
	public static function getId(): string
	{
		return 'confirm-action';
	}

	public function getControl(): ?array
	{
		$snippet = new Snippet();

		return $snippet->getApplyButton([
			'ONCHANGE' => [
				[
					'ACTION' => Actions::CALLBACK,
					'DATA' => [
						['JS' => "BX.AI.ShareRole.Library.Controller.applyMultipleAction('user_roles_library_3')"],
					],
				],
			],
		]);
	}
}
