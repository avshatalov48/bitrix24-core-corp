<?php

namespace Bitrix\Intranet\User\Grid\Panel\Action;

use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\Types;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class ChangeDepartmentAction extends JsAction
{

	public static function getId(): string
	{
		return 'changeDepartment';
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
			'TEXT' => Loc::getMessage('INTRANET_USER_LIST_GROUP_ACTION_CHANGE_DEPARTMENT_TITLE'),
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