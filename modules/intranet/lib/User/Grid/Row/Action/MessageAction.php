<?php

namespace Bitrix\Intranet\User\Grid\Row\Action;

use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class MessageAction extends \Bitrix\Main\Grid\Row\Action\BaseAction
{

	public static function getId(): ?string
	{
		return 'message';
	}

	public function processRequest(HttpRequest $request): ?Result
	{
		return null;
	}

	protected function getText(): string
	{
		return Loc::getMessage('INTRANET_USER_GRID_ROW_ACTIONS_MESSAGE') ?? '';
	}

	public function getControl(array $rawFields): ?array
	{
		$userId = $rawFields['ID'];

		$this->onclick = "top.BXIM.openMessenger($userId)";

		return parent::getControl($rawFields);
	}
}