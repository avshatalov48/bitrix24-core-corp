<?php

namespace Bitrix\BiConnector\Settings\Grid\Row\Action\Settings;

use Bitrix\Main\Grid\Row\Action\BaseAction;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use CUtil;

class OpenAction extends BaseAction
{
	public function __construct(protected string $viewUrl, protected int $width = 1100, protected string $loader = 'default-loader')
	{
	}

	public static function getId(): ?string
	{
		return 'open';
	}

	public function processRequest(HttpRequest $request): ?Result
	{
		return null;
	}

	protected function getText(): string
	{
		return Loc::getMessage('SETTINGS_GRID_ROW_ACTION_OPEN_TITLE');
	}

	public function getControl(array $rawFields): ?array
	{
		$this->default = true;
		$url = str_replace('#ID#', urlencode($rawFields['ID']), $this->viewUrl);
		$this->onclick = 'BX.SidePanel.Instance.open(\''
			. CUtil::JSEscape($url)
			. '\', {width:'
			. $this->width
			. ', loader: \''
			. $this->loader
			. '\'})';

		return parent::getControl($rawFields);
	}
}
