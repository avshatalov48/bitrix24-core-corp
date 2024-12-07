<?php

namespace Bitrix\Intranet\User\Grid\Row\Action;

use Bitrix\Main\Localization\Loc;

class OpenProfileAction extends \Bitrix\Main\Grid\Row\Action\BaseAction
{
	public static function getId(): ?string
	{
		return 'open_profile';
	}

	public function processRequest(\Bitrix\Main\HttpRequest $request): ?\Bitrix\Main\Result
	{
		return null;
	}

	protected function getText(): string
	{
		return Loc::getMessage('INTRANET_USER_GRID_ROW_ACTIONS_OPEN_PROFILE') ?? '';
	}

	public function getControl(array $rawFields): ?array
	{
		$userId = $rawFields['ID'];
		$url = "/company/personal/user/$userId/";
		$this->onclick = "top.BX.SidePanel.Instance.open('$url')";

		return parent::getControl($rawFields);
	}
}