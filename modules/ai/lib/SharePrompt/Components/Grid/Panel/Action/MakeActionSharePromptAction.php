<?php

namespace Bitrix\AI\SharePrompt\Components\Grid\Panel\Action;

use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\Snippet;

class MakeActionSharePromptAction extends BaseGridAction
{
	/**
	 * @inheritDoc
	 */
	public static function getId(): string
	{
		return 'confirm-action';
	}

	/**
	 * @inheritDoc
	 */
	public function getControl(): ?array
	{
		$snippet = new Snippet();

		return $snippet->getApplyButton([
			'ONCHANGE' => [
				[
					'ACTION' => Actions::CALLBACK,
					'DATA' => [
						['JS' => "BX.AI.SharePrompt.Library.Controller.applyMultipleAction('user_prompts_library_3')"],
					],
				],
			],
		]);
	}
}
