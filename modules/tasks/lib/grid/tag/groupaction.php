<?php

namespace Bitrix\Tasks\Grid\Tag;

use Bitrix\Main\Grid\Panel\Types;
use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Localization\Loc;

class GroupAction
{
	public function prepareGroupAction(): array
	{
		return [
			'GROUPS' => [
				[
					'ITEMS' => [
						[
							'TYPE' => Types::BUTTON,
							'TEXT' => Loc::getMessage('TASKS_USER_TAGS_DELETE_TAG_GROUP'),
							'NAME' => Loc::getMessage('TASKS_USER_TAGS_DELETE_TAG_GROUP'),
							'ONCHANGE' => [
								[
									'ACTION' => Actions::CALLBACK,
									'DATA' => [
										[
											'JS' => 'BX.Tasks.TagActionsObject.groupDelete()',
										],
									],
								],
							],
						],
					],
				],
			],
		];
	}
}