<?php

namespace Bitrix\Intranet\User\Grid\Panel\Action;

use Bitrix\Intranet\User\Grid\Settings\UserSettings;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Grid\Panel\Action\Action;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Json;

abstract class JsAction implements Action
{
	public function __construct(
		private readonly UserSettings $settings
	)
	{
	}

	protected function getSettings(): UserSettings
	{
		return $this->settings;
	}

	public function processRequest(HttpRequest $request, bool $isSelectedAllRows, ?Filter $filter): ?Result
	{
		return new Result();
	}

	protected function getJsCallBack(): ?string
	{
		$actionParams = Json::encode([
			'actionId' => $this->getId(),
			'gridId' => $this->getSettings()->getID(),
			'filter' => $this->getSettings()->getFilterFields(),
			'isCloud' => $this->getSettings()->isCloud(),
		]);

		return "BX.Intranet.UserList.Panel.executeAction($actionParams)";
	}
}