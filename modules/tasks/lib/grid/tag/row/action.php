<?php

namespace Bitrix\Tasks\Grid\Tag\Row;

use Bitrix\Main\Localization\Loc;

class Action
{
	protected array $rowData = [];
	protected array $parameters = [];

	public function __construct(array $rowData = [], array $parameters = [])
	{
		$this->rowData = $rowData;
		$this->parameters = $parameters;
	}

	public function prepare(): array
	{
		$id = $this->rowData['ID'];
		return [
			[
				'text' => Loc::getMessage('TASKS_USER_TAGS_GRID_RENAME_ACTION'),
				'onclick' => "BX.Tasks.TagActionsObject.updateTag({$id})",
			],
			[
				'text' => Loc::getMessage('TASKS_USER_TAGS_GRID_DELETE_ACTION'),
				'onclick' => "BX.Tasks.TagActionsObject.deleteTag({$id})",
			],
		];
	}
}