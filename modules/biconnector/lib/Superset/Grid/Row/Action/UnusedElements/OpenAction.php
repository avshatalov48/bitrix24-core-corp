<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action\UnusedElements;

use Bitrix\Main\Grid\Row\Action\BaseAction;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class OpenAction extends BaseAction
{
	public static function getId(): ?string
	{
		return 'open';
	}

	public function getControl(array $rawFields): ?array
	{
		$openUrl = \CUtil::JSEscape((string)$rawFields['OPEN_URL']);

		$this->onclick = "BX.BIConnector.UnusedElementsGridManager.Instance.openElement(\"{$openUrl}\")";

		return parent::getControl($rawFields);
	}

	public function processRequest(HttpRequest $request): ?Result
	{
		return null;
	}

	protected function getText(): string
	{
		return Loc::getMessage('BI_UNUSED_ELEMENTS_GRID_ROW_ACTION_OPEN') ?? '';
	}
}
