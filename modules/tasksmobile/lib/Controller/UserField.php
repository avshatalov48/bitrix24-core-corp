<?php

namespace Bitrix\TasksMobile\Controller;

use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\TasksMobile\UserField\Dto\UserFieldDto;
use Bitrix\TasksMobile\UserField\Provider\TaskUserFieldProvider;

class UserField extends Base
{
	protected function getQueryActionNames(): array
	{
		return [
			'getUserFields',
		];
	}

	public function getUserFieldsAction(array $taskData = []): AjaxJson
	{
		return AjaxJson::createSuccess(
			array_map(
				static fn(array $field): UserFieldDto => UserFieldDto::make($field),
				(new TaskUserFieldProvider())->getUserFields($taskData),
			),
		);
	}
}
