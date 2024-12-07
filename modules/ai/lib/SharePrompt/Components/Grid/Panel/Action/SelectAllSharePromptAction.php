<?php

namespace Bitrix\AI\SharePrompt\Components\Grid\Panel\Action;

use Bitrix\Main\Grid\Panel\Snippet;

class SelectAllSharePromptAction extends BaseGridAction
{
	/**
	 * @inheritDoc
	 */
	public static function getId(): string
	{
		return 'select-all';
	}

	/**
	 * @inheritDoc
	 */
	public function getControl(): ?array
	{
		$snippet = new Snippet();

		return $snippet->getForAllCheckbox();
	}
}
