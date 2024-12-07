<?php

namespace Bitrix\Intranet\User\Grid\Row\Action;

use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Uri;

class AddTaskAction extends \Bitrix\Main\Grid\Row\Action\BaseAction
{

	public static function getId(): ?string
	{
		return 'add_task';
	}

	public function processRequest(HttpRequest $request): ?Result
	{
		return null;
	}

	protected function getText(): string
	{
		return Loc::getMessage('INTRANET_USER_GRID_ROW_ACTIONS_ADD_TASK') ?? '';
	}

	public function getControl(array $rawFields): ?array
	{
		if ($rawFields['ACTIVE'] === 'N' || !empty($rawFields['CONFIRM_CODE']))
		{
			return null;
		}

		$userId = $rawFields['ID'];

		$url = new Uri("/company/personal/user/$userId/tasks/task/edit/0/");
		$url->addParams([
			'RESPONSIBLE_ID' => $userId,
			'ta_sec' => 'user',
			'ta_el' => 'context_menu',
		]);
		$uri = $url->getUri();

		$this->onclick = "top.BX.SidePanel.Instance.open('$uri')";

		return parent::getControl($rawFields);
	}
}