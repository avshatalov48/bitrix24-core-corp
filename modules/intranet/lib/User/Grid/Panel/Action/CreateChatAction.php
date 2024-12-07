<?php

namespace Bitrix\Intranet\User\Grid\Panel\Action;

use Bitrix\Intranet\User\Grid\Settings\UserSettings;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Grid\Panel\Action\Action;
use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\Types;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Json;

class CreateChatAction extends JsAction
{
	public static function getId(): string
	{
		return 'createChat';
	}

	public function processRequest(HttpRequest $request, bool $isSelectedAllRows, ?Filter $filter): ?Result
	{
		return new Result();
	}

	public function getControl(): ?array
	{
		return [
			'TYPE' => Types::BUTTON,
			'ID' => static::getId(),
			'TEXT' => Loc::getMessage('INTRANET_USER_LIST_GROUP_ACTION_CREATE_CHAT_TITLE'),
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
		];
	}
}