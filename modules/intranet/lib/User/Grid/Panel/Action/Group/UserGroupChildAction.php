<?php

namespace Bitrix\Intranet\User\Grid\Panel\Action\Group;

use Bitrix\Intranet\User\Grid\Settings\UserSettings;
use Bitrix\Main\Grid\Panel\Action\Group\GroupChildAction;
use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\Snippet;
use Bitrix\Main\Grid\Panel\Snippet\Onchange;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

abstract class UserGroupChildAction extends GroupChildAction
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

	protected function getJsCallBack(): ?string
	{
		$actionParams = Json::encode([
			'actionId' => static::getId(),
			'gridId' => $this->getSettings()->getID(),
			'filter' => $this->getSettings()->getFilterFields(),
		]);

		return "BX.Intranet.UserList.Panel.executeAction($actionParams)";
	}

	final protected function getOnchange(): Onchange
	{
		return new Onchange([
			[
				'ACTION' => Actions::RESET_CONTROLS,
			],
			[
				'ACTION' => Actions::CREATE,
				'DATA' => [
					(new Snippet)->getApplyButton([
						'ONCHANGE' => [
							[
								'ACTION' => Actions::CALLBACK,
								'DATA' => [
									[
										'JS' => $this->getJsCallBack(),
									]
								],
							],
						],
					]),
				],
			],
		]);
	}
}
