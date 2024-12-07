<?php

namespace Bitrix\AI\SharePrompt\Components\Grid\Panel\Action;

use Bitrix\Main\Grid\Panel\Action\DataProvider;

class SharePromptPanelProvider extends DataProvider
{
	/**
	 * @inheritDoc
	 */
	public function prepareActions(): array
	{
		return [
			new SelectActionSharePromptAction(),
			new MakeActionSharePromptAction(),
//			new SelectAllSharePromptAction(),
			new ActivateSharePromptsAction(),
			new DeactivateSharePromptsAction(),
			new ShowForMeSharePromptsAction(),
			new HideFromMeSharePromptsAction(),
		];
	}
}
