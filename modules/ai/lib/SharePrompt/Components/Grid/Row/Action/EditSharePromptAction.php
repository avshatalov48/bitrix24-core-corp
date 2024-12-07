<?php

namespace Bitrix\AI\SharePrompt\Components\Grid\Row\Action;

use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Text\HtmlFilter;

class EditSharePromptAction extends BaseGridAction
{
	/**
	 * @inheritDoc
	 */
	public static function getId(): ?string
	{
		return 'edit-prompt';
	}

	/**
	 * @inheritDoc
	 */
	protected function getText(): string
	{
		return Loc::getMessage('PROMPT_LIBRARY_GRID_ACTION_EDIT_PROMPT');
	}

	/**
	 * @param $request
	 *
	 * @return Result|null
	 */
	public function processRequestAction(HttpRequest $request): ?Result
	{
		return null;
	}

	public function getControl(array $rawFields): ?array
	{
		$promptCode = $rawFields['ID'];
		$this->onclick = HtmlFilter::encode("BX.AI.SharePrompt.Library.Controller.editPrompt('$promptCode')");

		return parent::getControl($rawFields);
	}
}
